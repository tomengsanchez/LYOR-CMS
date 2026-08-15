<?php
namespace App\Controllers;

use App\ContentSearch;
use Core\Auth;
use Core\Controller;

class ContentSearchController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        if (!Auth::canAny(['view_pages', 'view_posts', 'view_media'])) {
            $this->redirect(\App\AdminPath::url());
            return;
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $result = ContentSearch::query($q);
        if (!Auth::can('view_pages')) {
            $result['pages'] = [];
        }
        if (!Auth::can('view_posts')) {
            $result['posts'] = [];
        }
        if (!Auth::can('view_media')) {
            $result['media'] = [];
        }
        $this->view('search/index', $result);
    }
}
