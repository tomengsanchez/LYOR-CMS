<?php
namespace App\Controllers;

use App\AdminPath;
use App\Models\AppSettings;
use App\PublicTheme;
use App\ThemeStylePack;
use Core\Auth;
use Core\Controller;
use Core\Csrf;

/**
 * WordPress-style theme Customizer: sidebar controls + live public iframe.
 */
class CustomizeController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
        }
    }

    public function index(): void
    {
        $stored = PublicTheme::loadStoredConfig();
        if (!empty($_SESSION['pub_theme_preview']) && is_array($_SESSION['pub_theme_preview'])) {
            $theme = (object) array_merge((array) $stored, $_SESSION['pub_theme_preview']);
        } else {
            $theme = $stored;
            PublicTheme::setPreviewFromPost(PublicTheme::configToPostFields($stored));
        }

        $branding = AppSettings::getBrandingConfig();
        $flash = $_SESSION['style_pack_flash'] ?? null;
        unset($_SESSION['style_pack_flash']);
        $this->view('customize/shell', [
            'theme' => $theme,
            'branding' => $branding,
            'previewUrl' => PublicTheme::customizerFrameUrl('/'),
            'syncUrl' => AdminPath::url('customize/preview'),
            'publishUrl' => AdminPath::url('customize/publish'),
            'closeUrl' => AdminPath::url('customize/close'),
            'returnUrl' => AdminPath::url('system/general'),
            'bridgeConfig' => PublicTheme::configForBridge($theme),
            'stylePackName' => ThemeStylePack::packName(),
            'stylePackSource' => ThemeStylePack::packSource(),
            'stylePackCssUrl' => ThemeStylePack::activeCssPublicUrl(),
            'stylePackLibrary' => ThemeStylePack::listLibrary(),
            'stylePackActiveId' => ThemeStylePack::activePackId(),
            'importStylePackUrl' => AdminPath::url('customize/import-style-pack'),
            'clearStylePackUrl' => AdminPath::url('customize/clear-style-pack'),
            'exportStylePackUrl' => AdminPath::url('customize/export-style-pack'),
            'sampleStylePackUrl' => AdminPath::url('customize/sample-style-pack'),
            'playBuildSoundPackUrl' => AdminPath::url('customize/sample-style-pack') . '?pack=play-build-sound',
            'manlyPackUrl' => AdminPath::url('customize/sample-style-pack') . '?pack=manly',
            'stylePackFlash' => $flash,
        ]);
    }

    public function preview(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token.']]);
            return;
        }
        PublicTheme::setPreviewFromPost($_POST);
        $config = PublicTheme::configFromSessionPreview() ?? PublicTheme::loadStoredConfig();
        echo json_encode([
            'success' => true,
            'data' => [
                'config' => PublicTheme::configForBridge($config),
                'preview_url' => PublicTheme::customizerFrameUrl('/'),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function publish(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token.']]);
            return;
        }
        PublicTheme::saveConfig($_POST);
        echo json_encode([
            'success' => true,
            'data' => [
                'message' => 'Theme published.',
                'config' => PublicTheme::configForBridge(PublicTheme::loadStoredConfig()),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function close(): void
    {
        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            $this->redirect(AdminPath::url('system/general'));
            return;
        }
        PublicTheme::clearPreview();
        $this->redirect(AdminPath::url('system/general'));
    }

    public function importStylePack(): void
    {
        $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || (!empty($_POST['ajax']) && (string) $_POST['ajax'] === '1');

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token.']]);
                return;
            }
            $_SESSION['style_pack_flash'] = ['ok' => false, 'message' => 'Invalid security token.'];
            $this->redirect(AdminPath::url('customize'));
            return;
        }

        $result = ThemeStylePack::importUpload($_FILES['style_pack'] ?? []);
        PublicTheme::setPreviewFromPost(PublicTheme::configToPostFields());

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => ['message' => $result['message']]]);
                return;
            }
            echo json_encode([
                'success' => true,
                'data' => [
                    'message' => $result['message'],
                    'name' => $result['name'] ?? '',
                    'source' => $result['source'] ?? '',
                    'config' => PublicTheme::configForBridge(PublicTheme::loadStoredConfig()),
                    'css_url' => ThemeStylePack::activeCssPublicUrl(),
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $_SESSION['style_pack_flash'] = $result;
        $return = trim((string) ($_POST['return'] ?? ''));
        if ($return === 'general') {
            $this->redirect(AdminPath::url('system/general'));
            return;
        }
        $this->redirect(AdminPath::url('customize'));
    }

    public function clearStylePack(): void
    {
        $this->validateCsrf();
        ThemeStylePack::clearActivePack();
        $_SESSION['style_pack_flash'] = [
            'ok' => true,
            'message' => 'Active style pack cleared. Installed packs remain in the library.',
        ];
        $return = trim((string) ($_POST['return'] ?? ''));
        if ($return === 'general') {
            $this->redirect(AdminPath::url('system/general'));
            return;
        }
        $this->redirect(AdminPath::url('customize'));
    }

    public function activateStylePack(): void
    {
        $this->validateCsrf();
        $result = ThemeStylePack::activatePack((string) ($_POST['pack_id'] ?? ''));
        PublicTheme::setPreviewFromPost(PublicTheme::configToPostFields());
        $_SESSION['style_pack_flash'] = $result;
        $return = trim((string) ($_POST['return'] ?? ''));
        $this->redirect($return === 'general' ? AdminPath::url('system/general') : AdminPath::url('customize'));
    }

    public function deleteStylePack(): void
    {
        $this->validateCsrf();
        $result = ThemeStylePack::deletePack((string) ($_POST['pack_id'] ?? ''));
        $_SESSION['style_pack_flash'] = $result;
        $return = trim((string) ($_POST['return'] ?? ''));
        $this->redirect($return === 'general' ? AdminPath::url('system/general') : AdminPath::url('customize'));
    }

    public function installBundledStylePack(): void
    {
        $this->validateCsrf();
        $result = ThemeStylePack::installBundled((string) ($_POST['pack'] ?? 'example'));
        PublicTheme::setPreviewFromPost(PublicTheme::configToPostFields());
        $_SESSION['style_pack_flash'] = $result;
        $return = trim((string) ($_POST['return'] ?? ''));
        $this->redirect($return === 'general' ? AdminPath::url('system/general') : AdminPath::url('customize'));
    }

    public function exportStylePack(): void
    {
        try {
            $bin = ThemeStylePack::exportCurrentZipBinary();
        } catch (\Throwable $e) {
            $_SESSION['style_pack_flash'] = ['ok' => false, 'message' => 'Export failed: ' . $e->getMessage()];
            $this->redirect(AdminPath::url('customize'));
            return;
        }
        $filename = 'cms-style-pack-' . date('Ymd-His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: no-store');
        echo $bin;
        exit;
    }

    /** Download a bundled developer template zip (example or named packs). */
    public function downloadSampleStylePack(): void
    {
        $slug = ThemeStylePack::normalizePackSlug($_GET['pack'] ?? null);
        $path = ThemeStylePack::samplePackAbsolutePath($slug);
        if ($path === null) {
            $_SESSION['style_pack_flash'] = [
                'ok' => false,
                'message' => 'Pack zip missing. Run: php cli/build_style_pack.php',
            ];
            $return = trim((string) ($_GET['return'] ?? ''));
            $this->redirect($return === 'general' ? AdminPath::url('system/general') : AdminPath::url('customize'));
            return;
        }
        $filename = ThemeStylePack::samplePackDownloadName($slug);
        $size = filesize($path) ?: 0;
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $size);
        header('Cache-Control: public, max-age=3600');
        readfile($path);
        exit;
    }

    protected function csrfRedirectUrl(): string
    {
        $return = trim((string) ($_POST['return'] ?? ''));
        if ($return === 'general') {
            return AdminPath::url('system/general');
        }
        return AdminPath::url('customize');
    }
}
