<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;
use App\GeneralSettings;
use App\HelpChatSettings;
use App\Models\AppSettings;
use App\Models\Media;
use App\Models\Page;
use App\PublicTheme;
use App\ReadingSettings;
use App\DiscussionSettings;
use App\NewsletterSettings;
use App\PermalinkSettings;
use App\GoogleSettings;

class GeneralController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
        }
    }

    public function index(): void
    {
        $settings = GeneralSettings::get();
        $branding = AppSettings::getBrandingConfig();
        $siteSeo = AppSettings::getSiteSeoConfig();
        $this->view('general/index', [
            'settings'  => $settings,
            'regions'   => GeneralSettings::regions(),
            'timezones' => GeneralSettings::timezones(),
            'branding'  => $branding,
            'siteSeo'   => $siteSeo,
            'mediaImages' => Media::listImages(),
            'helpChat'  => HelpChatSettings::get(),
            'publicTheme' => PublicTheme::getConfig(),
            'reading' => ReadingSettings::get(),
            'publishedPages' => Page::publishedOptions(),
            'discussion' => DiscussionSettings::get(),
            'newsletter' => NewsletterSettings::get(),
            'permalinks' => PermalinkSettings::get(),
            'google' => GoogleSettings::get(),
        ]);
    }

    public function save(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }

        // Save org-wide region/timezone (System → General)
        GeneralSettings::save([
            'region'   => $_POST['region'] ?? '',
            'timezone' => $_POST['timezone'] ?? GeneralSettings::DEFAULT_TIMEZONE,
        ]);
        // Align PHP + MySQL session for the rest of this request
        \App\UserTime::apply(true);
        \Core\Database::refreshSessionTimezone();

        // Handle branding (app name, company name, logo)
        $logoPath = null;
        if (!empty($_FILES['app_logo']['name'] ?? '')) {
            $file = $_FILES['app_logo'];
            if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowed = [
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/x-icon' => 'ico',
                    'image/vnd.microsoft.icon' => 'ico',
                ];
                if (isset($allowed[$mime])) {
                    $ext = $allowed[$mime];
                    $dir = dirname(__DIR__, 2) . '/public/uploads/app';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $filename = 'logo.' . $ext;
                    $dest = $dir . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $logoPath = '/uploads/app/' . $filename;
                    }
                }
            }
        }

        AppSettings::saveBrandingConfig([
            'app_name' => $_POST['app_name'] ?? '',
            'company_name' => $_POST['company_name'] ?? '',
            'logo_path' => $logoPath,
        ]);

        PublicTheme::saveConfig($_POST);

        ReadingSettings::save($_POST);

        DiscussionSettings::save($_POST);
        NewsletterSettings::save($_POST);
        PermalinkSettings::save($_POST);
        GoogleSettings::save($_POST);

        AppSettings::saveSiteSeoConfig([
            'seo_title_suffix' => $_POST['seo_title_suffix'] ?? '',
            'seo_default_description' => $_POST['seo_default_description'] ?? '',
            'seo_default_image_id' => $_POST['seo_default_image_id'] ?? null,
            'seo_twitter_handle' => $_POST['seo_twitter_handle'] ?? '',
            'seo_google_site_verification' => $_POST['seo_google_site_verification'] ?? '',
            'seo_locale' => $_POST['seo_locale'] ?? 'en_US',
            'seo_site_keywords' => $_POST['seo_site_keywords'] ?? '',
            'llm_site_summary' => $_POST['llm_site_summary'] ?? '',
            'seo_enable_json_export' => !empty($_POST['seo_enable_json_export']),
            'seo_enable_sitemap' => !empty($_POST['seo_enable_sitemap']),
            'seo_enable_rss_feed' => !empty($_POST['seo_enable_rss_feed']),
            'seo_enable_llms_txt' => !empty($_POST['seo_enable_llms_txt']),
            'seo_allow_ai_crawlers' => !empty($_POST['seo_allow_ai_crawlers']),
            'seo_facebook_url' => $_POST['seo_facebook_url'] ?? '',
            'seo_linkedin_url' => $_POST['seo_linkedin_url'] ?? '',
            'seo_reddit_url' => $_POST['seo_reddit_url'] ?? '',
            'seo_youtube_url' => $_POST['seo_youtube_url'] ?? '',
            'seo_publisher_expertise' => $_POST['seo_publisher_expertise'] ?? '',
            'seo_preferred_citation' => $_POST['seo_preferred_citation'] ?? '',
            'seo_citation_guidance' => $_POST['seo_citation_guidance'] ?? '',
            'seo_pillar_topics' => $_POST['seo_pillar_topics'] ?? '',
            'seo_enable_faq_schema' => !empty($_POST['seo_enable_faq_schema']),
            'seo_enable_speakable' => !empty($_POST['seo_enable_speakable']),
            'seo_show_ai_writing_tips' => !empty($_POST['seo_show_ai_writing_tips']),
        ]);

        $_SESSION['general_saved'] = true;
        $this->redirect(AdminPath::url('system/general'));
    }

    public function saveHelpChat(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }

        HelpChatSettings::save([
            'enabled' => !empty($_POST['help_chat_enabled']),
            'api_base' => $_POST['help_chat_api_base'] ?? '',
            'model' => $_POST['help_chat_model'] ?? '',
            'max_per_hour' => $_POST['help_chat_max_per_hour'] ?? 30,
            'api_key' => $_POST['help_chat_api_key'] ?? '',
            'clear_api_key' => !empty($_POST['help_chat_clear_api_key']),
        ]);

        $_SESSION['help_chat_saved'] = true;
        $this->redirect(AdminPath::url('system/general'));
    }

    public function themePreview(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->json(['success' => false, 'error' => 'Forbidden']);
            return;
        }
        PublicTheme::setPreviewFromPost($_POST);
        $this->json(['success' => true, 'url' => PublicTheme::previewUrl()]);
    }

    public function themePreviewClear(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }
        PublicTheme::clearPreview();
        $this->redirect(AdminPath::url('system/general'));
    }
}
