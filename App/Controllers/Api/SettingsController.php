<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\GeneralSettings;
use App\DevelopmentSettings;
use App\DiscussionSettings;
use App\PermalinkSettings;
use App\ReadingSettings;
use App\DevClock;
use App\Models\AppSettings;
use App\PublicTheme;
use App\UserUiSettings;
use App\UserNotificationSettings;

class SettingsController extends Controller
{
    /**
     * Current user's UI and notification preferences.
     */
    public function ui(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }

        $ui = UserUiSettings::get();
        $notify = UserNotificationSettings::get();

        $this->apiSuccess([
            'ui' => $ui,
            'notifications' => $notify,
        ]);
    }

    /**
     * General system settings (region, timezone, branding).
     * Admin-only.
     */
    public function general(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            $this->apiForbidden('Administrator access required.');
            return;
        }

        $settings = GeneralSettings::get();
        $branding = AppSettings::getBrandingConfig();
        $siteSeo = AppSettings::getSiteSeoConfig();
        $publicTheme = PublicTheme::getConfig();

        $this->apiSuccess([
            'settings' => $settings,
            'branding' => $branding,
            'site_seo' => $siteSeo,
            'public_theme' => $publicTheme,
            'reading' => ReadingSettings::get(),
            'discussion' => DiscussionSettings::get(),
            'permalinks' => PermalinkSettings::get(),
            'regions' => GeneralSettings::regions(),
            'timezones' => GeneralSettings::timezones(),
        ]);
    }

    /**
     * Email (SMTP) settings.
     * Requires view_email_settings capability.
     */
    public function email(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::can('view_email_settings')) {
            $this->apiForbidden('view_email_settings capability required.');
            return;
        }

        $config = AppSettings::getEmailConfig();

        $this->apiSuccess([
            'config' => $config,
        ]);
    }

    /**
     * Security settings (password policy, login throttling, etc.).
     * Requires view_security_settings capability.
     */
    public function security(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::can('view_security_settings')) {
            $this->apiForbidden('view_security_settings capability required.');
            return;
        }

        $config = AppSettings::getSecurityConfig();
        $apiClients = \App\ApiClients::getConfig();

        $this->apiSuccess([
            'config' => $config,
            'api_clients' => [
                'enabled' => $apiClients['enabled'],
                'clients' => \App\ApiClients::listForUi(),
            ],
        ]);
    }

    /**
     * Development settings and simulated time.
     * Admin-only.
     */
    public function development(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            $this->apiForbidden('Administrator access required.');
            return;
        }

        $settings = DevelopmentSettings::get();
        $simulatedOverride = DevClock::getOverride();

        $this->apiSuccess([
            'settings' => $settings,
            'simulated_date' => $simulatedOverride,
        ]);
    }

    /**
     * Operational settings.
     * Requires view_operational_settings capability (or Administrator).
     */
    public function operational(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin() && !Auth::can('view_operational_settings')) {
            $this->apiForbidden('view_operational_settings capability required.');
            return;
        }

        $defaultFieldRoleId = (int) AppSettings::get('operational_default_field_role_id', '0');
        $role = null;
        if ($defaultFieldRoleId > 0) {
            $db = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT id, name FROM roles WHERE id = ?');
            $stmt->execute([$defaultFieldRoleId]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            if ($row) {
                $role = ['id' => (int) $row->id, 'name' => (string) $row->name];
            }
        }

        $this->apiSuccess([
            'settings' => [
                'default_field_role_id' => $defaultFieldRoleId > 0 ? $defaultFieldRoleId : null,
                'default_field_role' => $role,
            ],
            'holidays' => array_map(static function ($row) {
                return [
                    'id' => (int) ($row->id ?? 0),
                    'name' => (string) ($row->name ?? ''),
                    'description' => $row->description !== null ? (string) $row->description : null,
                    'holiday_date' => (string) ($row->holiday_date ?? ''),
                ];
            }, \App\Models\Holiday::all()),
        ]);
    }

    /**
     * Realtime security settings and recent auth telemetry summary.
     * Admin-only.
     */
    public function realtimeSecurity(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            $this->apiForbidden('Administrator access required.');
            return;
        }

        $controller = new \App\Controllers\RealtimeSecurityController();
        $controller->apiSummary();
    }

    /**
     * Trigger realtime security malware scan and return report.
     * Admin-only.
     */
    public function realtimeSecurityMalwareCheck(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            $this->apiForbidden('Administrator access required.');
            return;
        }

        $controller = new \App\Controllers\RealtimeSecurityController();
        $controller->apiMalwareCheck();
    }

    /**
     * Admin realtime presence dashboard snapshot.
     * Admin-only.
     */
    public function realtimeDashboard(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            $this->apiForbidden('Administrator access required.');
            return;
        }

        $controller = new \App\Controllers\RealtimeDashboardController();
        $controller->apiSummary();
    }

    /**
     * Admin force-end of a user session from the realtime dashboard.
     * Admin-only; CSRF required.
     */
    public function realtimeDashboardRevokeSession(int $id): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            $this->apiForbidden('Administrator access required.');
            return;
        }

        $controller = new \App\Controllers\RealtimeDashboardController();
        $controller->apiRevokeSession($id);
    }

    /**
     * Admin force-revoke of a Bearer API token from the realtime dashboard.
     * Admin-only; CSRF required.
     */
    public function realtimeDashboardRevokeToken(int $id): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            $this->apiForbidden('Administrator access required.');
            return;
        }

        $controller = new \App\Controllers\RealtimeDashboardController();
        $controller->apiRevokeToken($id);
    }
}

