<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;
use App\NotificationService;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->redirect(AdminPath::url('login'));
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'from'   => trim($_GET['from'] ?? ''),
            'to'     => trim($_GET['to'] ?? ''),
            'module' => trim($_GET['module'] ?? ''),
        ];

        $result = NotificationService::listForUser($userId, $filters, $page, 20);

        $this->view('notifications/index', [
            'notifications' => $result['items'],
            'filters'       => $filters,
            'pagination'    => $result,
        ]);
    }

    public function click(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->redirect(AdminPath::url('login'));
            return;
        }
        $url = NotificationService::clickAndGetUrl($id, $userId);
        $this->redirect($url ?? AdminPath::url());
    }
}
