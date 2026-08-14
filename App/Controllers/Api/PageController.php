<?php
namespace App\Controllers\Api;

use App\Models\Page;
use Core\Controller;

class PageController extends Controller
{
    public function listApi(): void
    {
        if (!$this->requireCapabilityApi('view_pages')) {
            return;
        }
        $items = Page::allActive();
        $this->apiSuccess(['items' => $items]);
    }

    public function getApi(int $id): void
    {
        if (!$this->requireCapabilityApi('view_pages')) {
            return;
        }
        $page = Page::find($id);
        if (!$page) {
            $this->apiNotFound('Page not found.');
            return;
        }
        $this->apiSuccess($page);
    }
}
