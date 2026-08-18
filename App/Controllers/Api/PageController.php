<?php
namespace App\Controllers\Api;

use App\ContentPassword;
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
        $this->apiSuccess(['items' => ContentPassword::withoutHashList($items)]);
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
        $this->apiSuccess(ContentPassword::withoutHash($page));
    }

    public function createApi(): void
    {
        if (!$this->requireCapabilityApi('add_pages')) {
            return;
        }
        $body = $this->readJsonBody();
        $title = trim((string) ($body['title'] ?? ''));
        if ($title === '') {
            $this->apiValidationError('title is required.');
            return;
        }
        $payload = $this->pagePayload($body);
        $pwError = ContentPassword::writeError($payload);
        if ($pwError !== null) {
            $this->apiValidationError($pwError);
            return;
        }
        $id = Page::create($payload);
        if ($id <= 0) {
            $this->apiConflict('Could not create page (slug conflict or invalid data).');
            return;
        }
        if (array_key_exists('layout_json', $body) || array_key_exists('layout', $body)) {
            $layoutRaw = $this->layoutRawFromBody($body);
            if ($layoutRaw !== null && !Page::saveLayoutJson($id, $layoutRaw)) {
                $this->apiBadRequest('Page created but layout could not be saved.');
                return;
            }
        }
        $page = Page::find($id);
        $this->apiSuccess(ContentPassword::withoutHash($page), 201);
    }

    public function updateApi(int $id): void
    {
        if (!$this->requireCapabilityApi('edit_pages')) {
            return;
        }
        $existing = Page::find($id);
        if (!$existing) {
            $this->apiNotFound('Page not found.');
            return;
        }
        $body = $this->readJsonBody();
        $merged = $this->pagePayload($body, $existing);
        $pwError = ContentPassword::writeError($merged);
        if ($pwError !== null) {
            $this->apiValidationError($pwError);
            return;
        }
        if (trim((string) ($merged['title'] ?? '')) === '') {
            $this->apiValidationError('title is required.');
            return;
        }
        if (!Page::update($id, $merged)) {
            $this->apiConflict('Could not update page (slug conflict or invalid data).');
            return;
        }
        if (array_key_exists('layout_json', $body) || array_key_exists('layout', $body)) {
            $layoutRaw = $this->layoutRawFromBody($body);
            if ($layoutRaw !== null && !Page::saveLayoutJson($id, $layoutRaw)) {
                $this->apiBadRequest('Page updated but layout could not be saved.');
                return;
            }
        }
        $this->apiSuccess(ContentPassword::withoutHash(Page::find($id)));
    }

    public function deleteApi(int $id): void
    {
        if (!$this->requireCapabilityApi('delete_pages')) {
            return;
        }
        if (!Page::find($id)) {
            $this->apiNotFound('Page not found.');
            return;
        }
        if (!Page::softDelete($id)) {
            $this->apiBadRequest('Could not delete page.');
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
    private function pagePayload(array $body, ?object $existing = null): array
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
        $payload = [
            'title' => (string) $get('title', ''),
            'slug' => (string) $get('slug', $existing->slug ?? ''),
            'body' => (string) $get('body', ''),
            'blocks_json' => $get('blocks_json'),
            'status' => (string) $get('status', 'draft'),
            'meta_title' => (string) $get('meta_title', ''),
            'meta_description' => (string) $get('meta_description', ''),
            'featured_image_id' => $get('featured_image_id'),
            'llm_summary' => (string) $get('llm_summary', ''),
            'citation_snippet' => (string) $get('citation_snippet', ''),
            'faq_json' => $get('faq_json'),
            'robots_noindex' => !empty($get('robots_noindex', 0)),
            'content_layout' => $get('content_layout'),
            'parent_id' => $get('parent_id'),
        ];
        if (array_key_exists('content_password', $body)) {
            $payload['content_password'] = (string) $body['content_password'];
        }
        if (!empty($body['remove_content_password'])) {
            $payload['remove_content_password'] = true;
        }
        return $payload;
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
