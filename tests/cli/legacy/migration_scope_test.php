<?php
/**
 * Smoke test for Core\MigrationScope transactional DML helper.
 * Run: php tests/cli/migration_scope_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Core\Database;
use Core\MigrationScope;

$db = Database::getInstance();

$threw = false;
try {
    MigrationScope::transaction($db, static function (\PDO $db): void {
        $db->exec('CREATE TEMPORARY TABLE migration_scope_test (id INT PRIMARY KEY, val VARCHAR(10))');
        $db->exec("INSERT INTO migration_scope_test (id, val) VALUES (1, 'ok')");
        throw new \RuntimeException('rollback probe');
    });
} catch (\RuntimeException $e) {
    if ($e->getMessage() !== 'rollback probe') {
        throw $e;
    }
    $threw = true;
}
assert($threw);

MigrationScope::transaction($db, static function (\PDO $db): void {
    $db->exec('CREATE TEMPORARY TABLE migration_scope_test2 (id INT PRIMARY KEY)');
    $db->exec('INSERT INTO migration_scope_test2 (id) VALUES (1)');
    $count = (int) $db->query('SELECT COUNT(*) FROM migration_scope_test2')->fetchColumn();
    assert($count === 1);
});

echo "migration_scope_test: OK\n";
