<?php
/**
 * CLI smoke test: GPS text columns + decimal parse (migration 065).
 * Run: php tests/cli/structure_gps_text_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Structure;

$payload = Structure::expandTaggingPayload([
    'gps_latitude' => 'N15°58.209',
    'gps_longitude' => 'E120°9.687',
]);

if ($payload['gps_latitude_text'] !== 'N15°58.209') {
    fwrite(STDERR, "gps_latitude_text mismatch\n");
    exit(1);
}
if ($payload['gps_longitude_text'] !== 'E120°9.687') {
    fwrite(STDERR, "gps_longitude_text mismatch\n");
    exit(1);
}
if ($payload['gps_latitude'] === null || abs($payload['gps_latitude'] - 15.97015) > 0.0001) {
    fwrite(STDERR, 'gps_latitude decimal mismatch: ' . var_export($payload['gps_latitude'], true) . "\n");
    exit(1);
}
if ($payload['gps_longitude'] === null || abs($payload['gps_longitude'] - 120.16145) > 0.0001) {
    fwrite(STDERR, 'gps_longitude decimal mismatch: ' . var_export($payload['gps_longitude'], true) . "\n");
    exit(1);
}

$map = Structure::gpsMapLinkFromFields('N15°58.209', 'E120°9.687');
if ($map === null || strpos($map['url'], 't=k') === false) {
    fwrite(STDERR, "gps map link should use satellite basemap (t=k)\n");
    exit(1);
}
if (empty($map['embed_url']) || strpos($map['embed_url'], 'output=embed') === false) {
    fwrite(STDERR, "gps embed_url should include output=embed\n");
    exit(1);
}

echo "structure_gps_text_test: OK\n";
