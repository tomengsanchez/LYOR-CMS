<?php
namespace App\Controllers;

use App\AdminPath;
use App\Models\Widget;
use Core\Auth;
use Core\Controller;

class WidgetController extends Controller
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
        $area = $_GET['area'] ?? Widget::AREA_SIDEBAR;
        if (!array_key_exists($area, Widget::areas())) {
            $area = Widget::AREA_SIDEBAR;
        }
        $this->view('widgets/index', [
            'area' => $area,
            'areas' => Widget::areas(),
            'widgets' => Widget::forArea($area, false),
            'widgetTypes' => Widget::types(),
            'saved' => !empty($_SESSION['widgets_saved']),
        ]);
        unset($_SESSION['widgets_saved']);
    }

    public function save(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }

        $area = (string) ($_POST['area'] ?? Widget::AREA_SIDEBAR);
        $types = $_POST['widget_type'] ?? [];
        $titles = $_POST['widget_title'] ?? [];
        $configs = $_POST['widget_config'] ?? [];
        $enabled = $_POST['widget_enabled'] ?? [];

        $rows = [];
        if (is_array($types)) {
            foreach ($types as $i => $type) {
                $config = [];
                if (isset($configs[$i]) && is_string($configs[$i])) {
                    $decoded = json_decode($configs[$i], true);
                    if (is_array($decoded)) {
                        $config = $decoded;
                    }
                }
                $rows[] = [
                    'widget_type' => $type,
                    'title' => $titles[$i] ?? '',
                    'config' => $config,
                    'is_enabled' => !empty($enabled[$i]),
                ];
            }
        }
        Widget::saveAreaWidgets($area, $rows);
        $_SESSION['widgets_saved'] = true;
        $this->redirect(AdminPath::url('widgets?area=' . urlencode($area)));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('widgets');
    }
}
