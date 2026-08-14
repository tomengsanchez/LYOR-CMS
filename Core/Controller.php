<?php
namespace Core;

use App\AdminPath;

use App\ApiErrorCode;
use App\ApiErrorRegistry;
use App\ApiIdempotency;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . "/App/Views/{$view}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$view}");
        }
    }

    protected function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }

    protected function json($data): void
    {
        header('Content-Type: application/json');
        $payload = $data;
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        // Wrap when path contains /api/ (root install or subdirectory, e.g. /app/public/api/...)
        if (is_string($requestPath) && strpos($requestPath, '/api/') !== false) {
            $payload = $this->normalizeApiEnvelope($data);
        }
        echo json_encode($payload);
        exit;
    }

    /**
     * Normalize API responses to a consistent envelope:
     * { success: bool, data: mixed|null, error: { code, message, details? }|null }
     */
    private function normalizeApiEnvelope($data): array
    {
        if (is_array($data) && array_key_exists('success', $data)) {
            // Already in envelope-like form.
            $hasData = array_key_exists('data', $data);
            $hasError = array_key_exists('error', $data);
            return [
                'success' => (bool) $data['success'],
                'data' => $hasData ? $data['data'] : null,
                'error' => $hasError ? $data['error'] : null,
            ];
        }

        if (is_array($data) && array_key_exists('error', $data)) {
            $errorValue = $data['error'];
            $message = is_string($errorValue)
                ? $errorValue
                : (is_array($errorValue) ? (string) ($errorValue['message'] ?? 'Request failed') : 'Request failed');
            $code = ApiErrorCode::BAD_REQUEST;
            $details = null;
            if (is_string($errorValue)) {
                $code = ApiErrorRegistry::codeFromLegacyErrorString($errorValue);
            } elseif (is_array($errorValue)) {
                $code = (string) ($errorValue['code'] ?? $code);
                if (array_key_exists('details', $errorValue)) {
                    $details = $errorValue['details'];
                }
            }
            if (isset($data['message']) && is_string($data['message']) && trim($data['message']) !== '') {
                $message = $data['message'];
            }
            if ($code === ApiErrorCode::FORBIDDEN
                && stripos($message, 'project not allowed') !== false) {
                $code = ApiErrorCode::FORBIDDEN_PROJECT;
            }
            $error = ['code' => $code, 'message' => $message];
            if ($details !== null) {
                $error['details'] = $details;
            }
            return [
                'success' => false,
                'data' => null,
                'error' => $error,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'error' => null,
        ];
    }

    protected function auth(): ?object
    {
        return Auth::user();
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect(AdminPath::url('login'));
        }
    }

    /** API variant: return 401 JSON instead of redirect. Use for REST endpoints. */
    protected function requireAuthApi(): bool
    {
        if (!Auth::check()) {
            $this->apiUnauthorized();
            return false;
        }
        return true;
    }

    /** @return bool False after emitting 403 if capability missing. */
    protected function requireCapabilityApi(string $capability): bool
    {
        if (!$this->requireAuthApi()) {
            return false;
        }
        if (!Auth::can($capability)) {
            $this->apiForbidden();
            return false;
        }
        return true;
    }

    /** @param list<string> $capabilities @return bool False after emitting 403 if none match. */
    protected function requireCapabilityAnyApi(array $capabilities): bool
    {
        if (!$this->requireAuthApi()) {
            return false;
        }
        if (!Auth::canAny($capabilities)) {
            $this->apiForbidden();
            return false;
        }
        return true;
    }

    protected function apiSuccess($data, int $status = 200): void
    {
        http_response_code($status);
        $this->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ]);
    }

    protected function apiError(string $code, string $message, int $status = 400, ?array $details = null): void
    {
        http_response_code($status);
        $error = ['code' => $code, 'message' => $message];
        if ($details !== null && $details !== []) {
            $error['details'] = $details;
        }
        $this->json([
            'success' => false,
            'data' => null,
            'error' => $error,
        ]);
    }

    protected function apiUnauthorized(string $message = 'Authentication required.'): void
    {
        $this->apiError(ApiErrorCode::UNAUTHORIZED, $message, 401);
    }

    protected function apiForbidden(string $message = 'You do not have permission for this action.'): void
    {
        $this->apiError(ApiErrorCode::FORBIDDEN, $message, 403);
    }

    protected function apiForbiddenProject(string $message = 'Project not allowed for the current user.'): void
    {
        $this->apiError(ApiErrorCode::FORBIDDEN_PROJECT, $message, 403);
    }

    protected function apiNotFound(string $message = 'Resource not found.'): void
    {
        $this->apiError(ApiErrorCode::NOT_FOUND, $message, 404);
    }

    protected function apiBadRequest(string $message): void
    {
        $this->apiError(ApiErrorCode::BAD_REQUEST, $message, 400);
    }

    protected function apiValidationError(string $message, ?array $details = null): void
    {
        $this->apiError(ApiErrorCode::VALIDATION_ERROR, $message, 400, $details);
    }

    protected function apiConflict(string $message = 'This record was updated by someone else. Reload and try again.'): void
    {
        $this->apiError(ApiErrorCode::CONFLICT, $message, 409);
    }

    /** Replay a prior idempotent API response when Idempotency-Key is supplied. */
    protected function idempotencyReplay(string $scope): bool
    {
        $key = ApiIdempotency::readKeyFromRequest();
        $userId = (int) (Auth::id() ?? 0);
        if ($key === null || $userId <= 0) {
            return false;
        }
        $replay = ApiIdempotency::replay($userId, $scope, $key);
        if ($replay === null) {
            return false;
        }
        http_response_code($replay['http_status']);
        header('Content-Type: application/json');
        echo json_encode($replay['envelope']);
        exit;
    }

    /** @param array<string, mixed> $data */
    protected function idempotencyRemember(string $scope, int $status, array $data): void
    {
        $key = ApiIdempotency::readKeyFromRequest();
        $userId = (int) (Auth::id() ?? 0);
        if ($key === null || $userId <= 0) {
            return;
        }
        ApiIdempotency::store($userId, $scope, $key, $status, [
            'success' => true,
            'data' => $data,
            'error' => null,
        ]);
    }

    protected function apiRateLimited(string $message = 'Too many requests. Try again later.'): void
    {
        $this->apiError(ApiErrorCode::RATE_LIMITED, $message, 429);
    }

    protected function apiInternalError(string $message = 'An unexpected server error occurred.'): void
    {
        $this->apiError(ApiErrorCode::INTERNAL_ERROR, $message, 500);
    }

    protected function requireCapability(string $capability): void
    {
        $this->requireAuth();
        if (!Auth::can($capability)) {
            $this->redirect(AdminPath::url());
        }
    }

    /** Validate CSRF for POST requests. Call at the start of any action that accepts POST. Redirects with error if invalid. */
    protected function validateCsrf(): void
    {
        if (!\Core\Csrf::validate()) {
            $this->redirect($this->csrfRedirectUrl(), 403);
        }
    }

    /** Override in subclass to set redirect target when CSRF validation fails (default /). */
    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url() . '?error=csrf';
    }
}
