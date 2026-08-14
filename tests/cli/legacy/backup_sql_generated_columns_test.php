#!/usr/bin/env php
<?php
/**
 * Unit tests for generated-column aware SQL dump helpers.
 *
 * Usage: php tests/cli/backup_sql_generated_columns_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/cli/backup_sql_helper.php';

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
  `grievance_case_number` varchar(50) DEFAULT NULL,
  `grievance_case_number_unique` varchar(50) GENERATED ALWAYS AS (NULLIF(`grievance_case_number`,'')) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `grievances` (`id`,`grievance_case_number`,`grievance_case_number_unique`) VALUES (1,'CASE-001','CASE-001'),(2,'CASE-002','CASE-002');
SQL;

$generated = paper_parse_generated_columns_from_sql_dump($sampleSql);
assert_true(
    isset($generated['grievances']) && in_array('grievance_case_number_unique', $generated['grievances'], true),
    'parse generated columns from CREATE TABLE'
);

$sanitized = paper_sanitize_sql_dump_contents($sampleSql);
assert_true(
    preg_match('/INSERT INTO `grievances`[^;]*grievance_case_number_unique/i', $sanitized) !== 1,
    'sanitizer removes generated column from INSERT'
);
assert_true(
    strpos($sanitized, "INSERT INTO `grievances` (`id`,`grievance_case_number`) VALUES (1,'CASE-001'),(2,'CASE-002');") !== false,
    'sanitizer keeps base columns and values'
);

$noColumnListSql = <<<'SQL'
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `control_number` varchar(50) DEFAULT NULL,
  `control_number_unique` varchar(50) GENERATED ALWAYS AS (NULLIF(`control_number`,'')) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `profiles` VALUES (5,'CTRL-5','CTRL-5');
SQL;

$multiLineSql = <<<'SQL'
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `control_number` varchar(50) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `control_number_unique` varchar(50) GENERATED ALWAYS AS (NULLIF(`control_number`,'')) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `profiles` VALUES
(1,'CN0001',0,'CN0001'),
(2,'CN0009',0,'CN0009');
SQL;

$sanitizedNoCols = paper_sanitize_sql_dump_contents($noColumnListSql);
assert_true(
    preg_match('/INSERT INTO `profiles`[^;]*control_number_unique/i', $sanitizedNoCols) !== 1,
    'sanitizer handles INSERT without explicit column list'
);
assert_true(
    strpos($sanitizedNoCols, "INSERT INTO `profiles` (`id`,`control_number`) VALUES (5,'CTRL-5');") !== false,
    'sanitizer adds column list for INSERT without column list'
);

$sanitizedMulti = paper_sanitize_sql_dump_contents($multiLineSql);
assert_true(
    strpos($sanitizedMulti, ",'CN0001')") === false && strpos($sanitizedMulti, ",'CN0009')") === false,
    'sanitizer handles mysqldump multi-line INSERT without column list'
);
assert_true(
    strpos($sanitizedMulti, "INSERT INTO `profiles` (`id`,`control_number`,`is_deleted`) VALUES (1,'CN0001',0),(2,'CN0009',0);") !== false,
    'sanitizer adds explicit column list for multi-line INSERT without column list'
);

$tmp = sys_get_temp_dir() . '/paper-sql-sanitize-' . getmypid() . '.sql';
file_put_contents($tmp, $sampleSql);
$result = paper_sanitize_sql_dump_file($tmp);
assert_true($result['changed'] === true, 'file sanitizer reports change');
assert_true(($result['stripped_generated'] ?? false) === true, 'file sanitizer reports generated strip');
assert_true(($result['stripped_sandbox'] ?? false) === false, 'sample without sandbox leaves stripped_sandbox false');
$onDisk = file_get_contents($tmp);
assert_true(
    is_string($onDisk)
        && preg_match('/INSERT INTO `grievances`[^;]*grievance_case_number_unique/i', $onDisk) !== 1,
    'file sanitizer writes cleaned SQL'
);
@unlink($tmp);

$sandboxSql = "/*M!999999\\- enable the sandbox mode */ \n-- MariaDB dump\n"
    . "CREATE TABLE `t` (`id` int NOT NULL) ENGINE=InnoDB;\n"
    . "INSERT INTO `t` (`id`) VALUES (1);\n";
$sandboxStripped = paper_strip_mariadb_sandbox_banner($sandboxSql);
assert_true(
    strpos($sandboxStripped, 'enable the sandbox mode') === false,
    'strip removes MariaDB sandbox banner (/*M! form)'
);
assert_true(
    strpos($sandboxStripped, '-- MariaDB dump') !== false,
    'strip keeps following dump content'
);

$altSandbox = "/*!999999\\- enable the sandbox mode */\nSET NAMES utf8mb4;\n";
assert_true(
    strpos(paper_strip_mariadb_sandbox_banner($altSandbox), 'enable the sandbox mode') === false,
    'strip removes /*!999999 sandbox banner'
);

$sandboxWithGenerated = "/*M!999999\\- enable the sandbox mode */\n" . $sampleSql;
$both = paper_sanitize_sql_dump_contents($sandboxWithGenerated);
assert_true(strpos($both, 'enable the sandbox mode') === false, 'contents sanitize strips sandbox');
assert_true(
    preg_match('/INSERT INTO `grievances`[^;]*grievance_case_number_unique/i', $both) !== 1,
    'contents sanitize still strips generated columns with sandbox present'
);

$tmpSandbox = sys_get_temp_dir() . '/paper-sql-sandbox-' . getmypid() . '.sql';
file_put_contents($tmpSandbox, $sandboxSql);
$sandboxFile = paper_sanitize_sql_dump_file($tmpSandbox);
assert_true($sandboxFile['changed'] === true, 'sandbox-only file reports changed');
assert_true(($sandboxFile['stripped_sandbox'] ?? false) === true, 'sandbox-only file reports stripped_sandbox');
assert_true(($sandboxFile['stripped_generated'] ?? false) === false, 'sandbox-only file does not claim generated strip');
$onDiskSandbox = file_get_contents($tmpSandbox);
assert_true(
    is_string($onDiskSandbox) && strpos($onDiskSandbox, 'enable the sandbox mode') === false,
    'file sanitizer writes dump without sandbox banner'
);
assert_true(
    is_string($onDiskSandbox) && strpos($onDiskSandbox, 'INSERT INTO `t`') !== false,
    'file sanitizer keeps INSERT after sandbox strip'
);
@unlink($tmpSandbox);

assert_true(paper_column_extra_is_generated('STORED GENERATED'), 'detect generated column extra');
assert_true(!paper_column_extra_is_generated('auto_increment'), 'non-generated extra ignored');

$parenInStringSql = <<<'SQL'
CREATE TABLE `grievances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description_complaint` text,
  `grievance_case_number_unique` varchar(50) GENERATED ALWAYS AS (NULLIF(`description_complaint`,'')) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `grievances` VALUES
(1,'Request (include PAP) area.'),
(2,'Other (second) case'),
(3,'Plain text');
SQL;

$sanitizedParens = paper_sanitize_sql_dump_contents($parenInStringSql);
assert_true(
    preg_match('/INSERT INTO `grievances`[^;]*grievance_case_number_unique/i', $sanitizedParens) !== 1,
    'paren-in-string sanitizer removes generated column'
);
assert_true(
    strpos($sanitizedParens, "INSERT INTO `grievances` (`id`,`description_complaint`) VALUES (1,'Request (include PAP) area.'),(2,'Other (second) case'),(3,'Plain text');") !== false,
    'paren-in-string keeps all INSERT tuples (parentheses inside quotes)'
);

$doubledQuoteSql = <<<'SQL'
CREATE TABLE `grievances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description_complaint` text,
  `grievance_case_number_unique` varchar(50) GENERATED ALWAYS AS (NULLIF(`description_complaint`,'')) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `grievances` VALUES (1,'it''s (ok)'),(2,'next');
SQL;
$sanitizedDoubled = paper_sanitize_sql_dump_contents($doubledQuoteSql);
assert_true(
    strpos($sanitizedDoubled, "INSERT INTO `grievances` (`id`,`description_complaint`) VALUES (1,'it''s (ok)'),(2,'next');") !== false,
    'doubled-quote strings with parentheses keep both tuples'
);

fwrite(STDOUT, "OK: backup_sql_generated_columns_test\n");
exit(0);
