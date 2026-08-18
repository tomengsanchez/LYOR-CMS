<?php
namespace App;

use App\Models\AppSettings;

class NewsletterSettings
{
    public static function get(): object
    {
        return (object) [
            'enabled' => AppSettings::get('newsletter_enabled', '1') === '1',
            'double_opt_in' => AppSettings::get('newsletter_double_opt_in', '1') === '1',
            'rate_limit_per_hour' => max(0, min(100, (int) AppSettings::get('newsletter_rate_limit', '8'))),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        AppSettings::set('newsletter_enabled', !empty($data['newsletter_enabled']) ? '1' : '0');
        AppSettings::set('newsletter_double_opt_in', !empty($data['newsletter_double_opt_in']) ? '1' : '0');
        $rate = max(0, min(100, (int) ($data['newsletter_rate_limit'] ?? 8)));
        AppSettings::set('newsletter_rate_limit', (string) $rate);
    }
}
