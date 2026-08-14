<?php
/**
 * Smoke test: LLM discovery (llms.txt), post SEO columns, AI robots rules.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\LlmsTxt;
use App\Models\AppSettings;
use App\PublicSeo;
use Core\Database;

$db = Database::getInstance();
foreach (['meta_title', 'meta_description', 'llm_summary', 'robots_noindex'] as $col) {
    $rows = $db->query('SHOW COLUMNS FROM cms_posts LIKE ' . $db->quote($col))->fetchAll();
    assert(count($rows) === 1, "cms_posts.{$col} should exist");
}

$seo = AppSettings::getSiteSeoConfig();
assert(isset($seo->enable_llms_txt), 'enable_llms_txt setting exists');
assert(isset($seo->allow_ai_crawlers), 'allow_ai_crawlers setting exists');

AppSettings::saveSiteSeoConfig([
    'seo_title_suffix' => $seo->title_suffix ?? '',
    'seo_default_description' => $seo->default_description ?? '',
    'seo_default_image_id' => $seo->default_image_id,
    'seo_twitter_handle' => $seo->twitter_handle ?? '',
    'seo_google_site_verification' => $seo->google_site_verification ?? '',
    'seo_locale' => $seo->locale ?? 'en_US',
    'seo_site_keywords' => $seo->site_keywords ?? '',
    'llm_site_summary' => $seo->llm_site_summary !== '' ? $seo->llm_site_summary : 'A test site for language models.',
    'seo_enable_json_export' => true,
    'seo_enable_sitemap' => true,
    'seo_enable_rss_feed' => $seo->enable_rss_feed,
    'seo_enable_llms_txt' => true,
    'seo_allow_ai_crawlers' => true,
    'seo_facebook_url' => 'https://www.facebook.com/example',
    'seo_linkedin_url' => 'https://www.linkedin.com/company/example',
]);

$reloaded = AppSettings::getSiteSeoConfig();
assert($reloaded->enable_llms_txt === true, 'llms.txt enabled');
assert($reloaded->allow_ai_crawlers === true, 'AI crawlers allowed');
assert($reloaded->facebook_url === 'https://www.facebook.com/example', 'facebook url saved');

$txt = LlmsTxt::indexDocument();
assert(str_contains($txt, '# '), 'llms.txt has title heading');
assert(str_contains($txt, '## Optional') || str_contains($txt, '## Pages') || str_contains($txt, '## Posts'), 'llms.txt has sections');

$robots = LlmsTxt::robotsTxt();
assert(str_contains($robots, 'User-agent: GPTBot'), 'robots lists GPTBot');
assert(str_contains($robots, 'Allow: /'), 'robots allow when AI enabled');
assert(str_contains($robots, 'llms.txt'), 'robots mentions llms.txt');
assert(str_contains($robots, 'Disallow: /admin'), 'robots disallows admin');

$site = PublicSeo::siteJsonDocument();
assert(!empty($site['endpoints']['llms']), 'site json has llms endpoint');
assert(!empty($site['endpoints']['blog_json']), 'site json has blog.json endpoint');

$ld = PublicSeo::siteJsonLd(null, ['description' => 'x']);
assert(($ld['potentialAction']['@type'] ?? '') === 'SearchAction', 'WebSite SearchAction');
assert(in_array('https://www.facebook.com/example', $ld['sameAs'] ?? [], true), 'sameAs includes facebook');

$blog = PublicSeo::blogJsonDocument();
assert($blog['type'] === 'blog', 'blog json type');
assert(isset($blog['posts']), 'blog json posts');

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
    'seo_enable_rss_feed' => $seo->enable_rss_feed,
    'seo_enable_llms_txt' => $seo->enable_llms_txt,
    'seo_allow_ai_crawlers' => $seo->allow_ai_crawlers,
    'seo_facebook_url' => $seo->facebook_url ?? '',
    'seo_linkedin_url' => $seo->linkedin_url ?? '',
]);

echo "cms_llm_discovery_smoke_test: OK\n";
