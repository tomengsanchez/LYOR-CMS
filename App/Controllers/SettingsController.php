<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use App\UserUiSettings;
use App\UserNotificationSettings;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_settings');
        $ui = UserUiSettings::get();
        $notifyPrefs = UserNotificationSettings::get();
        $this->view('settings/index', [
            'uiTheme'          => $ui['theme'],
            'uiLayout'         => $ui['layout'],
            'uiColorMode'      => $ui['color_mode'] ?? UserUiSettings::COLOR_MODE_LIGHT,
            'uiMobileFriendly' => !empty($ui['mobile_friendly']),
            'notifyPrefs'      => $notifyPrefs,
        ]);
    }

    public function updateNotifications(): void
    {
        $this->validateCsrf();
        $this->requireCapability('view_settings');
        UserNotificationSettings::save([
            UserNotificationSettings::NOTIFY_PAGE_PUBLISHED  => !empty($_POST['notify_page_published']),
            UserNotificationSettings::NOTIFY_POST_PUBLISHED  => !empty($_POST['notify_post_published']),
            UserNotificationSettings::NOTIFY_MEDIA_UPLOADED  => !empty($_POST['notify_media_uploaded']),
        ]);
        $_SESSION['settings_notifications_saved'] = true;
        $this->redirect(AdminPath::url('settings'));
    }

    public function updateUi(): void
    {
        $this->validateCsrf();
        $this->requireCapability('view_settings');
        UserUiSettings::save([
            'theme'           => trim($_POST['ui_theme'] ?? ''),
            'layout'          => trim($_POST['ui_layout'] ?? ''),
            'color_mode'      => trim($_POST['ui_color_mode'] ?? ''),
            'mobile_friendly' => !empty($_POST['ui_mobile_friendly']),
        ]);
        $_SESSION['settings_ui_saved'] = true;
        $this->redirect(AdminPath::url('settings'));
    }
}
