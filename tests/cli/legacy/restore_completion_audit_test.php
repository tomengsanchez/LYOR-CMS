<?php
/**
 * Unit tests for restore data-completion audit helpers.
 *
 * Usage: php tests/cli/restore_completion_audit_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__, 3);
require_once $root . '/cli/backup_sql_helper.php';
require_once $root . '/cli/restore_completion_audit.php';

function assert_true(bool $cond, string $message): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$sampleSql = <<<'SQL'
CREATE TABLE `grievances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description_complaint` text,
  `grievance_case_number_unique` varchar(50) GENERATED ALWAYS AS (NULLIF(`description_complaint`,'')) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `grievances` VALUES
(1,'Request (include PAP) area.','Request (include PAP) area.'),
(2,'Other (second) case','Other (second) case'),
(3,'Plain text','Plain text');

INSERT INTO `profiles` (`id`,`full_name`) VALUES (10,'Ada'),(11,'Bob');
SQL;

$counts = paper_count_insert_rows_by_table_from_sql($sampleSql);
assert_true(($counts['grievances'] ?? 0) === 3, 'grievances INSERT count is 3 with parentheses in text');
assert_true(($counts['profiles'] ?? 0) === 2, 'profiles INSERT count is 2');

$tmpDir = sys_get_temp_dir() . '/paper-audit-test-' . getmypid();
@mkdir($tmpDir, 0755, true);
$zipA = $tmpDir . '/paper-backup-20260101-120000.zip';
$zipB = $tmpDir . '/paper-before-restore-20260101-130000.zip';
file_put_contents($zipA, 'a');
file_put_contents($zipB, 'b');
touch($zipA, time() - 100);
touch($zipB, time() - 10);
$latest = paper_find_latest_backup_zip($tmpDir, []);
assert_true($latest === $zipB, 'latest backup prefers newest mtime');
$latestExcl = paper_find_latest_backup_zip($tmpDir, [basename($zipB)]);
assert_true($latestExcl === $zipA, 'latest backup respects exclude list');
@unlink($zipA);
@unlink($zipB);
@rmdir($tmpDir);

$okManifest = [
    'app' => 'PAPeR',
    'database' => ['dbname' => 'paper_live'],
];
$vOk = paper_validate_restore_manifest($okManifest, 'paper_live', false);
assert_true($vOk['ok'] === true, 'matching PAPeR manifest is allowed');

$cmsManifest = [
    'app' => 'SimpleCMS',
    'database' => ['dbname' => 'cms_dev'],
];
$vCms = paper_validate_restore_manifest($cmsManifest, 'cms_dev', false);
assert_true($vCms['ok'] === true, 'matching SimpleCMS manifest is allowed');

$vDb = paper_validate_restore_manifest($okManifest, 'other_db', false);
assert_true($vDb['ok'] === false, 'dbname mismatch hard-fails without --force');
$vDbForce = paper_validate_restore_manifest($okManifest, 'other_db', true);
assert_true($vDbForce['ok'] === true && count($vDbForce['warnings']) > 0, 'dbname mismatch allowed with --force');

$vApp = paper_validate_restore_manifest(['app' => 'OtherApp', 'database' => ['dbname' => 'paper_live']], 'paper_live', false);
assert_true($vApp['ok'] === false, 'foreign app hard-fails');
$vAppForce = paper_validate_restore_manifest(['app' => 'OtherApp', 'database' => ['dbname' => 'paper_live']], 'paper_live', true);
assert_true($vAppForce['ok'] === true, 'foreign app allowed with --force');

$vMissing = paper_validate_restore_manifest(['database' => ['dbname' => 'paper_live']], 'paper_live', false);
assert_true($vMissing['ok'] === false, 'missing app hard-fails');

// Compare helper with a minimal PDO stub is awkward; exercise mismatch shaping via fake live counts path
// by using an in-memory expectation against paper_compare only when DB available — skip if no DB.
try {
    require_once $root . '/bootstrap.php';
    $pdo = \Core\Database::getInstance();
    $audit = paper_compare_dump_counts_to_live(['migrations' => -999999], $pdo);
    assert_true($audit['ok'] === false, 'impossible dump count fails audit');
    assert_true(count($audit['mismatches']) === 1, 'one mismatch reported');
} catch (\Throwable $e) {
    fwrite(STDOUT, "NOTE: skipped live DB compare assertion (" . $e->getMessage() . ")\n");
}

fwrite(STDOUT, "OK: restore_completion_audit_test\n");
exit(0);
