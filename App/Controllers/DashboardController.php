<?php
namespace App\Controllers;

use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Comment;
use Core\Controller;
use Core\Database;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $pageCounts = Page::countsByStatus();
        $postCounts = Post::countsByStatus();
        $mediaCount = Media::countActive();
        $recentAudit = [];
        try {
            $recentAudit = Database::getInstance()->query('
                SELECT a.*, u.username AS created_by_name
                FROM audit_log a LEFT JOIN users u ON u.id = a.created_by
                ORDER BY a.created_at DESC LIMIT 10
            ')->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
        }
        $this->view('dashboard/index', [
            'pageCounts' => $pageCounts,
            'postCounts' => $postCounts,
            'mediaCount' => $mediaCount,
            'pendingComments' => Comment::pendingCount(),
            'recentPendingComments' => Comment::recentPending(5),
            'recentDraftPages' => Page::recentDrafts(5),
            'recentDraftPosts' => Post::recentDrafts(5),
            'recentAudit' => $recentAudit,
        ]);
    }
}
