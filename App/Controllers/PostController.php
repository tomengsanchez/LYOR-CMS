<?php
namespace App\Controllers;

use App\AdminPath;

use App\Flash;
use App\ListConfig;
use App\ListHelper;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use Core\Controller;

class PostController extends Controller
{
    private const LIST_BASE = '/admin/posts';
    private const LIST_MODULE = 'posts';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_posts');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $rows = Post::allActive();
        $rows = ListHelper::search($rows, $search, $columns, self::LIST_MODULE);
        $rows = ListHelper::sort($rows, $sort ?: ($columns[0] ?? 'title'), $order, $columns, self::LIST_MODULE);
        $pagination = ListHelper::paginate($rows, $page, $perPage);

        $this->view('posts/index', [
            'posts' => $pagination['items'],
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
        $this->requireCapability('view_posts');
        $post = Post::find($id);
        if (!$post) {
            $this->redirect(AdminPath::url('posts'));
            return;
        }
        $this->view('posts/view', [
            'post' => $post,
            'revisions' => \App\ContentRevision::listFor('post', $id),
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_posts');
        $this->view('posts/form', [
            'post' => (object) [
                'id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '',
                'status' => 'draft', 'category_id' => null, 'featured_image_id' => null, 'content_layout' => null,
                'blocks_json' => null, 'meta_title' => '', 'meta_description' => '', 'llm_summary' => '', 'citation_snippet' => '', 'faq_json' => '', 'robots_noindex' => 0,
            ],
            'categories' => Category::all(),
            'mediaImages' => Media::listImages(),
            'allTags' => \App\Models\Tag::all(),
            'isCreate' => true,
            'formError' => $_SESSION['post_form_error'] ?? '',
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_posts');
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $_SESSION['post_form_error'] = 'Title is required.';
            $this->redirect(AdminPath::url('posts/create'));
            return;
        }
        $id = Post::create([
            'title' => $title,
            'slug' => trim($_POST['slug'] ?? ''),
            'excerpt' => $_POST['excerpt'] ?? '',
            'body' => $_POST['body'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'category_id' => $_POST['category_id'] ?? null,
            'featured_image_id' => $_POST['featured_image_id'] ?? null,
            'content_layout' => $_POST['content_layout'] ?? '',
            'tags' => $_POST['tags'] ?? '',
            'blocks_json' => $_POST['blocks_json'] ?? null,
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'llm_summary' => $_POST['llm_summary'] ?? '',
            'citation_snippet' => $_POST['citation_snippet'] ?? '',
            'faq_json' => $_POST['faq_json'] ?? null,
            'robots_noindex' => !empty($_POST['robots_noindex']),
        ]);
        if ($id <= 0) {
            $_SESSION['post_form_error'] = 'Could not save post. Slug may conflict with an existing page.';
            $this->redirect(AdminPath::url('posts/create'));
            return;
        }
        $this->redirect(AdminPath::url('posts/view/' . $id));
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_posts');
        $post = Post::find($id);
        if (!$post) {
            $this->redirect(AdminPath::url('posts'));
            return;
        }
        $this->view('posts/form', [
            'post' => $post,
            'categories' => Category::all(),
            'mediaImages' => Media::listImages(),
            'allTags' => \App\Models\Tag::all(),
            'isCreate' => false,
            'formError' => $_SESSION['post_form_error'] ?? '',
        ]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_posts');
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $_SESSION['post_form_error'] = 'Title is required.';
            $this->redirect(AdminPath::url('posts/edit/' . $id));
            return;
        }
        if (!Post::update($id, [
            'title' => $title,
            'slug' => trim($_POST['slug'] ?? ''),
            'excerpt' => $_POST['excerpt'] ?? '',
            'body' => $_POST['body'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'category_id' => $_POST['category_id'] ?? null,
            'featured_image_id' => $_POST['featured_image_id'] ?? null,
            'content_layout' => $_POST['content_layout'] ?? '',
            'tags' => $_POST['tags'] ?? '',
            'blocks_json' => $_POST['blocks_json'] ?? null,
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'llm_summary' => $_POST['llm_summary'] ?? '',
            'citation_snippet' => $_POST['citation_snippet'] ?? '',
            'faq_json' => $_POST['faq_json'] ?? null,
            'robots_noindex' => !empty($_POST['robots_noindex']),
        ])) {
            $_SESSION['post_form_error'] = 'Could not update post. Slug may conflict with an existing page.';
            $this->redirect(AdminPath::url('posts/edit/' . $id));
            return;
        }
        $this->redirect(AdminPath::url('posts/view/' . $id));
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_posts');
        Post::softDelete($id);
        $this->redirect(AdminPath::url('posts'));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('posts');
    }
}
