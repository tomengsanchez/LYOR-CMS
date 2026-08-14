<?php
namespace App\Controllers;

use App\AdminPath;
use App\Models\Comment;
use Core\Auth;
use Core\Controller;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        if (!Auth::isAdmin() && !Auth::can('moderate_comments')) {
            $this->redirect(AdminPath::url());
            return;
        }
        $status = $_GET['status'] ?? '';
        $this->view('comments/index', [
            'comments' => Comment::allForAdmin($status !== '' ? $status : null),
            'statuses' => Comment::statuses(),
            'currentStatus' => $status,
            'pendingCount' => Comment::pendingCount(),
        ]);
    }

    public function approve(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin() && !Auth::can('moderate_comments')) {
            $this->redirect(AdminPath::url());
            return;
        }
        Comment::setStatus($id, Comment::STATUS_APPROVED);
        $this->redirect(AdminPath::url('comments'));
    }

    public function spam(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin() && !Auth::can('moderate_comments')) {
            $this->redirect(AdminPath::url());
            return;
        }
        Comment::setStatus($id, Comment::STATUS_SPAM);
        $this->redirect(AdminPath::url('comments'));
    }

    public function trash(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin() && !Auth::can('moderate_comments')) {
            $this->redirect(AdminPath::url());
            return;
        }
        Comment::delete($id);
        $this->redirect(AdminPath::url('comments'));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('comments');
    }
}
