<?php
namespace App\Controllers\Api;

use App\Models\Media;
use Core\Controller;

class MediaController extends Controller
{
    public function listApi(): void
    {
        if (!$this->requireCapabilityApi('view_media')) {
            return;
        }
        $items = Media::allActive();
        $this->apiSuccess(['items' => $items]);
    }
}
