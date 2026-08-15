<?php
namespace App\Models;

use Core\Database;

class AppSettings
{
    public static function get(string $key, $default = null)
    {
        $stmt = Database::getInstance()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v !== false ? $v : $default;
    }

    public static function set(string $key, string $value): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([$key, $value]);
    }

    public static function getEmailConfig(): object
    {
        return (object) [
            'email_provider' => self::get('email_provider', 'smtp'),
            'smtp_host'     => self::get('smtp_host', ''),
            'smtp_port'     => (int) self::get('smtp_port', 587),
            'smtp_username' => self::get('smtp_username', ''),
            'smtp_password' => self::get('smtp_password', ''),
            'smtp_encryption' => self::get('smtp_encryption', 'tls'),
            'mailersend_api_token' => self::get('mailersend_api_token', ''),
            'mailersend_from_email' => self::get('mailersend_from_email', ''),
            'mailersend_from_name' => self::get('mailersend_from_name', ''),
            'from_email'    => self::get('from_email', ''),
            'from_name'     => self::get('from_name', ''),
            'enable_notification_emails' => self::get('enable_notification_emails', '0') === '1',
        ];
    }

    public static function saveEmailConfig(array $data): void
    {
        $provider = strtolower(trim((string) ($data['email_provider'] ?? 'smtp')));
        if (!in_array($provider, ['smtp', 'mailersend'], true)) {
            $provider = 'smtp';
        }
        self::set('email_provider', $provider);

        $keys = [
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_encryption',
            'mailersend_from_email',
            'mailersend_from_name',
            'from_email',
            'from_name',
        ];
        foreach ($keys as $k) {
            $v = $data[$k] ?? '';
            if ($k === 'smtp_port') $v = (string) ((int) $v ?: 587);
            self::set($k, (string) $v);
        }
        if (($data['smtp_password'] ?? '') !== '') {
            self::set('smtp_password', $data['smtp_password']);
        }
        if (($data['mailersend_api_token'] ?? '') !== '') {
            self::set('mailersend_api_token', (string) $data['mailersend_api_token']);
        }
        $enableNotificationEmails = !empty($data['enable_notification_emails']);
        self::set('enable_notification_emails', $enableNotificationEmails ? '1' : '0');
    }

    public static function getSecurityConfig(): object
    {
        $enabled = self::get('enable_email_2fa', '0') === '1';
        return (object) [
            'enable_email_2fa' => $enabled,
            '2fa_expiration_minutes' => (int) self::get('2fa_expiration_minutes', 15),
            'user_logout_after_minutes' => (int) self::get('user_logout_after_minutes', 30),
            // Login throttling / brute-force protection
            'login_throttle_enabled' => self::get('login_throttle_enabled', '1') === '1',
            'login_throttle_max_attempts' => (int) self::get('login_throttle_max_attempts', 5),
            'login_throttle_lockout_minutes' => (int) self::get('login_throttle_lockout_minutes', 15),
            // Password policy
            'password_min_length' => (int) self::get('password_min_length', 8),
            'password_require_upper' => self::get('password_require_upper', '1') === '1',
            'password_require_lower' => self::get('password_require_lower', '1') === '1',
            'password_require_number' => self::get('password_require_number', '1') === '1',
            'password_require_symbol' => self::get('password_require_symbol', '0') === '1',
            'password_expiry_days' => (int) self::get('password_expiry_days', 0),
            'password_history_limit' => (int) self::get('password_history_limit', 5),
        ];
    }

    public static function saveSecurityConfig(array $data): void
    {
        $enabled = !empty($data['enable_email_2fa']);
        self::set('enable_email_2fa', $enabled ? '1' : '0');
        if ($enabled) {
            $mins = max(1, min(1440, (int) ($data['2fa_expiration_minutes'] ?? 15)));
            self::set('2fa_expiration_minutes', (string) $mins);
        }
        $logoutMins = max(0, min(10080, (int) ($data['user_logout_after_minutes'] ?? 30)));
        self::set('user_logout_after_minutes', (string) $logoutMins);

        // Login throttling settings
        $loginThrottleEnabled = !empty($data['login_throttle_enabled']);
        self::set('login_throttle_enabled', $loginThrottleEnabled ? '1' : '0');
        // When disabled we still persist last configured values so re-enabling restores them.
        $maxAttempts = (int) ($data['login_throttle_max_attempts'] ?? 5);
        // Hard bounds to keep values reasonable
        $maxAttempts = max(1, min(50, $maxAttempts));
        self::set('login_throttle_max_attempts', (string) $maxAttempts);

        $lockoutMinutes = (int) ($data['login_throttle_lockout_minutes'] ?? 15);
        // 1–1440 minutes (1 day) to allow flexibility but prevent absurd values
        $lockoutMinutes = max(1, min(1440, $lockoutMinutes));
        self::set('login_throttle_lockout_minutes', (string) $lockoutMinutes);

        // Password policy settings
        $minLength = (int) ($data['password_min_length'] ?? 8);
        $minLength = max(1, min(128, $minLength));
        self::set('password_min_length', (string) $minLength);

        $requireUpper = !empty($data['password_require_upper']);
        $requireLower = !empty($data['password_require_lower']);
        $requireNumber = !empty($data['password_require_number']);
        $requireSymbol = !empty($data['password_require_symbol']);
        self::set('password_require_upper', $requireUpper ? '1' : '0');
        self::set('password_require_lower', $requireLower ? '1' : '0');
        self::set('password_require_number', $requireNumber ? '1' : '0');
        self::set('password_require_symbol', $requireSymbol ? '1' : '0');

        $expiryDays = (int) ($data['password_expiry_days'] ?? 0);
        $expiryDays = max(0, min(3650, $expiryDays));
        self::set('password_expiry_days', (string) $expiryDays);

        $historyLimit = (int) ($data['password_history_limit'] ?? 5);
        $historyLimit = max(0, min(50, $historyLimit));
        self::set('password_history_limit', (string) $historyLimit);
    }

    /**
     * Branding (app name, company name, logo).
     */
    public static function getBrandingConfig(): object
    {
        // Defaults keep existing behavior if not configured
        $appName = self::get('app_name', 'Simple CMS');
        $companyName = self::get('company_name', '');
        $logoPath = self::get('app_logo_path', '');
        $accent = self::normalizeAccentColor(self::get('public_accent_color', '#2563eb'));
        return (object) [
            'app_name' => $appName,
            'company_name' => $companyName,
            'logo_path' => $logoPath,
            'public_accent_color' => $accent,
        ];
    }

    public static function saveBrandingConfig(array $data): void
    {
        $appName = trim($data['app_name'] ?? '');
        $companyName = trim($data['company_name'] ?? '');
        if ($appName === '') {
            $appName = 'Simple CMS';
        }
        self::set('app_name', $appName);
        self::set('company_name', $companyName);
        if (!empty($data['logo_path'])) {
            self::set('app_logo_path', (string) $data['logo_path']);
        }
        if (array_key_exists('public_accent_color', $data)) {
            self::set('public_accent_color', self::normalizeAccentColor(trim((string) $data['public_accent_color'])));
        }
    }

    public static function normalizeAccentColor(string $value): string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return '#2563eb';
        }
        return strtolower($value);
    }

    /**
     * Site-wide SEO & LLM defaults (System → General).
     */
    public static function getSiteSeoConfig(): object
    {
        $defaultImageId = (int) self::get('seo_default_image_id', '0');
        $defaultImageId = $defaultImageId > 0 ? $defaultImageId : null;
        return (object) [
            'title_suffix' => self::get('seo_title_suffix', ''),
            'default_description' => self::get('seo_default_description', ''),
            'default_image_id' => $defaultImageId,
            'twitter_handle' => self::normalizeTwitterHandle(self::get('seo_twitter_handle', '')),
            'google_site_verification' => trim(self::get('seo_google_site_verification', '')),
            'locale' => self::normalizeLocale(self::get('seo_locale', 'en_US')),
            'site_keywords' => trim(self::get('seo_site_keywords', '')),
            'llm_site_summary' => trim(self::get('llm_site_summary', '')),
            'enable_json_export' => self::get('seo_enable_json_export', '1') === '1',
            'enable_sitemap' => self::get('seo_enable_sitemap', '1') === '1',
            'enable_rss_feed' => self::get('seo_enable_rss_feed', '1') === '1',
            'enable_llms_txt' => self::get('seo_enable_llms_txt', '1') === '1',
            'allow_ai_crawlers' => self::get('seo_allow_ai_crawlers', '1') === '1',
            'facebook_url' => self::normalizeHttpUrl(self::get('seo_facebook_url', '')),
            'linkedin_url' => self::normalizeHttpUrl(self::get('seo_linkedin_url', '')),
            'reddit_url' => self::normalizeHttpUrl(self::get('seo_reddit_url', '')),
            'youtube_url' => self::normalizeHttpUrl(self::get('seo_youtube_url', '')),
            'publisher_expertise' => trim(self::get('seo_publisher_expertise', '')),
            'preferred_citation' => trim(self::get('seo_preferred_citation', '')),
            'citation_guidance' => trim(self::get('seo_citation_guidance', '')),
            'pillar_topics' => trim(self::get('seo_pillar_topics', '')),
            'enable_faq_schema' => self::get('seo_enable_faq_schema', '1') === '1',
            'enable_speakable' => self::get('seo_enable_speakable', '1') === '1',
            'show_ai_writing_tips' => self::get('seo_show_ai_writing_tips', '1') === '1',
        ];
    }

    /** @param array<string, mixed> $data */
    public static function saveSiteSeoConfig(array $data): void
    {
        $suffix = trim((string) ($data['seo_title_suffix'] ?? ''));
        if (mb_strlen($suffix) > 100) {
            $suffix = mb_substr($suffix, 0, 100);
        }
        self::set('seo_title_suffix', $suffix);

        $desc = trim((string) ($data['seo_default_description'] ?? ''));
        if (mb_strlen($desc) > 500) {
            $desc = mb_substr($desc, 0, 500);
        }
        self::set('seo_default_description', $desc);

        $imageId = Media::resolveImageId(
            !empty($data['seo_default_image_id']) ? (int) $data['seo_default_image_id'] : null
        );
        self::set('seo_default_image_id', $imageId ? (string) $imageId : '');

        self::set('seo_twitter_handle', self::normalizeTwitterHandle((string) ($data['seo_twitter_handle'] ?? '')));
        self::set('seo_google_site_verification', mb_substr(trim((string) ($data['seo_google_site_verification'] ?? '')), 0, 120));
        self::set('seo_locale', self::normalizeLocale((string) ($data['seo_locale'] ?? 'en_US')));

        $keywords = trim((string) ($data['seo_site_keywords'] ?? ''));
        if (mb_strlen($keywords) > 255) {
            $keywords = mb_substr($keywords, 0, 255);
        }
        self::set('seo_site_keywords', $keywords);

        $llm = trim((string) ($data['llm_site_summary'] ?? ''));
        if (mb_strlen($llm) > 2000) {
            $llm = mb_substr($llm, 0, 2000);
        }
        self::set('llm_site_summary', $llm);

        self::set('seo_enable_json_export', !empty($data['seo_enable_json_export']) ? '1' : '0');
        self::set('seo_enable_sitemap', !empty($data['seo_enable_sitemap']) ? '1' : '0');
        self::set('seo_enable_rss_feed', !empty($data['seo_enable_rss_feed']) ? '1' : '0');

        if (array_key_exists('seo_enable_llms_txt', $data)) {
            self::set('seo_enable_llms_txt', !empty($data['seo_enable_llms_txt']) ? '1' : '0');
        }
        if (array_key_exists('seo_allow_ai_crawlers', $data)) {
            self::set('seo_allow_ai_crawlers', !empty($data['seo_allow_ai_crawlers']) ? '1' : '0');
        }
        if (array_key_exists('seo_facebook_url', $data)) {
            self::set('seo_facebook_url', self::normalizeHttpUrl((string) $data['seo_facebook_url']));
        }
        if (array_key_exists('seo_linkedin_url', $data)) {
            self::set('seo_linkedin_url', self::normalizeHttpUrl((string) $data['seo_linkedin_url']));
        }
        if (array_key_exists('seo_reddit_url', $data)) {
            self::set('seo_reddit_url', self::normalizeHttpUrl((string) $data['seo_reddit_url']));
        }
        if (array_key_exists('seo_youtube_url', $data)) {
            self::set('seo_youtube_url', self::normalizeHttpUrl((string) $data['seo_youtube_url']));
        }

        $expertise = trim((string) ($data['seo_publisher_expertise'] ?? ''));
        if (mb_strlen($expertise) > 1000) {
            $expertise = mb_substr($expertise, 0, 1000);
        }
        self::set('seo_publisher_expertise', $expertise);

        $citation = trim((string) ($data['seo_preferred_citation'] ?? ''));
        if (mb_strlen($citation) > 500) {
            $citation = mb_substr($citation, 0, 500);
        }
        self::set('seo_preferred_citation', $citation);

        $guidance = trim((string) ($data['seo_citation_guidance'] ?? ''));
        if (mb_strlen($guidance) > 1000) {
            $guidance = mb_substr($guidance, 0, 1000);
        }
        self::set('seo_citation_guidance', $guidance);

        $pillars = trim((string) ($data['seo_pillar_topics'] ?? ''));
        if (mb_strlen($pillars) > 2000) {
            $pillars = mb_substr($pillars, 0, 2000);
        }
        self::set('seo_pillar_topics', $pillars);

        if (array_key_exists('seo_enable_faq_schema', $data)) {
            self::set('seo_enable_faq_schema', !empty($data['seo_enable_faq_schema']) ? '1' : '0');
        }
        if (array_key_exists('seo_enable_speakable', $data)) {
            self::set('seo_enable_speakable', !empty($data['seo_enable_speakable']) ? '1' : '0');
        }
        if (array_key_exists('seo_show_ai_writing_tips', $data)) {
            self::set('seo_show_ai_writing_tips', !empty($data['seo_show_ai_writing_tips']) ? '1' : '0');
        }
    }

    public static function formatSeoTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return '';
        }
        $seo = self::getSiteSeoConfig();
        $suffix = trim((string) ($seo->title_suffix ?? ''));
        if ($suffix !== '' && !str_ends_with($title, $suffix)) {
            return $title . $suffix;
        }
        return $title;
    }

    public static function defaultSeoDescription(): string
    {
        $seo = self::getSiteSeoConfig();
        return trim((string) ($seo->default_description ?? ''));
    }

    public static function normalizeTwitterHandle(string $value): string
    {
        $value = trim($value);
        $value = ltrim($value, '@');
        if ($value === '' || !preg_match('/^[A-Za-z0-9_]{1,15}$/', $value)) {
            return '';
        }
        return $value;
    }

    public static function normalizeLocale(string $value): string
    {
        $value = trim(str_replace('-', '_', $value));
        if ($value === '' || !preg_match('/^[a-z]{2,3}_[A-Z]{2}$/', $value)) {
            return 'en_US';
        }
        return $value;
    }

    public static function normalizeHttpUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $value)) {
            return '';
        }
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return '';
        }
        if (mb_strlen($value) > 255) {
            $value = mb_substr($value, 0, 255);
        }
        return $value;
    }
}
