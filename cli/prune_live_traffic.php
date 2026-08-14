#!/usr/bin/env php
<?php
/**
 * Prune Live Traffic events older than the configured retention period.
 *
 * Usage:
 *   php cli/prune_live_traffic.php
 *   php cli/prune_live_traffic.php --days=14
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    die("This script must be run from CLI.\n");
}

require_once dirname(__DIR__) . '/bootstrap.php';

use App\TrafficLog;

$days = null;
foreach ($argv as $arg) {
    if (str_starts_with((string) $arg, '--days=')) {
        $days = (int) substr((string) $arg, 7);
    }
}
if ($days === null || $days < 1) {
    $days = TrafficLog::retentionDays();
}

$deleted = TrafficLog::pruneOlderThan($days);
fwrite(STDOUT, "Pruned {$deleted} traffic_events older than {$days} day(s).\n");
exit(0);
