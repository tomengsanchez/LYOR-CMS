<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;
use App\AuditLog;
use Core\Database;

class AuditTrailController extends Controller
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'module' => trim($_GET['module'] ?? ''),
            'from'   => trim($_GET['from'] ?? ''),
            'to'     => trim($_GET['to'] ?? ''),
            'user_id' => !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null,
        ];

        $result = AuditLog::listForTrail($filters, $page, 25);
        $users = $this->getUsersForFilter();

        $this->view('audit_trail/index', [
            'items'      => $result['items'],
            'filters'    => $filters,
            'pagination' => $result,
            'users'     => $users,
        ]);
    }

    private function getUsersForFilter(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT id, username, display_name FROM users ORDER BY username');
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];
    }
}
