<?php
namespace App\Controllers;

use App\AdminPath;

use App\Models\Media;
use Core\Auth;
use Core\Controller;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_media');
        $this->view('media/index', [
            'mediaItems' => Media::allActive(),
            'uploadError' => $_SESSION['media_upload_error'] ?? '',
            'uploadSuccess' => $_SESSION['media_upload_success'] ?? '',
        ]);
    }

    public function upload(): void
    {
        $this->validateCsrf();
        if (!$this->userCanUploadMedia()) {
            $_SESSION['media_upload_error'] = 'You do not have permission to upload media.';
            $this->redirect(AdminPath::url('media'));
            return;
        }
        $file = $_FILES['file'] ?? null;
        if (!$file || !is_array($file)) {
            $_SESSION['media_upload_error'] = 'No file selected.';
            $this->redirect(AdminPath::url('media'));
            return;
        }
        $id = Media::createFromUpload($file, trim($_POST['alt_text'] ?? '') ?: null);
        if (!$id) {
            $_SESSION['media_upload_error'] = 'Upload failed. Allowed: JPG, PNG, GIF, WebP, SVG, PDF.';
            $this->redirect(AdminPath::url('media'));
            return;
        }
        unset($_SESSION['media_upload_error']);
        $_SESSION['media_upload_success'] = 'File uploaded. Responsive sizes generated for JPG/PNG/WebP when possible.';
        $this->redirect(AdminPath::url('media'));
    }

    /**
     * AJAX upload for page/post forms and block builder (JSON).
     * Uses Csrf::check (no rotate) so the open content form stays valid.
     */
    public function uploadJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!\Core\Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token. Refresh and try again.']]);
            return;
        }
        if (!$this->userCanUploadMedia()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'You do not have permission to upload media.']]);
            return;
        }
        $file = $_FILES['file'] ?? null;
        if (!$file || !is_array($file)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ['message' => 'No file selected.']]);
            return;
        }
        $id = Media::createFromUpload($file, trim($_POST['alt_text'] ?? '') ?: null);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ['message' => 'Upload failed. Allowed: JPG, PNG, GIF, WebP, SVG, PDF.']]);
            return;
        }
        $media = Media::find($id);
        if (!$media || !Media::isImageMime((string) ($media->mime_type ?? ''))) {
            // Non-image upload still succeeds for media library; pickers only need images
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'name' => $media->original_name ?? '',
                    'is_image' => false,
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode([
            'success' => true,
            'data' => array_merge(Media::toPickerItem($media), ['is_image' => true]),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function userCanUploadMedia(): bool
    {
        if (Auth::can('upload_media')) {
            return true;
        }
        // Content editors can upload while writing pages/posts (WordPress-like).
        return Auth::canAny(['add_posts', 'edit_posts', 'add_pages', 'edit_pages']);
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_media');
        Media::softDelete($id);
        $this->redirect(AdminPath::url('media'));
    }

    public function serve(int $id, string $size = 'full'): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            exit;
        }
        $media = Media::find($id);
        if (!$media) {
            http_response_code(404);
            exit;
        }
        $resolved = Media::resolveServePath($media, $size);
        if (!$resolved) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . $resolved['mime']);
        header('Content-Length: ' . filesize($resolved['path']));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($resolved['path']);
        exit;
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('media');
    }
}
