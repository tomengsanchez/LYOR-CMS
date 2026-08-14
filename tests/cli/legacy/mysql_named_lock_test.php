<?php
/**
 * Smoke test for Core\MySqlNamedLock (GET_LOCK / RELEASE_LOCK).
 * Run: php tests/cli/mysql_named_lock_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Core\Database;
use Core\MySqlNamedLock;

$db = Database::getInstance();
$lockName = 'paper_test_lock_' . getmypid();

$value = MySqlNamedLock::run($db, $lockName, 5, static function (): int {
    return 42;
});
assert($value === 42);

$held = false;
try {
    MySqlNamedLock::run($db, $lockName, 1, static function () use (&$held): void {
        $held = true;
        usleep(200_000);
    });
} catch (\RuntimeException $e) {
    // second sequential acquire on same connection succeeds after first releases
}

assert($held);

$threw = false;
try {
    MySqlNamedLock::run($db, 'bad lock name!', 1, static fn () => null);
} catch (\InvalidArgumentException) {
    $threw = true;
}
assert($threw);

echo "mysql_named_lock_test: OK\n";
