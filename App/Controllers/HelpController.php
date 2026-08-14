<?php
namespace App\Controllers;

use Core\Controller;

require_once dirname(__DIR__) . '/Views/help/helpers.php';

class HelpController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $from = isset($_GET['from']) ? (string)$_GET['from'] : '';
        $module = help_resolve_content_key($from);
        $this->view('help/index', [
            'module' => $module,
            'from' => $from,
        ]);
    }

    /** HTML fragment for AJAX help modal (no layout). */
    public function fragment(): void
    {
        $from = isset($_GET['from']) ? (string)$_GET['from'] : '';
        $module = help_resolve_content_key($from);
        $this->view('help/content', [
            'module' => $module,
            'from' => $from,
        ]);
    }
}

