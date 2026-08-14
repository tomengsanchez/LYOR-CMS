<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use App\Models\AppSettings;

class EmailSettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_email_settings');
        $config = AppSettings::getEmailConfig();
        $this->view('email_settings/index', ['config' => $config]);
    }

    public function update(): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_email_settings');
        AppSettings::saveEmailConfig([
            'email_provider' => trim($_POST['email_provider'] ?? 'smtp'),
            'smtp_host'     => trim($_POST['smtp_host'] ?? ''),
            'smtp_port'     => (int) ($_POST['smtp_port'] ?? 587),
            'smtp_username' => trim($_POST['smtp_username'] ?? ''),
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
            'mailersend_api_token' => trim($_POST['mailersend_api_token'] ?? ''),
            'mailersend_from_email' => trim($_POST['mailersend_from_email'] ?? ''),
            'mailersend_from_name' => trim($_POST['mailersend_from_name'] ?? ''),
            'from_email'    => trim($_POST['from_email'] ?? ''),
            'from_name'     => trim($_POST['from_name'] ?? ''),
            'enable_notification_emails' => isset($_POST['enable_notification_emails']),
        ]);
        $this->redirect(AdminPath::url('settings/email') . '?success=1');
    }

    public function testMail(): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_email_settings');
        $to = trim($_POST['test_email'] ?? '');
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->redirect(AdminPath::url('settings/email') . '?error=invalid');
            return;
        }
        $appName = AppSettings::getBrandingConfig()->app_name ?? 'Simple CMS';
        $result = \Core\Mailer::send($to, $appName . ' Test Email', 'This is a test email from ' . $appName . '. Your email provider configuration is working correctly.');
        if ($result['success']) {
            $this->redirect(AdminPath::url('settings/email') . '?test=success');
        } else {
            $this->redirect(AdminPath::url('settings/email') . '?test=error&msg=' . urlencode($result['error'] ?? 'Unknown error'));
        }
    }
}
