#!/usr/bin/env php
<?php
/**
 * Regression: mysqldump -- comments must not swallow DROP TABLE (PDO restore importer).
 *
 * Usage: php tests/cli/restore_sql_comment_drop_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/cli/restore_sql_helper.php';

function fail_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

$sample = <<<'SQL'
/*M!999999\- enable the sandbox mode */
-- MariaDB dump
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

--
-- Table structure for table `api_2fa_challenges`
--

DROP TABLE IF EXISTS `api_2fa_challenges`;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_2fa_challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data
--

INSERT INTO `api_2fa_challenges` (`id`,`name`) VALUES (1,'a;b');
SQL;

$stmts = paper_split_sql_statements($sample);
$drops = array_values(array_filter(
    $stmts,
    static fn (string $s): bool => stripos($s, 'DROP TABLE') !== false
));
$creates = array_values(array_filter(
    $stmts,
    static fn (string $s): bool => stripos($s, 'CREATE TABLE') !== false
));
$inserts = array_values(array_filter(
    $stmts,
    static fn (string $s): bool => stripos($s, 'INSERT INTO') !== false
));

if (count($drops) !== 1) {
    fail_test('expected 1 DROP TABLE, got ' . count($drops) . ' (total stmts=' . count($stmts) . ')');
}
if (stripos($drops[0], 'api_2fa_challenges') === false) {
    fail_test('DROP TABLE missing table name: ' . $drops[0]);
}
if (strpos(ltrim($drops[0]), '--') === 0) {
    fail_test('DROP still starts with comment: ' . $drops[0]);
}
if (count($creates) !== 1) {
    fail_test('expected 1 CREATE TABLE, got ' . count($creates));
}
if (count($inserts) !== 1) {
    fail_test('expected 1 INSERT, got ' . count($inserts));
}
if (strpos($inserts[0], "VALUES (1,'a;b')") === false) {
    fail_test('INSERT semicolon inside string was split incorrectly: ' . $inserts[0]);
}

// Optional: if fromlive.zip is present, assert all DROP lines become executable statements
$zipPath = $root . '/storage/backups/fromlive.zip';
if (is_file($zipPath) && class_exists('ZipArchive')) {
    $z = new ZipArchive();
    if ($z->open($zipPath) === true) {
        $sql = $z->getFromName('database.sql');
        $z->close();
        if (is_string($sql) && $sql !== '') {
            $liveStmts = paper_split_sql_statements($sql);
            $liveDrops = 0;
            foreach ($liveStmts as $s) {
                if (stripos($s, 'DROP TABLE') !== false) {
                    $liveDrops++;
                }
            }
            if ($liveDrops < 1) {
                fail_test('fromlive.zip: expected DROP TABLE statements after split, got 0');
            }
            fwrite(STDOUT, "fromlive.zip: DROP TABLE statements after split = {$liveDrops}\n");
        }
    }
}

fwrite(STDOUT, "OK: restore SQL comment/DROP splitter regression passed (" . count($stmts) . " statements).\n");
exit(0);
