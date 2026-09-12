<?php
/**
 * Smoke: Google Analytics / Ads / AdSense / CSE ID normalize + save.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\GoogleSettings;
use App\Models\AppSettings;
use Core\Database;

assert(GoogleSettings::normalizeAnalyticsId('g-abc12xyz') === 'G-ABC12XYZ', 'GA4 uppercased');
assert(GoogleSettings::normalizeAnalyticsId('UA-12345678-1') === 'UA-12345678-1', 'legacy UA kept');
assert(GoogleSettings::normalizeAnalyticsId('javascript:alert(1)') === '', 'analytics junk rejected');
assert(GoogleSettings::normalizeAnalyticsId('<script>G-ABC12XYZ</script>') === '', 'analytics script rejected');
assert(GoogleSettings::normalizeAnalyticsId('G-AB') === '', 'analytics too short rejected');

assert(GoogleSettings::normalizeAdsId('aw-123456789') === 'AW-123456789', 'ads uppercased');
assert(GoogleSettings::normalizeAdsId('AW-12') === '', 'ads too short rejected');
assert(GoogleSettings::normalizeAdsId('javascript:AW-123456789') === '', 'ads junk rejected');

assert(GoogleSettings::normalizeAdsenseClient('CA-PUB-1234567890123456') === 'ca-pub-1234567890123456', 'adsense lowercased');
assert(GoogleSettings::normalizeAdsenseClient('ca-pub-123') === '', 'adsense too short rejected');
assert(GoogleSettings::normalizeAdsenseClient('javascript:ca-pub-1234567890123456') === '', 'adsense junk rejected');

assert(GoogleSettings::normalizeCseCx('partner-pub-123:abcd') === 'partner-pub-123:abcd', 'cse cx kept');
assert(GoogleSettings::normalizeCseCx('short') === '', 'cse too short rejected');
assert(GoogleSettings::normalizeCseCx('https://evil.example/x') === '', 'cse url rejected');

$empty = (object) ['analytics_id' => '', 'ads_id' => '', 'adsense_client' => ''];
assert(GoogleSettings::hasPublicTags($empty) === false, 'empty has no public tags');
assert(GoogleSettings::hasPublicTags((object) ['analytics_id' => 'G-ABC12XYZ', 'ads_id' => '', 'adsense_client' => '']) === true, 'GA has public tags');
assert(GoogleSettings::gtagLoaderId((object) ['analytics_id' => '', 'ads_id' => 'AW-123456789']) === 'AW-123456789', 'loader falls back to ads');

Database::getInstance();

$prev = [
    'google_analytics_id' => (string) AppSettings::get('google_analytics_id', ''),
    'google_ads_id' => (string) AppSettings::get('google_ads_id', ''),
    'google_adsense_client' => (string) AppSettings::get('google_adsense_client', ''),
    'google_cse_cx' => (string) AppSettings::get('google_cse_cx', ''),
];

try {
    GoogleSettings::save([
        'google_analytics_id' => 'G-SMOKE12TEST',
        'google_ads_id' => 'AW-9876543210',
        'google_adsense_client' => 'ca-pub-9876543210987654',
        'google_cse_cx' => 'cse-smoke-engine-id',
    ]);
    $got = GoogleSettings::get();
    assert($got->analytics_id === 'G-SMOKE12TEST', 'saved analytics');
    assert($got->ads_id === 'AW-9876543210', 'saved ads');
    assert($got->adsense_client === 'ca-pub-9876543210987654', 'saved adsense');
    assert($got->cse_cx === 'cse-smoke-engine-id', 'saved cse');
    assert(GoogleSettings::hasPublicTags($got) === true, 'saved has public tags');

    GoogleSettings::save([
        'google_analytics_id' => '<script>alert(1)</script>',
        'google_ads_id' => 'not-an-id',
        'google_adsense_client' => 'javascript:alert(1)',
        'google_cse_cx' => '../../etc/passwd',
    ]);
    $cleared = GoogleSettings::get();
    assert($cleared->analytics_id === '', 'invalid analytics cleared');
    assert($cleared->ads_id === '', 'invalid ads cleared');
    assert($cleared->adsense_client === '', 'invalid adsense cleared');
    assert($cleared->cse_cx === '', 'invalid cse cleared');
    assert(GoogleSettings::hasPublicTags($cleared) === false, 'cleared has no public tags');
} finally {
    GoogleSettings::save($prev);
}

echo "cms_google_settings_smoke_test: OK\n";
