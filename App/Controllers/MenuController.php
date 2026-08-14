<?php
namespace App\Controllers;

use App\AdminPath;
use App\Models\Category;
use App\Models\NavMenu;
use App\Models\Page;
use App\Models\Post;
use Core\Auth;
use Core\Controller;

class MenuController extends Controller
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
        $menu = NavMenu::findByLocation(NavMenu::LOCATION_PRIMARY);
        $items = $menu ? NavMenu::allItemsForMenu((int) $menu->id) : [];
        $this->view('menus/index', [
            'menu' => $menu,
            'items' => $items,
            'itemTypes' => NavMenu::itemTypes(),
            'pages' => Page::publishedOptions(),
            'posts' => Post::publishedList(100, 0),
            'categories' => Category::all(),
            'saved' => !empty($_SESSION['menu_saved']),
        ]);
        unset($_SESSION['menu_saved']);
    }

    public function save(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }

        $labels = $_POST['item_label'] ?? [];
        $types = $_POST['item_type'] ?? [];
        $objectIds = $_POST['item_object_id'] ?? [];
        $urls = $_POST['item_custom_url'] ?? [];
        $newTabs = $_POST['item_new_tab'] ?? [];

        $rows = [];
        if (is_array($labels)) {
            foreach ($labels as $i => $label) {
                $rows[] = [
                    'label' => $label,
                    'item_type' => $types[$i] ?? NavMenu::TYPE_CUSTOM,
                    'object_id' => $objectIds[$i] ?? null,
                    'custom_url' => $urls[$i] ?? '',
                    'open_in_new_tab' => !empty($newTabs[$i]),
                ];
            }
        }
        NavMenu::savePrimaryItems($rows);
        $_SESSION['menu_saved'] = true;
        $this->redirect(AdminPath::url('menus'));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('menus');
    }
}
