<?php
namespace App\Controllers;

use App\AdminPath;
use App\ListConfig;
use App\ListHelper;
use App\Models\Media;
use App\Models\Page;
use Core\Controller;

class PageController extends Controller
{
    private const LIST_BASE = '/admin/pages';
    private const LIST_MODULE = 'pages';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_pages');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $rows = Page::allActive();
        $rows = ListHelper::search($rows, $search, $columns, self::LIST_MODULE);
        $rows = ListHelper::sort($rows, $sort ?: ($columns[0] ?? 'title'), $order, $columns, self::LIST_MODULE);
        $pagination = ListHelper::paginate($rows, $page, $perPage);

        $this->view('pages/index', [
            'pages' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort ?: ($columns[0] ?? ''),
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::LIST_MODULE),
        ]);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_pages');
        $page = Page::find($id);
        if (!$page) {
            $this->redirect(AdminPath::url('pages'));
            return;
        }
        $this->view('pages/view', ['page' => $page]);
    }

    public function create(): void
    {
        $this->requireCapability('add_pages');
        $this->view('pages/form', [
            'page' => (object) [
                'id' => 0, 'title' => '', 'slug' => '', 'body' => '', 'status' => 'draft',
                'meta_title' => '', 'meta_description' => '', 'featured_image_id' => null,
                'llm_summary' => '', 'robots_noindex' => 0, 'content_layout' => null, 'parent_id' => null,
                'blocks_json' => null,
            ],
            'mediaImages' => Media::listImages(),
            'isCreate' => true,
            'formError' => $_SESSION['page_form_error'] ?? '',
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_pages');
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $_SESSION['page_form_error'] = 'Title is required.';
            $this->redirect(AdminPath::url('pages/create'));
            return;
        }
        $id = Page::create($this->pagePayloadFromPost());
        if ($id <= 0) {
            $_SESSION['page_form_error'] = 'Could not save page. Slug may conflict with an existing post.';
            $this->redirect(AdminPath::url('pages/create'));
            return;
        }
        $this->redirect(AdminPath::url('pages/view/' . $id));
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_pages');
        $page = Page::find($id);
        if (!$page) {
            $this->redirect(AdminPath::url('pages'));
            return;
        }
        $this->view('pages/form', [
            'page' => $page,
            'mediaImages' => Media::listImages(),
            'isCreate' => false,
            'formError' => $_SESSION['page_form_error'] ?? '',
        ]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_pages');
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $_SESSION['page_form_error'] = 'Title is required.';
            $this->redirect(AdminPath::url('pages/edit/' . $id));
            return;
        }
        if (!Page::update($id, $this->pagePayloadFromPost())) {
            $_SESSION['page_form_error'] = 'Could not update page. Slug may conflict with an existing post.';
            $this->redirect(AdminPath::url('pages/edit/' . $id));
            return;
        }
        $this->redirect(AdminPath::url('pages/view/' . $id));
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_pages');
        Page::softDelete($id);
        $this->redirect(AdminPath::url('pages'));
    }

    /** @return array<string, mixed> */
    private function pagePayloadFromPost(): array
    {
        return [
            'title' => $_POST['title'] ?? '',
            'slug' => trim($_POST['slug'] ?? ''),
            'body' => $_POST['body'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'featured_image_id' => $_POST['featured_image_id'] ?? null,
            'llm_summary' => $_POST['llm_summary'] ?? '',
            'robots_noindex' => !empty($_POST['robots_noindex']),
            'content_layout' => $_POST['content_layout'] ?? '',
            'parent_id' => $_POST['parent_id'] ?? null,
            'blocks_json' => $_POST['blocks_json'] ?? null,
        ];
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('pages');
    }
}
