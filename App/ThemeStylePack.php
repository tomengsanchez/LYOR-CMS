<?php
namespace App;

use App\Models\AppSettings;
use App\AuditLog;

/**
 * Import/export theme style packs (CMS zip or best-effort WordPress theme.zip).
 * Does not execute WordPress PHP templates.
 */
class ThemeStylePack
{
    public const FORMAT = 'cms-style-pack';
    public const VERSION = 1;
    public const MAX_BYTES = 2097152; // 2 MiB

    private const SETTING_NAME = 'pub_theme_pack_name';
    private const SETTING_SOURCE = 'pub_theme_pack_source';
    private const SETTING_CSS = 'pub_theme_pack_css';
    private const SETTING_LIBRARY = 'pub_theme_pack_library';
    private const SETTING_ACTIVE_ID = 'pub_theme_pack_active_id';

    private const ALLOWED_EXT = ['json', 'css', 'txt', 'png', 'jpg', 'jpeg', 'webp', 'svg'];

    /** @return list<string> */
    public static function allowedSettingKeys(): array
    {
        return array_keys(PublicTheme::configToPostFields());
    }

    public static function packName(): string
    {
        return trim((string) AppSettings::get(self::SETTING_NAME, ''));
    }

    public static function packSource(): string
    {
        $s = trim((string) AppSettings::get(self::SETTING_SOURCE, ''));
        return in_array($s, ['cms', 'wordpress', 'bundled'], true) ? $s : '';
    }

    public static function activePackId(): string
    {
        return trim((string) AppSettings::get(self::SETTING_ACTIVE_ID, ''));
    }

    /**
     * Installed packs (newest first).
     * @return list<array{id: string, name: string, source: string, bundled_slug: ?string, has_css: bool, created_at: string}>
     */
    public static function listLibrary(): array
    {
        $raw = AppSettings::get(self::SETTING_LIBRARY, '[]');
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $row) {
            if (!is_array($row) || empty($row['id'])) {
                continue;
            }
            $id = preg_replace('/[^a-f0-9]/', '', strtolower((string) $row['id'])) ?? '';
            if ($id === '' || !is_dir(self::libraryPackDir($id))) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => mb_substr(trim((string) ($row['name'] ?? 'Pack')), 0, 120) ?: 'Pack',
                'source' => (string) ($row['source'] ?? 'cms'),
                'bundled_slug' => isset($row['bundled_slug']) && $row['bundled_slug'] !== ''
                    ? (string) $row['bundled_slug']
                    : null,
                'has_css' => !empty($row['has_css']),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }
        return $out;
    }

    public static function activeCssPublicUrl(): ?string
    {
        $rel = trim((string) AppSettings::get(self::SETTING_CSS, ''));
        if ($rel === '' || str_contains($rel, '..')) {
            return null;
        }
        $abs = self::uploadsRoot() . '/' . str_replace('\\', '/', $rel);
        if (!is_file($abs)) {
            return null;
        }
        return '/public/uploads/' . ltrim(str_replace('\\', '/', $rel), '/');
    }

    /** Absolute path to a shipped style-pack zip. Default: developer template (example). */
    public static function samplePackAbsolutePath(?string $slug = null): ?string
    {
        $slug = self::normalizePackSlug($slug);
        $file = self::packZipFilename($slug);
        $root = dirname(__DIR__);
        $candidates = [
            $root . '/public/assets/theme-packs/' . $file,
            $root . '/docs/samples/' . $file,
        ];
        foreach ($candidates as $path) {
            if (is_file($path) && filesize($path) > 0) {
                return $path;
            }
        }
        return null;
    }

    public static function samplePackDownloadName(?string $slug = null): string
    {
        return self::packZipFilename(self::normalizePackSlug($slug));
    }

    /** @return array<string, string> slug => label */
    public static function bundledPackLabels(): array
    {
        return [
            'example' => 'Sample template',
            'play-build-sound' => 'Play · Build · Sound (gaming / web / music)',
            'manly' => 'Manly (oak, iron, leather)',
            'pulse' => 'Pulse (header & homepage widgets)',
            'enterprise' => 'Enterprise (modern navy & slate)',
            'filipino-men' => 'The Filipino Men (paper journal)',
        ];
    }

    public static function normalizePackSlug(?string $slug): string
    {
        $slug = strtolower(trim((string) $slug));
        if ($slug === '' || $slug === 'sample' || $slug === 'default' || $slug === 'template') {
            return 'example';
        }
        if ($slug === 'gaming' || $slug === 'music' || $slug === 'dev' || $slug === 'play') {
            return 'play-build-sound';
        }
        if (in_array($slug, ['manhood', 'lodge', 'oak', 'iron', 'forge'], true)) {
            return 'manly';
        }
        if (in_array($slug, ['gazette', 'harbor', 'civic', 'community'], true)) {
            return 'pulse';
        }
        if (in_array($slug, ['corporate', 'business', 'professional', 'b2b'], true)) {
            return 'enterprise';
        }
        if (in_array($slug, ['filipino', 'tfm', 'lalaki', 'everyday-manhood', 'the-filipino-men'], true)) {
            return 'filipino-men';
        }
        $allowed = array_keys(self::bundledPackLabels());
        return in_array($slug, $allowed, true) ? $slug : 'example';
    }

    private static function packZipFilename(string $slug): string
    {
        return match ($slug) {
            'example' => 'cms-style-pack-example.zip',
            default => 'cms-style-pack-' . $slug . '.zip',
        };
    }

    /** Public URL for a bundled pack when present under public/assets. */
    public static function samplePackPublicUrl(?string $slug = null): ?string
    {
        $file = self::packZipFilename(self::normalizePackSlug($slug));
        $root = dirname(__DIR__);
        $rel = '/public/assets/theme-packs/' . $file;
        if (is_file($root . $rel)) {
            return $rel;
        }
        return null;
    }

    /**
     * Import from a local zip path (CLI / smoke tests).
     * @return array{ok: bool, message: string, name?: string, source?: string}
     */
    public static function importFromPath(string $path): array
    {
        if (!is_file($path)) {
            return ['ok' => false, 'message' => 'File not found.'];
        }
        return self::importUpload([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $path,
            'size' => (int) (filesize($path) ?: 0),
            'name' => basename($path),
            'from_path' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $file $_FILES entry
     * @return array{ok: bool, message: string, name?: string, source?: string}
     */
    public static function importUpload(array $file): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return ['ok' => false, 'message' => 'PHP ZipArchive extension is required.'];
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Upload failed. Choose a .zip file.'];
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $fromUpload = !empty($file['from_path']);
        if ($tmp === '' || (!$fromUpload && !is_uploaded_file($tmp)) || ($fromUpload && !is_file($tmp))) {
            return ['ok' => false, 'message' => 'Invalid upload.'];
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            $size = (int) (@filesize($tmp) ?: 0);
        }
        if ($size <= 0 || $size > self::MAX_BYTES) {
            return ['ok' => false, 'message' => 'Zip must be under 2 MB.'];
        }
        $name = (string) ($file['name'] ?? '');
        if (!preg_match('/\.zip$/i', $name)) {
            return ['ok' => false, 'message' => 'File must be a .zip archive.'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) {
            return ['ok' => false, 'message' => 'Could not open zip archive.'];
        }

        $entries = self::listSafeEntries($zip);
        if ($entries === null) {
            $zip->close();
            return ['ok' => false, 'message' => 'Zip contains disallowed or unsafe paths (path traversal is blocked).'];
        }

        $cmsJsonPath = self::findEntry($entries, 'cms-theme.json');
        $styleCssPath = self::findEntry($entries, 'style.css');
        $wpThemeJsonPath = self::findEntry($entries, 'theme.json');

        if ($cmsJsonPath !== null) {
            $raw = $zip->getFromName($cmsJsonPath);
            $zip->close();
            if ($raw === false) {
                return ['ok' => false, 'message' => 'Could not read cms-theme.json.'];
            }
            return self::importCmsPack($raw, $tmp, $entries, $cmsJsonPath);
        }

        if ($styleCssPath !== null) {
            $styleRaw = $zip->getFromName($styleCssPath);
            $themeJsonRaw = $wpThemeJsonPath !== null ? $zip->getFromName($wpThemeJsonPath) : false;
            $zip->close();
            if ($styleRaw === false) {
                return ['ok' => false, 'message' => 'Could not read style.css.'];
            }
            if (!self::looksLikeWpStyleHeader($styleRaw)) {
                return ['ok' => false, 'message' => 'style.css does not look like a WordPress theme header. Use a CMS style pack (cms-theme.json) instead.'];
            }
            return self::importWordpressPartial($styleRaw, is_string($themeJsonRaw) ? $themeJsonRaw : null, $tmp, $entries);
        }

        $zip->close();
        return ['ok' => false, 'message' => 'Unrecognized pack. Include cms-theme.json (CMS) or a WordPress style.css Theme Name header.'];
    }

    /** Clear active pack pointer/CSS only; installed library packs are kept. */
    public static function clearActivePack(): void
    {
        self::wipeActiveDir();
        AppSettings::set(self::SETTING_NAME, '');
        AppSettings::set(self::SETTING_SOURCE, '');
        AppSettings::set(self::SETTING_CSS, '');
        AppSettings::set(self::SETTING_ACTIVE_ID, '');
        AuditLog::record('theme_style_pack', 0, 'cleared');
    }

    /**
     * Apply an installed library pack (settings + CSS).
     * @return array{ok: bool, message: string, name?: string, source?: string, id?: string}
     */
    public static function activatePack(string $id): array
    {
        $id = preg_replace('/[^a-f0-9]/', '', strtolower($id)) ?? '';
        if ($id === '') {
            return ['ok' => false, 'message' => 'Invalid pack id.'];
        }
        $dir = self::libraryPackDir($id);
        $settingsFile = $dir . '/settings.json';
        if (!is_file($settingsFile)) {
            return ['ok' => false, 'message' => 'Pack not found in library.'];
        }
        $settings = json_decode((string) file_get_contents($settingsFile), true);
        if (!is_array($settings)) {
            return ['ok' => false, 'message' => 'Pack settings are corrupt.'];
        }
        $meta = self::findLibraryMeta($id);
        $name = $meta['name'] ?? 'Pack';
        $source = $meta['source'] ?? 'cms';

        $allowed = array_flip(self::allowedSettingKeys());
        $filtered = [];
        foreach ($settings as $key => $value) {
            $key = (string) $key;
            if (!isset($allowed[$key])) {
                continue;
            }
            $filtered[$key] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }
        $post = array_merge(PublicTheme::configToPostFields(), $filtered);
        PublicTheme::saveConfig($post);

        $cssRel = is_file($dir . '/extra.css') ? ('theme-packs/library/' . $id . '/extra.css') : '';
        AppSettings::set(self::SETTING_NAME, $name);
        AppSettings::set(self::SETTING_SOURCE, $source);
        AppSettings::set(self::SETTING_CSS, $cssRel);
        AppSettings::set(self::SETTING_ACTIVE_ID, $id);
        AuditLog::record('theme_style_pack', 0, 'activated', ['id' => $id, 'name' => $name]);

        $message = 'Activated style pack: ' . $name . '.';
        $widgetsFile = $dir . '/widgets.json';
        if (is_file($widgetsFile)) {
            $map = json_decode((string) file_get_contents($widgetsFile), true);
            $filled = \App\Models\Widget::installStarterIfEmpty(is_array($map) ? $map : []);
            if ($filled > 0) {
                $message .= ' Filled ' . $filled . ' empty widget area(s) (occupied areas were left unchanged).';
            }
        }

        return [
            'ok' => true,
            'message' => $message,
            'name' => $name,
            'source' => $source,
            'id' => $id,
        ];
    }

    /**
     * Remove a pack from the library (and deactivate if it was active).
     * @return array{ok: bool, message: string}
     */
    public static function deletePack(string $id): array
    {
        $id = preg_replace('/[^a-f0-9]/', '', strtolower($id)) ?? '';
        if ($id === '') {
            return ['ok' => false, 'message' => 'Invalid pack id.'];
        }
        $wasActive = self::activePackId() === $id;
        self::removeLibraryMeta($id);
        self::wipeDir(self::libraryPackDir($id));
        if ($wasActive) {
            AppSettings::set(self::SETTING_NAME, '');
            AppSettings::set(self::SETTING_SOURCE, '');
            AppSettings::set(self::SETTING_CSS, '');
            AppSettings::set(self::SETTING_ACTIVE_ID, '');
        }
        AuditLog::record('theme_style_pack', 0, 'deleted', ['id' => $id]);
        return ['ok' => true, 'message' => 'Style pack removed from library.'];
    }

    /**
     * Install a bundled sample zip into the library (or re-activate if already installed).
     * @return array{ok: bool, message: string, name?: string, source?: string, id?: string}
     */
    public static function installBundled(string $slug): array
    {
        $slug = self::normalizePackSlug($slug);
        foreach (self::listLibrary() as $row) {
            if (($row['bundled_slug'] ?? null) === $slug) {
                return self::activatePack($row['id']);
            }
        }
        $path = self::samplePackAbsolutePath($slug);
        if ($path === null) {
            return ['ok' => false, 'message' => 'Bundled zip not found. Run php cli/build_style_pack.php'];
        }
        $result = self::importFromPath($path);
        if (!$result['ok']) {
            return $result;
        }
        $lib = self::listLibrary();
        if ($lib !== []) {
            $id = $lib[0]['id'];
            self::updateLibraryMeta($id, ['bundled_slug' => $slug, 'source' => 'bundled']);
            AppSettings::set(self::SETTING_SOURCE, 'bundled');
            $result['source'] = 'bundled';
            $result['id'] = $id;
            $result['message'] = 'Installed & activated: ' . ($result['name'] ?? $slug) . '.';
        }
        return $result;
    }

    /**
     * Build a CMS style-pack zip of current settings (binary string).
     */
    public static function exportCurrentZipBinary(): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive required');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'cms_theme_pack_');
        if ($tmp === false) {
            throw new \RuntimeException('temp file failed');
        }
        $zipPath = $tmp . '.zip';
        @unlink($tmp);
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create zip');
        }
        $name = self::packName() !== '' ? self::packName() : 'Current theme';
        $payload = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'name' => $name,
            'settings' => PublicTheme::configToPostFields(),
        ];
        $cssRel = trim((string) AppSettings::get(self::SETTING_CSS, ''));
        $cssAbs = $cssRel !== '' ? self::uploadsRoot() . '/' . str_replace('\\', '/', $cssRel) : '';
        if ($cssAbs !== '' && is_file($cssAbs)) {
            $payload['extra_css'] = 'extra.css';
            $zip->addFromString('extra.css', (string) file_get_contents($cssAbs));
        }
        $zip->addFromString('cms-theme.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('README.txt', "CMS style pack v1\nImport via Appearance → Customize or System → General.\nDoes not run WordPress PHP themes.\n");
        $zip->close();
        $bin = (string) file_get_contents($zipPath);
        @unlink($zipPath);
        return $bin;
    }

    /** @return array{ok: bool, message: string, name?: string, source?: string, id?: string} */
    private static function importCmsPack(string $jsonRaw, string $zipTmp, array $entries, string $cmsJsonPath): array
    {
        $data = json_decode($jsonRaw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'cms-theme.json is not valid JSON.'];
        }
        $format = (string) ($data['format'] ?? '');
        if ($format !== '' && $format !== self::FORMAT) {
            return ['ok' => false, 'message' => 'Unsupported format (expected cms-style-pack).'];
        }
        $version = (int) ($data['version'] ?? 1);
        if ($version < 1 || $version > self::VERSION) {
            return ['ok' => false, 'message' => 'Unsupported style pack version.'];
        }
        $packName = trim((string) ($data['name'] ?? 'Imported pack'));
        if ($packName === '') {
            $packName = 'Imported pack';
        }
        if (mb_strlen($packName) > 120) {
            $packName = mb_substr($packName, 0, 120);
        }

        $settingsIn = is_array($data['settings'] ?? null) ? $data['settings'] : [];
        $allowed = array_flip(self::allowedSettingKeys());
        $filtered = [];
        foreach ($settingsIn as $key => $value) {
            $key = (string) $key;
            if (!isset($allowed[$key])) {
                continue;
            }
            if (is_bool($value)) {
                $filtered[$key] = $value ? '1' : '0';
            } elseif (is_int($value) || is_float($value)) {
                $filtered[$key] = (string) $value;
            } else {
                $filtered[$key] = (string) $value;
            }
        }

        $post = array_merge(PublicTheme::configToPostFields(), $filtered);
        $extraCssName = trim((string) ($data['extra_css'] ?? ''));
        $id = self::storeLibraryPack($zipTmp, $entries, $packName, 'cms', $post, $extraCssName !== '' ? $extraCssName : null, null);
        if ($id === null) {
            return ['ok' => false, 'message' => 'Could not store pack in library.'];
        }
        $widgets = \App\Models\Widget::sanitizeStarterMap(is_array($data['widgets'] ?? null) ? $data['widgets'] : []);
        if ($widgets !== []) {
            $wfile = self::libraryPackDir($id) . '/widgets.json';
            file_put_contents($wfile, json_encode($widgets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        AuditLog::record('theme_style_pack', 0, 'imported_cms', ['name' => $packName, 'id' => $id]);
        return self::activatePack($id);
    }

    /** @return array{ok: bool, message: string, name?: string, source?: string, id?: string} */
    private static function importWordpressPartial(string $styleCss, ?string $themeJson, string $zipTmp, array $entries): array
    {
        $meta = self::parseWpStyleHeaders($styleCss);
        $packName = $meta['name'] !== '' ? $meta['name'] : 'WordPress theme (partial)';
        if (mb_strlen($packName) > 120) {
            $packName = mb_substr($packName, 0, 120);
        }

        $post = PublicTheme::configToPostFields();
        $colors = self::mapWpColors($themeJson);
        if ($colors !== []) {
            $post = array_merge($post, $colors);
            if (isset($colors['pub_theme_custom_bg']) || isset($colors['pub_theme_custom_text']) || isset($colors['pub_theme_custom_surface'])) {
                $post['pub_theme_preset'] = PublicTheme::PRESET_CUSTOM;
            }
        }

        $extra = "/* Imported from WordPress theme (partial) — PHP templates not applied */\n";
        if (!empty($post['public_accent_color'])) {
            $accent = preg_replace('/[^#a-fA-F0-9]/', '', (string) $post['public_accent_color']);
            if ($accent !== '') {
                $extra .= ":root { --pub-accent: {$accent}; }\n";
            }
        }

        $id = bin2hex(random_bytes(8));
        $dir = self::libraryPackDir($id);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'message' => 'Could not create theme-packs library directory.'];
        }
        file_put_contents($dir . '/settings.json', json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        file_put_contents($dir . '/extra.css', self::sanitizeCss($extra));
        @copy($zipTmp, $dir . '/pack.zip');
        self::extractImagesToDir($zipTmp, $entries, $dir);
        self::prependLibraryMeta([
            'id' => $id,
            'name' => $packName,
            'source' => 'wordpress',
            'bundled_slug' => null,
            'has_css' => true,
            'created_at' => date('c'),
        ]);
        AuditLog::record('theme_style_pack', 0, 'imported_wordpress', ['name' => $packName, 'id' => $id]);
        $activated = self::activatePack($id);
        if ($activated['ok']) {
            $activated['message'] = 'WordPress theme zip saved & activated (colors/metadata only): ' . $packName . '. PHP templates were ignored.';
        }
        return $activated;
    }

    /**
     * @param list<string> $entries
     * @param array<string, string> $postSettings
     */
    private static function storeLibraryPack(
        string $zipTmp,
        array $entries,
        string $packName,
        string $source,
        array $postSettings,
        ?string $extraCssName,
        ?string $bundledSlug
    ): ?string {
        $id = bin2hex(random_bytes(8));
        $dir = self::libraryPackDir($id);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        file_put_contents($dir . '/settings.json', json_encode($postSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @copy($zipTmp, $dir . '/pack.zip');
        $hasCss = self::extractCssAndImagesToDir($zipTmp, $entries, $dir, $extraCssName);
        self::prependLibraryMeta([
            'id' => $id,
            'name' => $packName,
            'source' => $source,
            'bundled_slug' => $bundledSlug,
            'has_css' => $hasCss,
            'created_at' => date('c'),
        ]);
        return $id;
    }

    /**
     * @param list<string> $entries
     */
    private static function extractCssAndImagesToDir(string $zipTmp, array $entries, string $dir, ?string $extraCssName): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipTmp) !== true) {
            return false;
        }
        $hasCss = false;
        if ($extraCssName !== null && $extraCssName !== '') {
            $cssEntry = self::findEntry($entries, basename(str_replace('\\', '/', $extraCssName)));
            if ($cssEntry === null) {
                $want = ltrim(str_replace('\\', '/', $extraCssName), '/');
                foreach ($entries as $e) {
                    if (strcasecmp($e, $want) === 0 || strcasecmp(basename($e), basename($want)) === 0) {
                        $cssEntry = $e;
                        break;
                    }
                }
            }
            if ($cssEntry !== null) {
                $raw = $zip->getFromName($cssEntry);
                if (is_string($raw)) {
                    file_put_contents($dir . '/extra.css', self::sanitizeCss($raw));
                    $hasCss = true;
                }
            }
        }
        foreach ($entries as $entry) {
            $base = basename($entry);
            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                continue;
            }
            $raw = $zip->getFromName($entry);
            if (!is_string($raw) || $raw === '') {
                continue;
            }
            $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $base) ?: 'image.' . $ext;
            file_put_contents($dir . '/' . $safe, $raw);
        }
        $zip->close();
        return $hasCss;
    }

    /** @param list<string> $entries */
    private static function extractImagesToDir(string $zipTmp, array $entries, string $dir): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipTmp) !== true) {
            return;
        }
        foreach ($entries as $entry) {
            $base = basename($entry);
            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                continue;
            }
            if (!preg_match('/screenshot|preview|logo/i', $base)) {
                continue;
            }
            $raw = $zip->getFromName($entry);
            if (!is_string($raw) || strlen($raw) > 500000) {
                continue;
            }
            $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $base) ?: 'image.' . $ext;
            @file_put_contents($dir . '/' . $safe, $raw);
        }
        $zip->close();
    }

    /** @param array<string, mixed> $meta */
    private static function prependLibraryMeta(array $meta): void
    {
        $list = self::listLibrary();
        array_unshift($list, [
            'id' => (string) $meta['id'],
            'name' => (string) ($meta['name'] ?? 'Pack'),
            'source' => (string) ($meta['source'] ?? 'cms'),
            'bundled_slug' => $meta['bundled_slug'] ?? null,
            'has_css' => !empty($meta['has_css']),
            'created_at' => (string) ($meta['created_at'] ?? date('c')),
        ]);
        self::saveLibraryList($list);
    }

    /** @param array<string, mixed> $patch */
    private static function updateLibraryMeta(string $id, array $patch): void
    {
        $list = self::listLibrary();
        foreach ($list as $i => $row) {
            if ($row['id'] === $id) {
                $list[$i] = array_merge($row, $patch, ['id' => $id]);
                break;
            }
        }
        self::saveLibraryList($list);
    }

    private static function removeLibraryMeta(string $id): void
    {
        $list = array_values(array_filter(
            self::listLibrary(),
            static fn(array $row): bool => $row['id'] !== $id
        ));
        self::saveLibraryList($list);
    }

    /** @return array{id: string, name: string, source: string, bundled_slug: ?string, has_css: bool, created_at: string}|null */
    private static function findLibraryMeta(string $id): ?array
    {
        foreach (self::listLibrary() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    /** @param list<array<string, mixed>> $list */
    private static function saveLibraryList(array $list): void
    {
        // Cap library size for safety
        if (count($list) > 40) {
            $list = array_slice($list, 0, 40);
        }
        AppSettings::set(self::SETTING_LIBRARY, json_encode(array_values($list), JSON_UNESCAPED_UNICODE));
    }

    private static function libraryPackDir(string $id): string
    {
        return self::uploadsRoot() . '/theme-packs/library/' . $id;
    }

    /**
     * @param list<string> $entries
     * @return string|null relative path under uploads, or null
     * @deprecated kept for legacy active/ path; new imports use library/
     */
    private static function extractActiveAssets(string $zipTmp, array $entries, ?string $extraCssName): ?string
    {
        self::wipeActiveDir();
        $dir = self::activeDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        $has = self::extractCssAndImagesToDir($zipTmp, $entries, $dir, $extraCssName);
        return $has ? 'theme-packs/active/extra.css' : null;
    }

    /** @param list<string> $entries */
    private static function extractImagesOnly(string $zipTmp, array $entries): void
    {
        self::extractImagesToDir($zipTmp, $entries, self::activeDir());
    }

    /** @return array<string, string> post fields */
    private static function mapWpColors(?string $themeJson): array
    {
        if ($themeJson === null || trim($themeJson) === '') {
            return [];
        }
        $data = json_decode($themeJson, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        $palette = $data['settings']['color']['palette'] ?? null;
        if (is_array($palette)) {
            foreach ($palette as $swatch) {
                if (!is_array($swatch)) {
                    continue;
                }
                $slug = strtolower((string) ($swatch['slug'] ?? ''));
                $color = self::normalizeHex((string) ($swatch['color'] ?? ''));
                if ($color === null) {
                    continue;
                }
                if (in_array($slug, ['primary', 'accent', 'brand', 'main'], true) && !isset($out['public_accent_color'])) {
                    $out['public_accent_color'] = $color;
                }
                if (in_array($slug, ['background', 'base', 'body'], true) && !isset($out['pub_theme_custom_bg'])) {
                    $out['pub_theme_custom_bg'] = $color;
                }
                if (in_array($slug, ['surface', 'card', 'secondary'], true) && !isset($out['pub_theme_custom_surface'])) {
                    $out['pub_theme_custom_surface'] = $color;
                }
                if (in_array($slug, ['text', 'foreground', 'contrast'], true) && !isset($out['pub_theme_custom_text'])) {
                    $out['pub_theme_custom_text'] = $color;
                }
            }
        }
        $stylesColor = $data['styles']['color'] ?? null;
        if (is_array($stylesColor)) {
            $bg = self::normalizeHex((string) ($stylesColor['background'] ?? ''));
            $text = self::normalizeHex((string) ($stylesColor['text'] ?? ''));
            if ($bg !== null) {
                $out['pub_theme_custom_bg'] = $bg;
            }
            if ($text !== null) {
                $out['pub_theme_custom_text'] = $text;
            }
        }
        return $out;
    }

    private static function normalizeHex(string $color): ?string
    {
        $color = trim($color);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return AppSettings::normalizeAccentColor($color);
        }
        return null;
    }

    /** @return array{name: string, description: string} */
    private static function parseWpStyleHeaders(string $css): array
    {
        $head = substr($css, 0, 8192);
        $name = '';
        $desc = '';
        if (preg_match('/^[ \t]*Theme Name:\s*(.+)$/mi', $head, $m)) {
            $name = trim($m[1]);
        }
        if (preg_match('/^[ \t]*Description:\s*(.+)$/mi', $head, $m)) {
            $desc = trim($m[1]);
        }
        return ['name' => $name, 'description' => $desc];
    }

    private static function looksLikeWpStyleHeader(string $css): bool
    {
        return (bool) preg_match('/^[ \t]*Theme Name:\s*.+/mi', substr($css, 0, 8192));
    }

    public static function sanitizeCss(string $css): string
    {
        if (strlen($css) > 400000) {
            $css = substr($css, 0, 400000);
        }
        $css = preg_replace('/@import\b[^;]*;/i', '/* @import removed */', $css) ?? $css;
        $css = preg_replace('/expression\s*\(/i', 'invalid(', $css) ?? $css;
        $css = preg_replace('/behavior\s*:/i', '/*behavior:*/', $css) ?? $css;
        $css = preg_replace('/javascript\s*:/i', 'blocked:', $css) ?? $css;
        $css = preg_replace('/-moz-binding\s*:/i', '/*-moz-binding:*/', $css) ?? $css;
        return $css;
    }

    /**
     * @return list<string>|null
     */
    private static function listSafeEntries(\ZipArchive $zip): ?array
    {
        $out = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || !isset($stat['name'])) {
                continue;
            }
            $name = str_replace('\\', '/', (string) $stat['name']);
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }
            if (str_starts_with($name, '/') || str_contains($name, '..')) {
                return null;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === 'php' || $ext === 'phtml' || $ext === 'phar' || $ext === 'inc') {
                // Never extract PHP; skip (WordPress theme zips always include templates).
                continue;
            }
            if ($ext !== '' && !in_array($ext, self::ALLOWED_EXT, true)) {
                // Skip unknown extensions silently (e.g. .map) rather than failing whole zip
                continue;
            }
            if ($ext === '' && !preg_match('/(^|\/)readme(\.txt)?$/i', $name)) {
                continue;
            }
            $out[] = $name;
        }
        return $out;
    }

    /** @param list<string> $entries */
    private static function findEntry(array $entries, string $basename): ?string
    {
        $basename = strtolower($basename);
        foreach ($entries as $e) {
            if (strcasecmp(basename($e), $basename) === 0) {
                return $e;
            }
        }
        return null;
    }

    private static function uploadsRoot(): string
    {
        return dirname(__DIR__) . '/public/uploads';
    }

    private static function activeDir(): string
    {
        return self::uploadsRoot() . '/theme-packs/active';
    }

    private static function wipeActiveDir(): void
    {
        self::wipeDir(self::activeDir());
    }

    private static function wipeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}
