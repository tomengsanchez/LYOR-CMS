#!/usr/bin/env php
<?php
/**
 * Database migration CLI
 *
 * Usage:
 *   php cli/migrate.php              Run pending migrations
 *   php cli/migrate.php --status     Show migration status
 *   php cli/migrate.php --rollback   Roll back the last migration
 *   php cli/migrate.php --rollback --steps=2   Roll back the last 2 migrations
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    die('This script must be run from the command line.');
}

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Database;
use Core\MigrationRunner;

$db = Database::getInstance();
$runner = new MigrationRunner($db);

$argv = $argv ?? [];
$statusOnly = in_array('--status', $argv, true);
$rollback = in_array('--rollback', $argv, true);

$steps = 1;
foreach ($argv as $arg) {
    if (strpos($arg, '--steps=') === 0) {
        $steps = (int) substr($arg, 8);
        $steps = $steps < 1 ? 1 : $steps;
        break;
    }
}

if ($statusOnly) {
    $status = $runner->status();
    echo "Migration status:\n";
    foreach ($status as $m) {
        $mark = $m['status'] === 'ran' ? '[x]' : '[ ]';
        echo "  $mark {$m['name']} ({$m['file']})\n";
    }
    exit(0);
}

if ($rollback) {
    $result = $runner->rollback($steps);
    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $err) {
            fwrite(STDERR, "Error: $err\n");
        }
        exit(1);
    }
    if ($result['rolled'] > 0) {
        echo "Rolled back {$result['rolled']} migration(s).\n";
    } else {
        echo "No migrations to roll back.\n";
    }
    exit(0);
}

$result = $runner->runPending();

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $err) {
        fwrite(STDERR, "Error: $err\n");
    }
    exit(1);
}

foreach ($result['warnings'] ?? [] as $warn) {
    echo "Note: $warn\n";
}

if ($result['ran'] > 0) {
    echo "Ran {$result['ran']} migration(s).\n";
} else {
    echo "No pending migrations.\n";
}
