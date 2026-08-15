<?php
namespace App\Controllers;

use App\AdminPath;
use App\Flash;
use App\Models\Page;
use App\Models\Post;
use Core\Controller;

class RevisionController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function restorePage(int $id, int $revisionId): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_pages');
        if (Page::restoreRevision($id, $revisionId)) {
            Flash::success('Page restored from revision.');
        } else {
            Flash::error('Could not restore that revision.');
        }
        $this->redirect(AdminPath::url('pages/view/' . $id));
    }

    public function restorePost(int $id, int $revisionId): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_posts');
        if (Post::restoreRevision($id, $revisionId)) {
            Flash::success('Post restored from revision.');
        } else {
            Flash::error('Could not restore that revision.');
        }
        $this->redirect(AdminPath::url('posts/view/' . $id));
    }
}
