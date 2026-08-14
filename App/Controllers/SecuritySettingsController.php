<?php
namespace App\Controllers;

use App\AdminPath;

use App\Flash;
use Core\Controller;
use App\Models\AppSettings;

class SecuritySettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_security_settings');
        $config = AppSettings::getSecurityConfig();
        $this->view('security_settings/index', [
            'config' => $config,
            'canManageSecurity' => \Core\Auth::can('manage_security_settings'),
        ]);
    }

    public function update(): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_security_settings');
        AppSettings::saveSecurityConfig([
            'enable_email_2fa' => isset($_POST['enable_email_2fa']),
            '2fa_expiration_minutes' => (int) ($_POST['2fa_expiration_minutes'] ?? 15),
            'user_logout_after_minutes' => (int) ($_POST['user_logout_after_minutes'] ?? 30),
            'login_throttle_enabled' => isset($_POST['login_throttle_enabled']),
            'login_throttle_max_attempts' => (int) ($_POST['login_throttle_max_attempts'] ?? 5),
            'login_throttle_lockout_minutes' => (int) ($_POST['login_throttle_lockout_minutes'] ?? 15),
            'password_min_length' => (int) ($_POST['password_min_length'] ?? 8),
            'password_require_upper' => isset($_POST['password_require_upper']),
            'password_require_lower' => isset($_POST['password_require_lower']),
            'password_require_number' => isset($_POST['password_require_number']),
            'password_require_symbol' => isset($_POST['password_require_symbol']),
            'password_expiry_days' => (int) ($_POST['password_expiry_days'] ?? 0),
            'password_history_limit' => (int) ($_POST['password_history_limit'] ?? 5),
        ]);
        Flash::success('Security settings saved successfully.');
        $this->redirect(AdminPath::url('settings/security'));
    }
}
