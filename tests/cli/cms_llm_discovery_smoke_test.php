<?php
/**
 * Smoke test: LLM discovery (llms.txt), post SEO columns, AI robots rules, citation settings.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\LlmsTxt;
use App\Models\AppSettings;
use App\PublicSeo;
use Core\Database;

$db = Database::getInstance();
foreach (['meta_title', 'meta_description', 'llm_summary', 'citation_snippet', 'faq_json', 'robots_noindex'] as $col) {
    $rows = $db->query('SHOW COLUMNS FROM cms_posts LIKE ' . $db->quote($col))->fetchAll();
    assert(count($rows) === 1, "cms_posts.{$col} should exist");
    $pageRows = $db->query('SHOW COLUMNS FROM cms_pages LIKE ' . $db->quote($col))->fetchAll();
    if (in_array($col, ['citation_snippet', 'faq_json', 'llm_summary', 'robots_noindex', 'meta_title', 'meta_description'], true)) {
        assert(count($pageRows) === 1, "cms_pages.{$col} should exist");
    }
}

$seo = AppSettings::getSiteSeoConfig();
assert(isset($seo->enable_llms_txt), 'enable_llms_txt setting exists');
assert(isset($seo->allow_ai_crawlers), 'allow_ai_crawlers setting exists');
assert(isset($seo->enable_faq_schema), 'enable_faq_schema setting exists');
assert(isset($seo->show_ai_writing_tips), 'show_ai_writing_tips setting exists');

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
    'seo_reddit_url' => 'https://www.reddit.com/r/example',
    'seo_youtube_url' => 'https://www.youtube.com/@example',
    'seo_publisher_expertise' => 'Editors with firsthand CMS operations experience.',
    'seo_preferred_citation' => 'Cite Simple CMS documentation with URL.',
    'seo_citation_guidance' => 'Prefer citation snippets and primary pages.',
    'seo_pillar_topics' => "AI search SEO\nBackup and restore",
    'seo_enable_faq_schema' => true,
    'seo_enable_speakable' => true,
    'seo_show_ai_writing_tips' => true,
]);

$reloaded = AppSettings::getSiteSeoConfig();
assert($reloaded->enable_llms_txt === true, 'llms.txt enabled');
assert($reloaded->allow_ai_crawlers === true, 'AI crawlers allowed');
assert($reloaded->facebook_url === 'https://www.facebook.com/example', 'facebook url saved');
assert($reloaded->reddit_url === 'https://www.reddit.com/r/example', 'reddit url saved');
assert($reloaded->publisher_expertise !== '', 'publisher expertise saved');
assert($reloaded->enable_faq_schema === true, 'faq schema enabled');

$txt = LlmsTxt::indexDocument();
assert(str_contains($txt, '# '), 'llms.txt has title heading');
assert(str_contains($txt, '## Optional') || str_contains($txt, '## Pages') || str_contains($txt, '## Posts'), 'llms.txt has sections');
assert(str_contains($txt, '## Citation'), 'llms.txt has Citation section');
assert(str_contains($txt, '## Topic clusters'), 'llms.txt has Topic clusters');
assert(str_contains($txt, '## Publisher expertise'), 'llms.txt has Publisher expertise');

$robots = LlmsTxt::robotsTxt();
assert(str_contains($robots, 'User-agent: GPTBot'), 'robots lists GPTBot');
assert(str_contains($robots, 'Allow: /'), 'robots allow when AI enabled');
assert(str_contains($robots, 'llms.txt'), 'robots mentions llms.txt');
assert(str_contains($robots, 'Disallow: /admin'), 'robots disallows admin');

$site = PublicSeo::siteJsonDocument();
assert(!empty($site['endpoints']['llms']), 'site json has llms endpoint');
assert(!empty($site['endpoints']['blog_json']), 'site json has blog.json endpoint');
assert(($site['preferred_citation'] ?? '') !== '', 'site json preferred citation');
assert(is_array($site['pillar_topics'] ?? null) && $site['pillar_topics'] !== [], 'site json pillar topics');

$ld = PublicSeo::siteJsonLd(null, ['description' => 'x']);
assert(($ld['potentialAction']['@type'] ?? '') === 'SearchAction', 'WebSite SearchAction');
assert(in_array('https://www.facebook.com/example', $ld['sameAs'] ?? [], true), 'sameAs includes facebook');
assert(in_array('https://www.reddit.com/r/example', $ld['sameAs'] ?? [], true), 'sameAs includes reddit');

$faq = PublicSeo::normalizeFaqJson([
    ['question' => 'How do AI engines find sites?', 'answer' => 'They combine crawler indexes with live retrieval.'],
]);
assert(is_string($faq) && str_contains($faq, 'How do AI engines'), 'faq json normalizes');

$fakePost = (object) [
    'title' => 'AI citation',
    'llm_summary' => 'Summary',
    'citation_snippet' => 'AI engines extract passage-level answers from clear HTML.',
    'faq_json' => $faq,
    'published_at' => '2026-08-15 00:00:00',
    'updated_at' => '2026-08-15 00:00:00',
    'author_name' => 'Editor',
    'category_name' => 'SEO',
];
$postLd = PublicSeo::postJsonLd($fakePost, null, [
    'title' => 'AI citation',
    'description' => 'd',
    'url' => 'https://example.test/blog/ai',
    'image' => '',
], ['seo']);
assert(isset($postLd['@graph']) || ($postLd['@type'] ?? '') === 'BlogPosting', 'post JSON-LD shape');
if (isset($postLd['@graph'])) {
    $types = array_map(static fn ($n) => $n['@type'] ?? '', $postLd['@graph']);
    assert(in_array('FAQPage', $types, true), 'FAQPage in graph');
    $posting = $postLd['@graph'][0];
    assert(($posting['abstract'] ?? '') !== '', 'speakable abstract set');
    assert(isset($posting['speakable']), 'speakable present');
}

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
    'seo_reddit_url' => $seo->reddit_url ?? '',
    'seo_youtube_url' => $seo->youtube_url ?? '',
    'seo_publisher_expertise' => $seo->publisher_expertise ?? '',
    'seo_preferred_citation' => $seo->preferred_citation ?? '',
    'seo_citation_guidance' => $seo->citation_guidance ?? '',
    'seo_pillar_topics' => $seo->pillar_topics ?? '',
    'seo_enable_faq_schema' => $seo->enable_faq_schema,
    'seo_enable_speakable' => $seo->enable_speakable,
    'seo_show_ai_writing_tips' => $seo->show_ai_writing_tips,
]);

echo "cms_llm_discovery_smoke_test: OK\n";
