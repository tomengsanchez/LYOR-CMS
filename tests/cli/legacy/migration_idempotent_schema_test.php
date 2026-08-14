<?php
/**
 * MigrationRunner: idempotent schema conflicts must not run down().
 *
 * Usage: php tests/cli/migration_idempotent_schema_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';

use Core\Database;
use Core\MigrationRunner;

function assert_true(bool $cond, string $message): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$db = Database::getInstance();
$tmpDir = sys_get_temp_dir() . '/paper-mig-idem-' . getmypid();
@mkdir($tmpDir, 0755, true);

$migName = 'migration_test_idempotent_col_' . getmypid();
$migFile = $tmpDir . '/' . $migName . '.php';
$php = <<<PHP
<?php
return [
    'name' => '{$migName}',
    'up' => function (\\PDO \$db): void {
        \$db->exec("CREATE TABLE IF NOT EXISTS _paper_idem_test (
            id INT PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        \$db->exec("ALTER TABLE _paper_idem_test ADD COLUMN note VARCHAR(50) NOT NULL DEFAULT ''");
    },
    'down' => function (\\PDO \$db): void {
        \$db->exec('DROP TABLE IF EXISTS _paper_idem_test');
    },
];
PHP;
file_put_contents($migFile, $php);

$db->exec('DROP TABLE IF EXISTS _paper_idem_test');
$db->prepare('DELETE FROM migrations WHERE name = ?')->execute([$migName]);

$runner = new MigrationRunner($db, $tmpDir);
$first = $runner->runPending();
assert_true($first['errors'] === [], 'first run has no errors: ' . implode('; ', $first['errors'] ?? []));
assert_true(($first['ran'] ?? 0) >= 1, 'first run applied migration');

$db->prepare('DELETE FROM migrations WHERE name = ?')->execute([$migName]);

$second = $runner->runPending();
assert_true($second['errors'] === [], 'second run treats duplicate column as applied: ' . implode('; ', $second['errors'] ?? []));
assert_true(($second['ran'] ?? 0) >= 1, 'second run counted as ran');
assert_true(!empty($second['warnings']), 'second run emits warning about already present schema');

$tableStillThere = (bool) $db->query("SHOW TABLES LIKE '_paper_idem_test'")->fetchColumn();
assert_true($tableStillThere, 'down() must not have dropped the table on duplicate-column conflict');

$col = $db->query("SHOW COLUMNS FROM _paper_idem_test LIKE 'note'")->fetch();
assert_true((bool) $col, 'note column still present');

$logged = $db->prepare('SELECT COUNT(*) FROM migrations WHERE name = ?');
$logged->execute([$migName]);
assert_true((int) $logged->fetchColumn() === 1, 'migration recorded after idempotent conflict');

$db->exec('DROP TABLE IF EXISTS _paper_idem_test');
$db->prepare('DELETE FROM migrations WHERE name = ?')->execute([$migName]);
@unlink($migFile);
@rmdir($tmpDir);

fwrite(STDOUT, "OK: migration_idempotent_schema_test\n");
exit(0);
