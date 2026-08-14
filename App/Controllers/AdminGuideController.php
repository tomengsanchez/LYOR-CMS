<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;

class AdminGuideController extends Controller
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
        $this->view('admin/guide');
    }
}

