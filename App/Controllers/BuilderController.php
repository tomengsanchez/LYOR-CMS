<?php
namespace App\Controllers;

use App\AdminPath;
use App\LayoutBuilder;
use App\Models\LayoutTemplate;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Permalink;
use Core\Auth;
use Core\Controller;
use Core\Csrf;

class BuilderController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function editPage(int $id): void
    {
        $this->requireCapability('edit_pages');
        $page = Page::find($id);
        if (!$page) {
            $this->redirect(AdminPath::url('pages'));
            return;
        }
        $this->renderEditor('page', $page);
    }

    public function editPost(int $id): void
    {
        $this->requireCapability('edit_posts');
        $post = Post::find($id);
        if (!$post) {
            $this->redirect(AdminPath::url('posts'));
            return;
        }
        $this->renderEditor('post', $post);
    }

    public function savePage(int $id): void
    {
        $this->saveLayout('page', $id);
    }

    public function savePost(int $id): void
    {
        $this->saveLayout('post', $id);
    }

    public function listTemplates(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->canUseBuilderTemplates()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Permission denied.']]);
            return;
        }
        $items = [];
        foreach (LayoutTemplate::all() as $row) {
            $items[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'created_by_name' => (string) ($row->created_by_name ?? ''),
                'updated_at' => (string) ($row->updated_at ?? ''),
            ];
        }
        echo json_encode(['success' => true, 'data' => ['items' => $items]], JSON_UNESCAPED_UNICODE);
    }

    public function getTemplate(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->canUseBuilderTemplates()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Permission denied.']]);
            return;
        }
        $row = LayoutTemplate::find($id);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => ['message' => 'Template not found.']]);
            return;
        }
        $layout = LayoutTemplate::layoutArray($row);
        if ($layout === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ['message' => 'Template has no layout.']]);
            return;
        }
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'layout' => $layout,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function saveTemplate(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->canUseBuilderTemplates()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Permission denied.']]);
            return;
        }
        $payload = $this->readJsonOrPost();
        if (!$this->checkCsrfFromPayload($payload)) {
            return;
        }
        $name = trim((string) ($payload['name'] ?? ''));
        $raw = $payload['layout_json'] ?? null;
        if (is_array($payload['layout'] ?? null)) {
            $raw = json_encode($payload['layout'], JSON_UNESCAPED_UNICODE);
        }
        $id = LayoutTemplate::create($name, is_string($raw) ? $raw : null);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ['message' => 'Could not save template. Provide a name and valid layout.']]);
            return;
        }
        $row = LayoutTemplate::find($id);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'name' => (string) ($row->name ?? $name),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function deleteTemplate(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->canUseBuilderTemplates()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Permission denied.']]);
            return;
        }
        $payload = $this->readJsonOrPost();
        if (!$this->checkCsrfFromPayload($payload)) {
            return;
        }
        if (!LayoutTemplate::delete($id)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => ['message' => 'Template not found.']]);
            return;
        }
        echo json_encode(['success' => true, 'data' => ['deleted' => true]]);
    }

    private function canUseBuilderTemplates(): bool
    {
        return Auth::isAdmin() || Auth::canAny([
            'edit_pages', 'edit_posts', 'add_pages', 'add_posts',
        ]);
    }

    /** @return array<string, mixed> */
    private function readJsonOrPost(): array
    {
        $ct = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($ct, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            return is_array($decoded) ? $decoded : [];
        }
        return $_POST;
    }

    /** @param array<string, mixed> $payload */
    private function checkCsrfFromPayload(array $payload): bool
    {
        $token = $payload['csrf_token'] ?? $_POST['csrf_token'] ?? null;
        if (!Csrf::check($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token. Refresh and try again.']]);
            return false;
        }
        return true;
    }

    private function renderEditor(string $entityType, object $entity): void
    {
        $layout = LayoutBuilder::parse($entity->layout_json ?? null);
        if ($layout['sections'] === []) {
            $layout = LayoutBuilder::starterLayout();
        }
        $canUpload = Auth::can('upload_media')
            || Auth::canAny(['add_posts', 'edit_posts', 'add_pages', 'edit_pages']);
        $backUrl = $entityType === 'page'
            ? AdminPath::url('pages/edit/' . (int) $entity->id)
            : AdminPath::url('posts/edit/' . (int) $entity->id);
        $saveUrl = $entityType === 'page'
            ? AdminPath::url('builder/page/' . (int) $entity->id . '/save')
            : AdminPath::url('builder/post/' . (int) $entity->id . '/save');
        $previewUrl = '';
        if (($entity->status ?? '') === 'published') {
            $previewUrl = $entityType === 'page'
                ? Permalink::urlForPage($entity)
                : Permalink::urlForPost($entity);
        }

        $templates = [];
        foreach (LayoutTemplate::all() as $row) {
            $templates[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
            ];
        }

        $this->view('builder/editor', [
            'entityType' => $entityType,
            'entity' => $entity,
            'layoutJson' => json_encode($layout, JSON_UNESCAPED_UNICODE),
            'moduleTypesJson' => json_encode(LayoutBuilder::moduleCatalog(), JSON_UNESCAPED_UNICODE),
            'mediaJson' => json_encode(Media::listImagesForPicker(), JSON_UNESCAPED_UNICODE),
            'templatesJson' => json_encode($templates, JSON_UNESCAPED_UNICODE),
            'backUrl' => $backUrl,
            'saveUrl' => $saveUrl,
            'templatesUrl' => AdminPath::url('builder/templates'),
            'previewUrl' => $previewUrl,
            'canUploadMedia' => $canUpload,
            'uploadUrl' => AdminPath::url('media/upload-json'),
        ]);
    }

    private function saveLayout(string $entityType, int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token. Refresh and try again.']]);
            return;
        }

        if ($entityType === 'page') {
            if (!Auth::can('edit_pages') && !Auth::isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => ['message' => 'Permission denied.']]);
                return;
            }
            $entity = Page::find($id);
        } else {
            if (!Auth::can('edit_posts') && !Auth::isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => ['message' => 'Permission denied.']]);
                return;
            }
            $entity = Post::find($id);
        }

        if (!$entity) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => ['message' => 'Not found.']]);
            return;
        }

        $raw = $_POST['layout_json'] ?? '';
        if ($raw === '' && isset($_SERVER['CONTENT_TYPE']) && str_contains((string) $_SERVER['CONTENT_TYPE'], 'application/json')) {
            $body = file_get_contents('php://input');
            $decoded = json_decode((string) $body, true);
            if (is_array($decoded)) {
                $raw = json_encode($decoded['layout'] ?? $decoded, JSON_UNESCAPED_UNICODE);
                if (!empty($decoded['csrf_token']) && !Csrf::check((string) $decoded['csrf_token'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token.']]);
                    return;
                }
            }
        }

        $ok = $entityType === 'page'
            ? Page::saveLayoutJson($id, is_string($raw) ? $raw : null)
            : Post::saveLayoutJson($id, is_string($raw) ? $raw : null);

        if (!$ok) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ['message' => 'Could not save layout.']]);
            return;
        }

        $fresh = $entityType === 'page' ? Page::find($id) : Post::find($id);
        $layout = LayoutBuilder::parse($fresh->layout_json ?? null);
        echo json_encode([
            'success' => true,
            'data' => [
                'layout' => $layout,
                'has_layout' => LayoutBuilder::hasLayout($fresh),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
}
