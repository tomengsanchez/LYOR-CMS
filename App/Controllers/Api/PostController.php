<?php
namespace App\Controllers\Api;

use App\Models\Post;
use Core\Controller;

class PostController extends Controller
{
    public function listApi(): void
    {
        if (!$this->requireCapabilityApi('view_posts')) {
            return;
        }
        $items = Post::allActive();
        $this->apiSuccess(['items' => $items]);
    }

    public function getApi(int $id): void
    {
        if (!$this->requireCapabilityApi('view_posts')) {
            return;
        }
        $post = Post::find($id);
        if (!$post) {
            $this->apiNotFound('Post not found.');
            return;
        }
        $this->apiSuccess($post);
    }
}
