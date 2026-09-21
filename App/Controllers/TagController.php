<?php
namespace App\Controllers;

use App\AdminPath;
use App\Models\Tag;
use Core\Controller;

class TagController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('manage_categories');
        $this->view('tags/index', ['tags' => Tag::all()]);
    }

    public function create(): void
    {
        $this->requireCapability('manage_categories');
        $this->view('tags/form', [
            'tag' => (object) ['id' => 0, 'name' => '', 'slug' => ''],
            'isCreate' => true,
            'formError' => $_SESSION['tag_form_error'] ?? '',
        ]);
        unset($_SESSION['tag_form_error']);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_categories');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['tag_form_error'] = 'Name is required.';
            $this->redirect(AdminPath::url('tags/create'));
            return;
        }
        if (Tag::findByName($name)) {
            $_SESSION['tag_form_error'] = 'A tag with this name already exists.';
            $this->redirect(AdminPath::url('tags/create'));
            return;
        }
        Tag::create(['name' => $name, 'slug' => trim($_POST['slug'] ?? '')]);
        $this->redirect(AdminPath::url('tags'));
    }

    public function edit(int $id): void
    {
        $this->requireCapability('manage_categories');
        $tag = Tag::find($id);
        if (!$tag) {
            $this->redirect(AdminPath::url('tags'));
            return;
        }
        $this->view('tags/form', [
            'tag' => $tag,
            'isCreate' => false,
            'formError' => $_SESSION['tag_form_error'] ?? '',
        ]);
        unset($_SESSION['tag_form_error']);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_categories');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['tag_form_error'] = 'Name is required.';
            $this->redirect(AdminPath::url('tags/edit/' . $id));
            return;
        }
        $other = Tag::findByName($name);
        if ($other && (int) $other->id !== $id) {
            $_SESSION['tag_form_error'] = 'A tag with this name already exists.';
            $this->redirect(AdminPath::url('tags/edit/' . $id));
            return;
        }
        if (!Tag::update($id, ['name' => $name, 'slug' => trim($_POST['slug'] ?? '')])) {
            $this->redirect(AdminPath::url('tags'));
            return;
        }
        $this->redirect(AdminPath::url('tags'));
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_categories');
        Tag::delete($id);
        $this->redirect(AdminPath::url('tags'));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('tags');
    }
}
