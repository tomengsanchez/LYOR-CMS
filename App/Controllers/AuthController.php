<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;
use Core\Database;
use Core\Logger;
use Core\Mailer;
use Core\LoginThrottle;
use App\Models\AppSettings;

class AuthController extends Controller
{
    private function authView(string $view, array $data = []): void
    {
        if (!isset($data['branding'])) {
            $data['branding'] = AppSettings::getBrandingConfig();
        }
        $this->view($view, $data);
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect(AdminPath::url());
        }
        $this->authView('auth/login');
    }

    public function login(): void
    {
        if (!\Core\Csrf::validate()) {
            $this->redirect(AdminPath::url('login') . '?error=csrf');
            return;
        }
        $ip = LoginThrottle::getClientIp();
        if (LoginThrottle::isBlocked($ip)) {
            $security = AppSettings::getSecurityConfig();
            $lockoutMinutes = (int) ($security->login_throttle_lockout_minutes ?? 15);
            if ($lockoutMinutes < 1) {
                $lockoutMinutes = 15;
            }
            \App\TrafficLog::recordAuthEvent(
                'login_blocked',
                'Login blocked by throttle for IP ' . $ip,
                null,
                null,
                429
            );
            $this->authView('auth/login', [
                'error' => 'Too many login attempts. Please try again in ' . $lockoutMinutes . ' minute' . ($lockoutMinutes === 1 ? '' : 's') . '.',
            ]);
            return;
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        Logger::auth('Login attempt', [
            'username' => $username ?: '(empty)',
            'password_provided' => !empty($password),
        ]);

        if (empty($username) || empty($password)) {
            Logger::auth('Login rejected: missing credentials');
            $this->authView('auth/login', ['error' => 'Username and password required']);
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, username, password_hash, email, password_changed_at, created_at FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_OBJ);

            Logger::auth('User lookup result', [
                'username' => $username,
                'user_found' => (bool) $user,
                'user_id' => $user->id ?? null,
            ]);

            if (!$user || !\App\Models\User::canLogin($user) || !password_verify($password, $user->password_hash)) {
                LoginThrottle::recordFailure($ip);
                Logger::auth('Login failed: user not found', ['username' => $username]);
                \App\TrafficLog::recordAuthEvent(
                    'login_failure',
                    "failed login attempt as '" . $username . "'",
                    null,
                    $username,
                    401
                );
                $this->authView('auth/login', ['error' => 'Invalid credentials']);
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
                            Logger::auth('Login blocked: password expired', ['user_id' => $user->id, 'expiry_days' => $expiryDays]);
                            $this->authView('auth/login', ['error' => 'Your password has expired. Please contact your administrator to set a new password.']);
                            return;
                        }
                    }
                }
            }

            if ($security->enable_email_2fa) {
                $email = trim($user->email ?? '');
                if (empty($email)) {
                    Logger::auth('2FA required but user has no email', ['user_id' => $user->id]);
                    $this->authView('auth/login', ['error' => '2FA is enabled but your account has no email. Please contact administrator.']);
                    return;
                }
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires = time() + ($security->{'2fa_expiration_minutes'} * 60);
                $_SESSION['pending_2fa_user_id'] = (int) $user->id;
                $_SESSION['pending_2fa_code'] = $code;
                $_SESSION['pending_2fa_expires'] = $expires;
                $appName = AppSettings::getBrandingConfig()->app_name ?? 'Simple CMS';
                $result = Mailer::send($email, $appName . ' Login Code', "Your verification code is: $code\n\nThis code expires in {$security->{'2fa_expiration_minutes'}} minutes.");
                if (!$result['success']) {
                    Logger::auth('2FA email send failed', ['user_id' => $user->id, 'error' => $result['error'] ?? '']);
                    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
                    $this->authView('auth/login', ['error' => 'Failed to send verification code. Please try again or contact administrator.']);
                    return;
                }
                Logger::auth('2FA code sent', ['user_id' => $user->id]);
                $this->redirect(AdminPath::url('login/2fa'));
                return;
            }

            LoginThrottle::clear($ip);
            Auth::login((int) $user->id);
            \App\UserSession::onLogin((int) $user->id);
            \App\AuditLog::record('user', (int) $user->id, 'login', ['ip' => $ip]);
            Logger::auth('Login success', ['username' => $username, 'user_id' => $user->id]);
            \App\TrafficLog::recordAuthEvent(
                'login_success',
                "logged in successfully as '" . $username . "'",
                (int) $user->id,
                $username,
                200
            );
            $this->redirect(AdminPath::url());
        } catch (\Throwable $e) {
            LoginThrottle::recordFailure($ip ?? LoginThrottle::getClientIp());
            Logger::auth('Login exception', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Logger::php('Auth exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            \App\TrafficLog::recordAuthEvent(
                'login_failure',
                "failed login attempt as '" . ($username ?? '') . "'",
                null,
                $username ?? null,
                401
            );
            $this->authView('auth/login', ['error' => 'Invalid credentials']);
        }
    }

    public function logout(): void
    {
        if (!\Core\Csrf::validate()) {
            $this->redirect(AdminPath::url('login') . '?error=csrf');
            return;
        }
        $userId = Auth::id();
        if ($userId) {
            \App\AuditLog::record('user', (int) $userId, 'logout');
        }
        Auth::logout();
        $this->redirect(AdminPath::url('login'));
    }

    public function twoFactorForm(): void
    {
        if (Auth::check()) {
            $this->redirect(AdminPath::url());
            return;
        }
        if (empty($_SESSION['pending_2fa_user_id']) || empty($_SESSION['pending_2fa_code'])) {
            $this->redirect(AdminPath::url('login'));
            return;
        }
        if (time() > ($_SESSION['pending_2fa_expires'] ?? 0)) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
            $this->redirect(AdminPath::url('login') . '?error=2fa_expired');
            return;
        }
        $this->authView('auth/2fa');
    }

    public function twoFactorVerify(): void
    {
        if (!\Core\Csrf::validate()) {
            $this->redirect(AdminPath::url('login') . '?error=csrf');
            return;
        }
        $code = trim($_POST['code'] ?? '');
        $userId = $_SESSION['pending_2fa_user_id'] ?? null;
        $storedCode = $_SESSION['pending_2fa_code'] ?? '';
        $expires = $_SESSION['pending_2fa_expires'] ?? 0;

        if (!$userId || time() > $expires) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
            $this->redirect(AdminPath::url('login') . '?error=2fa_expired');
            return;
        }

        if ($code !== $storedCode) {
            Logger::auth('2FA verification failed', ['user_id' => $userId]);
            $this->authView('auth/2fa', ['error' => 'Invalid or expired code.']);
            return;
        }

        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
        Auth::login((int) $userId);
        \App\UserSession::onLogin((int) $userId);
        $ip = LoginThrottle::getClientIp();
        \App\AuditLog::record('user', (int) $userId, 'login', ['ip' => $ip]);
        Logger::auth('2FA verification success', ['user_id' => $userId]);
        $username = Auth::user()->username ?? ('#' . $userId);
        \App\TrafficLog::recordAuthEvent(
            'login_success',
            "logged in successfully as '" . $username . "' (2FA)",
            (int) $userId,
            $username,
            200
        );
        $this->redirect(AdminPath::url());
    }
}
