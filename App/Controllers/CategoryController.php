<?php
namespace App\Controllers;

use App\AdminPath;

use App\ListConfig;
use App\ListHelper;
use App\Models\Category;
use Core\Controller;

class CategoryController extends Controller
{
    private const LIST_BASE = '/admin/categories';
    private const LIST_MODULE = 'categories';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_categories');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'asc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $rows = Category::all();
        $rows = ListHelper::search($rows, $search, $columns, self::LIST_MODULE);
        $rows = ListHelper::sort($rows, $sort ?: 'name', $order, $columns, self::LIST_MODULE);
        $pagination = ListHelper::paginate($rows, $page, $perPage);

        $this->view('categories/index', [
            'categories' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort ?: 'name',
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::LIST_MODULE),
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('manage_categories');
        $this->view('categories/form', [
            'category' => (object) ['id' => 0, 'name' => '', 'slug' => '', 'description' => ''],
            'isCreate' => true,
            'formError' => $_SESSION['category_form_error'] ?? '',
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_categories');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['category_form_error'] = 'Name is required.';
            $this->redirect(AdminPath::url('categories/create'));
            return;
        }
        Category::create([
            'name' => $name,
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => $_POST['description'] ?? '',
        ]);
        $this->redirect(AdminPath::url('categories'));
    }

    /** Quick-create category from the post editor (JSON). */
    public function quickStore(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!\Core\Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'Invalid security token. Refresh and try again.']]);
            return;
        }
        if (!\Core\Auth::can('manage_categories') && !\Core\Auth::canAny(['add_posts', 'edit_posts'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['message' => 'You do not have permission to create categories.']]);
            return;
        }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ['message' => 'Category name is required.']]);
            return;
        }
        $id = Category::create([
            'name' => $name,
            'slug' => '',
            'description' => '',
        ]);
        $cat = Category::find($id);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'name' => (string) ($cat->name ?? $name),
                'slug' => (string) ($cat->slug ?? ''),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('manage_categories');
        $category = Category::find($id);
        if (!$category) {
            $this->redirect(AdminPath::url('categories'));
            return;
        }
        $this->view('categories/form', [
            'category' => $category,
            'isCreate' => false,
            'formError' => $_SESSION['category_form_error'] ?? '',
        ]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_categories');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['category_form_error'] = 'Name is required.';
            $this->redirect(AdminPath::url('categories/edit/' . $id));
            return;
        }
        Category::update($id, [
            'name' => $name,
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => $_POST['description'] ?? '',
        ]);
        $this->redirect(AdminPath::url('categories'));
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_categories');
        Category::delete($id);
        $this->redirect(AdminPath::url('categories'));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('categories');
    }
}
