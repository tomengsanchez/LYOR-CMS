<?php
/**
 * Smoke test: RSS feed and site SEO toggles.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\CommentRateLimit;
use App\DiscussionSettings;
use App\Models\AppSettings;
use App\Models\Post;
use Core\Database;

$db = Database::getInstance();

$seo = AppSettings::getSiteSeoConfig();
assert(isset($seo->enable_rss_feed), 'enable_rss_feed setting exists');

$posts = Post::publishedForFeed(5);
assert(is_array($posts), 'publishedForFeed returns array');

AppSettings::saveSiteSeoConfig([
    'seo_title_suffix' => $seo->title_suffix ?? '',
    'seo_default_description' => $seo->default_description ?? '',
    'seo_default_image_id' => $seo->default_image_id,
    'seo_twitter_handle' => $seo->twitter_handle ?? '',
    'seo_google_site_verification' => $seo->google_site_verification ?? '',
    'seo_locale' => $seo->locale ?? 'en_US',
    'seo_site_keywords' => $seo->site_keywords ?? '',
    'llm_site_summary' => $seo->llm_site_summary ?? '',
    'seo_enable_json_export' => $seo->enable_json_export,
    'seo_enable_sitemap' => $seo->enable_sitemap,
    'seo_enable_rss_feed' => true,
]);

assert(AppSettings::getSiteSeoConfig()->enable_rss_feed === true, 'rss feed save');

$discussion = DiscussionSettings::get();
assert(isset($discussion->comment_rate_limit_per_hour), 'comment rate limit setting');
DiscussionSettings::save([
    'discussion_comments_enabled' => 1,
    'discussion_moderation' => 1,
    'discussion_require_name_email' => 1,
    'discussion_show_sidebar' => 1,
    'discussion_comment_rate_limit' => 5,
]);
assert(DiscussionSettings::get()->comment_rate_limit_per_hour === 5, 'rate limit save');
DiscussionSettings::save([
    'discussion_comments_enabled' => 1,
    'discussion_moderation' => 1,
    'discussion_require_name_email' => 1,
    'discussion_show_sidebar' => 1,
    'discussion_comment_rate_limit' => 10,
]);

assert(CommentRateLimit::isLimited('127.0.0.1', 0) === false, 'zero limit disabled');

echo "cms_rss_smoke_test: OK\n";
