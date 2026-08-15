<?php
/**
 * Smoke test: site-wide SEO settings in app_settings.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\AppSettings;
use App\PublicSeo;

$seo = AppSettings::getSiteSeoConfig();
assert(isset($seo->default_description), 'site seo config exists');
assert(isset($seo->enable_json_export), 'json export toggle exists');
assert(isset($seo->llm_site_summary), 'llm site summary exists');
assert(AppSettings::normalizeLocale('fil_PH') === 'fil_PH', 'locale fil_PH');
assert(AppSettings::normalizeTwitterHandle('@brand') === 'brand', 'twitter handle normalized');

$doc = PublicSeo::siteJsonDocument();
assert($doc['type'] === 'site', 'site json document');
assert(isset($doc['endpoints']['sitemap']), 'sitemap endpoint in site json');
assert(array_key_exists('publisher_expertise', $doc), 'site json includes publisher_expertise');
assert(array_key_exists('pillar_topics', $doc), 'site json includes pillar_topics');

$ctx = PublicSeo::siteContext();
assert(!empty($ctx['share']['title']), 'site context share title');
assert(array_key_exists('citation_snippet', $ctx), 'site context citation_snippet');

echo "cms_site_seo_smoke_test: OK\n";
