<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;
use Core\Database;
use App\ListConfig;
use App\ListHelper;
use App\CsvExporter;
use App\AuditLog;
use App\Flash;
use App\UserSession;
use App\ApiToken;

class UserController extends Controller
{
    private const LIST_BASE = '/admin/users';
    private const LIST_MODULE = 'users';

    public function __construct()
    {
        $this->requireAuth();
    }

    private function fetchUsers(): array
    {
        $db = Database::getInstance();
        return $db->query('
            SELECT u.*, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.id
        ')->fetchAll(\PDO::FETCH_OBJ);
    }

    public function index(): void
    {
        $this->requireCapability('view_users');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $rows = $this->fetchUsers();
        $rows = ListHelper::search($rows, $search, $columns, self::LIST_MODULE);
        $rows = ListHelper::sort($rows, $sort ?: 'username', $order, $columns, self::LIST_MODULE);
        $pagination = ListHelper::paginate($rows, $page, $perPage);

        $this->view('users/index', [
            'users' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort ?: 'username',
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::LIST_MODULE),
        ]);
    }

    public function export(): void
    {
        $this->requireCapability('export_users');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $rows = ListHelper::sort(
            ListHelper::search($this->fetchUsers(), trim($_GET['q'] ?? ''), $columns, self::LIST_MODULE),
            $_GET['sort'] ?? 'username',
            in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc',
            $columns,
            self::LIST_MODULE
        );
        $exportCols = ListConfig::getExportColumns(self::LIST_MODULE);
        CsvExporter::stream('users', array_column($exportCols, 'label'), $rows, array_column($exportCols, 'key'));
    }

    public function create(): void
    {
        $this->requireCapability('add_users');
        $roles = Database::getInstance()->query('SELECT * FROM roles')->fetchAll(\PDO::FETCH_OBJ);
        $this->view('users/form', ['user' => null, 'roles' => $roles]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_users');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 0);

        if ($username === '' || !$roleId) {
            $this->redirect(AdminPath::url('users/create') . '?error=1');
            return;
        }
        if ($password !== '') {
            $policyError = \App\PasswordPolicy::validate($password);
            if ($policyError !== null) {
                $_SESSION['user_password_error'] = $policyError;
                $this->redirect(AdminPath::url('users/create') . '?error=policy');
                return;
            }
        }
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $db = Database::getInstance();
        $db->prepare('INSERT INTO users (username, email, display_name, password_hash, role_id) VALUES (?, ?, ?, ?, ?)')
            ->execute([$username, $email ?: null, $displayName ?: null, $passwordHash, $roleId]);
        $userId = (int) $db->lastInsertId();
        if ($password !== '') {
            \App\PasswordPolicy::recordPasswordChange($userId, $passwordHash);
        }
        AuditLog::record('user', $userId, 'created');
        $this->redirect(AdminPath::url('users/view/' . $userId));
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_users');
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$user) {
            $this->redirect(AdminPath::url('users'));
            return;
        }
        $this->view('users/view', ['user' => $user]);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_users');
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$user) {
            $this->redirect(AdminPath::url('users'));
            return;
        }
        $roles = $db->query('SELECT * FROM roles')->fetchAll(\PDO::FETCH_OBJ);
        $this->view('users/form', ['user' => $user, 'roles' => $roles]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_users');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $db = Database::getInstance();
        if (!empty($password)) {
            $policyError = \App\PasswordPolicy::validateForUser($password, $id);
            if ($policyError !== null) {
                $_SESSION['user_password_error'] = $policyError;
                $this->redirect(AdminPath::url('users/edit/' . $id) . '?error=policy');
                return;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET username = ?, email = ?, display_name = ?, password_hash = ?, role_id = ?, password_changed_at = NOW() WHERE id = ?')
                ->execute([$username, $email ?: null, $displayName ?: null, $hash, $roleId, $id]);
            \App\PasswordPolicy::recordPasswordChange($id, $hash);
        } else {
            $db->prepare('UPDATE users SET username = ?, email = ?, display_name = ?, role_id = ? WHERE id = ?')
                ->execute([$username, $email ?: null, $displayName ?: null, $roleId, $id]);
        }
        AuditLog::record('user', $id, 'updated');
        $this->redirect(AdminPath::url('users/view/' . $id));
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_users');
        if ($id === Auth::id()) {
            $this->redirect(AdminPath::url('users') . '?error=self');
            return;
        }
        Database::getInstance()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $this->redirect(AdminPath::url('users'));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('users');
    }
}
