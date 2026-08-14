<?php
/**
 * Smoke test: app timezone + UserTime format kinds (system vs business).
 * Run: php tests/cli/app_timezone_usertime_test.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\GeneralSettings;
use App\UserTime;
use App\Models\GrievanceStatusLog;
use Core\Database;

$tz = UserTime::timezoneId();
if ($tz === '' || !in_array($tz, timezone_identifiers_list(), true)) {
    fwrite(STDERR, "FAIL: invalid timezoneId: {$tz}\n");
    exit(1);
}

$offset = UserTime::mysqlOffset();
if (!preg_match('/^[+-]\d{2}:\d{2}$/', $offset)) {
    fwrite(STDERR, "FAIL: invalid mysqlOffset: {$offset}\n");
    exit(1);
}

$db = Database::getInstance();
$row = $db->query('SELECT @@session.time_zone AS session_tz, NOW() AS now_sql')->fetch(\PDO::FETCH_ASSOC);
$sessionTz = (string) ($row['session_tz'] ?? '');
if ($sessionTz !== $offset && $sessionTz !== 'SYSTEM') {
    // Accept either our offset or SYSTEM only if we failed to set; require offset match.
    if ($sessionTz !== $offset) {
        fwrite(STDERR, "FAIL: session time_zone expected {$offset}, got {$sessionTz}\n");
        exit(1);
    }
}

$business = '2026-01-15 09:30:00';
$formattedBusiness = UserTime::formatBusiness($business, 'Y-m-d H:i:s');
if ($formattedBusiness !== $business) {
    fwrite(STDERR, "FAIL: business format must be pass-through, got {$formattedBusiness}\n");
    exit(1);
}

$pretty = UserTime::formatBusiness($business, 'M j, Y H:i');
if ($pretty !== 'Jan 15, 2026 09:30') {
    fwrite(STDERR, "FAIL: unexpected business pretty format: {$pretty}\n");
    exit(1);
}

$systemRaw = '2026-06-15 14:00:00';
$systemFormatted = UserTime::formatSystem($systemRaw);
if ($systemFormatted !== $systemRaw) {
    fwrite(STDERR, "FAIL: system format under aligned TZ should pass-through wall clock, got {$systemFormatted}\n");
    exit(1);
}

$entry = (object) [
    'id' => 1,
    'status' => 'open',
    'progress_level' => null,
    'progress_level_name' => null,
    'note' => '',
    'effective_at' => '2026-06-10 09:00:00',
    'created_at' => '2026-06-15 14:00:00',
    'created_by' => 1,
    'created_by_name' => 'admin',
    'attachments' => null,
];
$api = GrievanceStatusLog::entryForApi($entry, 10);
if ($api['effective_at'] !== '2026-06-10 09:00:00') {
    fwrite(STDERR, "FAIL: effective_at must remain unchanged, got " . ($api['effective_at'] ?? 'null') . "\n");
    exit(1);
}
if ($api['recorded_at_differs'] !== true) {
    fwrite(STDERR, "FAIL: recorded_at_differs should be true\n");
    exit(1);
}

$defaults = GeneralSettings::DEFAULT_TIMEZONE;
if ($defaults !== 'UTC') {
    fwrite(STDERR, "FAIL: unexpected DEFAULT_TIMEZONE\n");
    exit(1);
}

echo "OK app_timezone_usertime_test timezone={$tz} offset={$offset} session_tz={$sessionTz}\n";
exit(0);
