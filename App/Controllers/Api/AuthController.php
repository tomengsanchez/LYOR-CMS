<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use Core\Database;
use Core\Logger;
use Core\Mailer;
use Core\LoginThrottle;
use App\ApiErrorCode;
use App\ApiToken;
use App\ApiTwoFactorChallenge;
use App\Models\AppSettings;

/**
 * REST API authentication.
 *
 * Flow when email 2FA is OFF:
 *   POST /api/auth/login → envelope.data { token, expires_at, user }
 *
 * Flow when email 2FA is ON:
 *   POST /api/auth/login            → envelope.data { pending_2fa: true, challenge_id, expires_at, email_hint }
 *   POST /api/auth/2fa/verify       → envelope.data { token, expires_at, user }
 *   POST /api/auth/2fa/resend       → envelope.data { challenge_id, expires_at, email_hint, resent: true }
 *
 * Other:
 *   GET  /api/auth/me     - Bearer token → current user + capabilities
 *   POST /api/auth/logout - Bearer token → revokes token
 */
class AuthController extends Controller
{
    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        Logger::auth('API login attempt', ['username' => $username ?: '(empty)']);

        if ($username === '' || $password === '') {
            $this->apiBadRequest('username and password are required.');
            return;
        }

        $ip = LoginThrottle::getClientIp();
        if (LoginThrottle::isBlocked($ip)) {
            \App\TrafficLog::recordAuthEvent(
                'login_blocked',
                'API login blocked by throttle for IP ' . $ip,
                null,
                null,
                429
            );
            $this->apiRateLimited();
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, username, password_hash, email, display_name, password_changed_at, created_at FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_OBJ);

            if (!$user || !\App\Models\User::canLogin($user) || !password_verify($password, $user->password_hash)) {
                LoginThrottle::recordFailure($ip);
                Logger::auth('API login failed: invalid credentials', ['username' => $username]);
                \App\TrafficLog::recordAuthEvent(
                    'login_failure',
                    "API failed login attempt as '" . $username . "'",
                    null,
                    $username,
                    401
                );
                $this->apiError(ApiErrorCode::UNAUTHORIZED, 'Invalid credentials.', 401);
                return;
            }

            $security = AppSettings::getSecurityConfig();
            $expiryDays = (int) ($security->password_expiry_days ?? 0);
            if ($expiryDays > 0) {
                $changedAt = $user->password_changed_at ?? $user->created_at ?? null;
                if ($changedAt) {
                    $changedTs = strtotime($changedAt);
                    if ($changedTs !== false) {
                        $expirySeconds = $expiryDays * 86400;
                        if (time() - $changedTs > $expirySeconds) {
                            Logger::auth('API login blocked: password expired', ['user_id' => $user->id, 'expiry_days' => $expiryDays]);
                            $this->apiError(
                                ApiErrorCode::PASSWORD_EXPIRED,
                                'Password has expired. Please contact your administrator.',
                                403
                            );
                            return;
                        }
                    }
                }
            }

            if (!empty($security->enable_email_2fa)) {
                $this->beginTwoFactorChallenge($user, (int) ($security->{'2fa_expiration_minutes'} ?? 15), $ip);
                return;
            }

            $this->issueTokenResponse($user, $ip);
        } catch (\Throwable $e) {
            LoginThrottle::recordFailure($ip ?? LoginThrottle::getClientIp());
            Logger::auth('API login exception', ['error' => $e->getMessage()]);
            $this->apiInternalError('Login failed.');
        }
    }

    /**
     * Step 2 of API 2FA: client submits { challenge_id, code }.
     * On success, issues an API token (same shape as /api/auth/login when 2FA is off).
     */
    public function verify2fa(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $challengeId = trim((string) ($body['challenge_id'] ?? ''));
        $code = trim((string) ($body['code'] ?? ''));

        if ($challengeId === '' || $code === '') {
            $this->apiBadRequest('challenge_id and code are required.');
            return;
        }

        $ip = LoginThrottle::getClientIp();
        if (LoginThrottle::isBlocked($ip)) {
            $this->apiRateLimited();
            return;
        }

        try {
            $result = ApiTwoFactorChallenge::verify($challengeId, $code);
            switch ($result['status']) {
                case ApiTwoFactorChallenge::VERIFY_NOT_FOUND:
                    $this->apiError(
                        ApiErrorCode::TWO_FACTOR_CHALLENGE_NOT_FOUND,
                        'No active verification challenge for the provided id.',
                        404
                    );
                    return;
                case ApiTwoFactorChallenge::VERIFY_EXPIRED:
                    $this->apiError(
                        ApiErrorCode::TWO_FACTOR_CHALLENGE_EXPIRED,
                        'The verification challenge has expired. Restart login.',
                        410
                    );
                    return;
                case ApiTwoFactorChallenge::VERIFY_LOCKED:
                    Logger::auth('API 2FA verify locked', ['challenge_id' => $this->maskChallenge($challengeId)]);
                    $this->apiError(
                        ApiErrorCode::TWO_FACTOR_LOCKED,
                        'Too many incorrect codes. Restart login.',
                        429
                    );
                    return;
                case ApiTwoFactorChallenge::VERIFY_ALREADY_USED:
                    $this->apiError(
                        ApiErrorCode::TWO_FACTOR_CHALLENGE_NOT_FOUND,
                        'This challenge was already used. Restart login.',
                        404
                    );
                    return;
                case ApiTwoFactorChallenge::VERIFY_INVALID_CODE:
                    LoginThrottle::recordFailure($ip);
                    Logger::auth('API 2FA verify invalid code', ['challenge_id' => $this->maskChallenge($challengeId)]);
                    $this->apiError(
                        ApiErrorCode::TWO_FACTOR_INVALID_CODE,
                        'The verification code is incorrect.',
                        401
                    );
                    return;
                case ApiTwoFactorChallenge::VERIFY_OK:
                    $userId = (int) ($result['user_id'] ?? 0);
                    if ($userId <= 0) {
                        $this->apiInternalError('2FA verification failed.');
                        return;
                    }
                    $user = $this->findUserById($userId);
                    if (!$user) {
                        $this->apiError(
                            ApiErrorCode::TWO_FACTOR_CHALLENGE_NOT_FOUND,
                            'Account no longer exists for this challenge.',
                            404
                        );
                        return;
                    }
                    ApiTwoFactorChallenge::consume($challengeId);
                    Logger::auth('API 2FA verify success', ['user_id' => $userId]);
                    $this->issueTokenResponse($user, $ip);
                    return;
            }

            $this->apiInternalError('2FA verification failed.');
        } catch (\Throwable $e) {
            Logger::auth('API 2FA verify exception', ['error' => $e->getMessage()]);
            $this->apiInternalError('2FA verification failed.');
        }
    }

    /**
     * Re-send the OTP for an existing challenge (rotates the code, resets attempts,
     * extends expiry). Returns the same challenge_id so clients keep using it.
     */
    public function resend2fa(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $challengeId = trim((string) ($body['challenge_id'] ?? ''));

        if ($challengeId === '') {
            $this->apiBadRequest('challenge_id is required.');
            return;
        }

        $ip = LoginThrottle::getClientIp();
        if (LoginThrottle::isBlocked($ip)) {
            $this->apiRateLimited();
            return;
        }

        try {
            $security = AppSettings::getSecurityConfig();
            if (empty($security->enable_email_2fa)) {
                $this->apiError(
                    ApiErrorCode::TWO_FACTOR_CHALLENGE_NOT_FOUND,
                    '2FA is not enabled; no challenge can be resent.',
                    404
                );
                return;
            }

            $rotated = ApiTwoFactorChallenge::rotateCode(
                $challengeId,
                (int) ($security->{'2fa_expiration_minutes'} ?? 15)
            );
            if ($rotated === null) {
                $this->apiError(
                    ApiErrorCode::TWO_FACTOR_CHALLENGE_NOT_FOUND,
                    'No active verification challenge for the provided id.',
                    404
                );
                return;
            }

            $userId = $this->lookupUserIdForChallenge($challengeId);
            $user = $userId > 0 ? $this->findUserById($userId) : null;
            $email = $user && isset($user->email) ? trim((string) $user->email) : '';
            if ($email === '') {
                $this->apiError(
                    ApiErrorCode::TWO_FACTOR_NO_EMAIL,
                    '2FA email cannot be resent for this account.',
                    403
                );
                return;
            }

            $sendResult = Mailer::send(
                $email,
                (AppSettings::getBrandingConfig()->app_name ?? 'Simple CMS') . ' Login Code',
                'Your new verification code is: ' . $rotated['code']
                . "\n\nThis code expires at " . $rotated['expires_at'] . '.'
            );
            if (empty($sendResult['success'])) {
                Logger::auth('API 2FA resend email send failed', [
                    'user_id' => $userId,
                    'error' => $sendResult['error'] ?? '',
                ]);
                $this->apiError(
                    ApiErrorCode::TWO_FACTOR_SEND_FAILED,
                    'Failed to send the verification email. Please try again.',
                    502
                );
                return;
            }

            Logger::auth('API 2FA code resent', ['user_id' => $userId]);
            $this->apiSuccess([
                'challenge_id' => $rotated['challenge_id'],
                'expires_at' => $rotated['expires_at'],
                'email_hint' => ApiTwoFactorChallenge::maskEmail($email),
                'resent' => true,
            ]);
        } catch (\Throwable $e) {
            Logger::auth('API 2FA resend exception', ['error' => $e->getMessage()]);
            $this->apiInternalError('Resend failed.');
        }
    }

    public function me(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        $u = Auth::user();
        $this->apiSuccess([
            'id'            => (int) $u->id,
            'username'      => $u->username,
            'display_name'  => $u->display_name ?? $u->username,
            'email'         => $u->email ?? null,
            'role_name'     => $u->role_name ?? null,
            'capabilities'  => Auth::capabilitiesForCurrentUser(),
        ]);
    }

    public function logout(): void
    {
        $bearer = ApiToken::getBearerToken();
        if ($bearer !== null) {
            ApiToken::revoke($bearer);
        } elseif (Auth::check()) {
            Auth::logout();
        }
        $this->apiSuccess(['message' => 'Logged out']);
    }

    /**
     * Begin a 2FA challenge (login step 1 when 2FA is on). Sends an email, returns
     * an envelope.data with pending_2fa flag and challenge_id. Note: this path is a
     * SUCCESS envelope (200), not an error — credentials were valid, just incomplete.
     */
    private function beginTwoFactorChallenge(object $user, int $expirationMinutes, string $ip): void
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email === '') {
            Logger::auth('API 2FA blocked: user has no email', ['user_id' => $user->id]);
            $this->apiError(
                ApiErrorCode::TWO_FACTOR_NO_EMAIL,
                '2FA is enabled but this account has no email address. Contact your administrator.',
                403
            );
            return;
        }

        $challenge = ApiTwoFactorChallenge::create(
            (int) $user->id,
            $expirationMinutes,
            $ip,
            isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : null
        );

        $send = Mailer::send(
            $email,
            (AppSettings::getBrandingConfig()->app_name ?? 'Simple CMS') . ' Login Code',
            'Your verification code is: ' . $challenge['code']
            . "\n\nThis code expires at " . $challenge['expires_at'] . '.'
        );
        if (empty($send['success'])) {
            Logger::auth('API 2FA email send failed', [
                'user_id' => $user->id,
                'error' => $send['error'] ?? '',
            ]);
            $this->apiError(
                ApiErrorCode::TWO_FACTOR_SEND_FAILED,
                'Failed to send the verification email. Please try again.',
                502
            );
            return;
        }

        Logger::auth('API 2FA challenge issued', ['user_id' => $user->id]);
        $this->apiSuccess([
            'pending_2fa' => true,
            'challenge_id' => $challenge['challenge_id'],
            'expires_at' => $challenge['expires_at'],
            'email_hint' => ApiTwoFactorChallenge::maskEmail($email),
        ]);
    }

    private function issueTokenResponse(object $user, string $ip): void
    {
        LoginThrottle::clear($ip);
        $result = ApiToken::create((int) $user->id);
        \App\AuditLog::record('user', (int) $user->id, 'login', ['ip' => $ip, 'channel' => 'api']);
        \App\TrafficLog::recordAuthEvent(
            'login_success',
            "API logged in successfully as '" . ($user->username ?? '') . "'",
            (int) $user->id,
            $user->username ?? null,
            200
        );

        $this->apiSuccess([
            'token'      => $result['token'],
            'expires_at' => $result['expires_at'],
            'user'       => [
                'id'           => (int) $user->id,
                'username'     => $user->username,
                'display_name' => $user->display_name ?? $user->username,
                'email'        => $user->email ?? null,
            ],
        ]);
    }

    private function findUserById(int $userId): ?object
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, username, display_name, email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    private function lookupUserIdForChallenge(string $challengeId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT user_id FROM api_2fa_challenges WHERE challenge_id = ? LIMIT 1');
        $stmt->execute([$challengeId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ? (int) $row->user_id : 0;
    }

    private function maskChallenge(string $challengeId): string
    {
        $len = strlen($challengeId);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }
        return substr($challengeId, 0, 4) . str_repeat('*', $len - 8) . substr($challengeId, -4);
    }
}
