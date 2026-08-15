<?php
namespace App\Controllers\Api;

use App\Models\Post;
use App\Models\Tag;
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

    public function createApi(): void
    {
        if (!$this->requireCapabilityApi('add_posts')) {
            return;
        }
        $body = $this->readJsonBody();
        $title = trim((string) ($body['title'] ?? ''));
        if ($title === '') {
            $this->apiValidationError('title is required.');
            return;
        }
        $id = Post::create($this->postPayload($body));
        if ($id <= 0) {
            $this->apiConflict('Could not create post (slug conflict or invalid data).');
            return;
        }
        if (array_key_exists('layout_json', $body) || array_key_exists('layout', $body)) {
            $layoutRaw = $this->layoutRawFromBody($body);
            if ($layoutRaw !== null && !Post::saveLayoutJson($id, $layoutRaw)) {
                $this->apiBadRequest('Post created but layout could not be saved.');
                return;
            }
        }
        $this->apiSuccess(Post::find($id), 201);
    }

    public function updateApi(int $id): void
    {
        if (!$this->requireCapabilityApi('edit_posts')) {
            return;
        }
        $existing = Post::find($id);
        if (!$existing) {
            $this->apiNotFound('Post not found.');
            return;
        }
        $body = $this->readJsonBody();
        $merged = $this->postPayload($body, $existing);
        if (trim((string) ($merged['title'] ?? '')) === '') {
            $this->apiValidationError('title is required.');
            return;
        }
        if (!Post::update($id, $merged)) {
            $this->apiConflict('Could not update post (slug conflict or invalid data).');
            return;
        }
        if (array_key_exists('layout_json', $body) || array_key_exists('layout', $body)) {
            $layoutRaw = $this->layoutRawFromBody($body);
            if ($layoutRaw !== null && !Post::saveLayoutJson($id, $layoutRaw)) {
                $this->apiBadRequest('Post updated but layout could not be saved.');
                return;
            }
        }
        $this->apiSuccess(Post::find($id));
    }

    public function deleteApi(int $id): void
    {
        if (!$this->requireCapabilityApi('delete_posts')) {
            return;
        }
        if (!Post::find($id)) {
            $this->apiNotFound('Post not found.');
            return;
        }
        if (!Post::softDelete($id)) {
            $this->apiBadRequest('Could not delete post.');
            return;
        }
        $this->apiSuccess(['deleted' => true]);
    }

    /** @return array<string, mixed> */
    private function readJsonBody(): array
    {
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function postPayload(array $body, ?object $existing = null): array
    {
        $get = static function (string $key, $default = null) use ($body, $existing) {
            if (array_key_exists($key, $body)) {
                return $body[$key];
            }
            if ($existing !== null && isset($existing->$key)) {
                return $existing->$key;
            }
            return $default;
        };
        $tags = array_key_exists('tags', $body)
            ? $body['tags']
            : ($existing !== null ? Tag::namesForPost((int) $existing->id) : '');
        return [
            'title' => (string) $get('title', ''),
            'slug' => (string) $get('slug', $existing->slug ?? ''),
            'excerpt' => (string) $get('excerpt', ''),
            'body' => (string) $get('body', ''),
            'blocks_json' => $get('blocks_json'),
            'category_id' => $get('category_id'),
            'featured_image_id' => $get('featured_image_id'),
            'meta_title' => (string) $get('meta_title', ''),
            'meta_description' => (string) $get('meta_description', ''),
            'llm_summary' => (string) $get('llm_summary', ''),
            'citation_snippet' => (string) $get('citation_snippet', ''),
            'faq_json' => $get('faq_json'),
            'robots_noindex' => !empty($get('robots_noindex', 0)),
            'content_layout' => $get('content_layout'),
            'status' => (string) $get('status', 'draft'),
            'tags' => is_string($tags) ? $tags : (is_array($tags) ? implode(',', $tags) : ''),
        ];
    }

    /** @param array<string, mixed> $body */
    private function layoutRawFromBody(array $body): ?string
    {
        if (array_key_exists('layout', $body) && is_array($body['layout'])) {
            return json_encode($body['layout'], JSON_UNESCAPED_UNICODE);
        }
        if (array_key_exists('layout_json', $body)) {
            $v = $body['layout_json'];
            if ($v === null || $v === '') {
                return null;
            }
            return is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        return null;
    }
}
