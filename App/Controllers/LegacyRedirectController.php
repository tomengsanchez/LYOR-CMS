<?php
namespace App\Controllers;

use App\AdminPath;
use Core\Controller;

/** Permanent redirects from pre-/admin URL paths. */
class LegacyRedirectController extends Controller
{
    private function go(string $adminPath, int $code = 301): void
    {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        $url = AdminPath::url($adminPath);
        if ($qs !== '') {
            $url .= '?' . $qs;
        }
        $this->redirect($url, $code);
    }

    public function login(): void
    {
        $this->go('login');
    }

    public function login2fa(): void
    {
        $this->go('login/2fa');
    }

    public function help(): void
    {
        $this->go('help');
    }

    public function helpFragment(): void
    {
        $this->go('help/fragment');
    }

    public function pages(): void
    {
        $this->go('pages');
    }

    public function posts(): void
    {
        $this->go('posts');
    }

    public function categories(): void
    {
        $this->go('categories');
    }

    public function media(): void
    {
        $this->go('media');
    }

    public function settings(): void
    {
        $this->go('settings');
    }

    public function users(): void
    {
        $this->go('users');
    }

    public function account(): void
    {
        $this->go('account');
    }

    public function notifications(): void
    {
        $this->go('notifications');
    }

    public function adminGuide(): void
    {
        $this->go('admin-guide');
    }

    public function systemGeneral(): void
    {
        $this->go('system/general');
    }

    public function systemBackupRestore(): void
    {
        $this->go('system/backup-restore');
    }

    public function systemAuditTrail(): void
    {
        $this->go('system/audit-trail');
    }
}
