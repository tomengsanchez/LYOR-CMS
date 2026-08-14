<?php
namespace App;

use App\Models\AppSettings;

class DiscussionSettings
{
    public static function get(): object
    {
        return (object) [
            'comments_enabled' => AppSettings::get('discussion_comments_enabled', '1') === '1',
            'moderation' => AppSettings::get('discussion_moderation', '1') === '1',
            'require_name_email' => AppSettings::get('discussion_require_name_email', '1') === '1',
            'show_sidebar' => AppSettings::get('discussion_show_sidebar', '1') === '1',
            'comment_rate_limit_per_hour' => max(0, min(100, (int) AppSettings::get('discussion_comment_rate_limit', '10'))),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        AppSettings::set('discussion_comments_enabled', !empty($data['discussion_comments_enabled']) ? '1' : '0');
        AppSettings::set('discussion_moderation', !empty($data['discussion_moderation']) ? '1' : '0');
        AppSettings::set('discussion_require_name_email', !empty($data['discussion_require_name_email']) ? '1' : '0');
        AppSettings::set('discussion_show_sidebar', !empty($data['discussion_show_sidebar']) ? '1' : '0');
        $rate = max(0, min(100, (int) ($data['discussion_comment_rate_limit'] ?? 10)));
        AppSettings::set('discussion_comment_rate_limit', (string) $rate);
    }
}
