<?php
/**
 * Unit test: MySQL option-file quoting for backup/restore credentials.
 * Passwords with #, quotes, spaces must be double-quoted so mysqldump does not get 1045
 * while PHP PDO still connects.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/cli/backup_sql_helper.php';

$cases = [
    'plain' => ['secret', '"secret"'],
    'hash_comment' => ['p@ss#word', '"p@ss#word"'],
    'spaces' => ['two words', '"two words"'],
    'double_quote' => ['say"hi', '"say\\"hi"'],
    'backslash' => ['a\\b', '"a\\\\b"'],
    'semicolon' => ['a;b', '"a;b"'],
    'empty' => ['', '""'],
];

foreach ($cases as $name => [$input, $expected]) {
    $got = paper_mysql_option_file_quote($input);
    if ($got !== $expected) {
        fwrite(STDERR, "FAIL {$name}: expected {$expected}, got {$got}\n");
        exit(1);
    }
}

$tmp = sys_get_temp_dir() . '/cms-mysql-cnf-' . bin2hex(random_bytes(4)) . '.cnf';
paper_write_mysql_client_cnf($tmp, 'localhost', 'lyor_cms', 'p@ss#w"ord', 'utf8mb4');
$body = (string) file_get_contents($tmp);
@unlink($tmp);

if (strpos($body, 'password="p@ss#w\\"ord"') === false) {
    fwrite(STDERR, "FAIL cnf password line missing or wrong:\n{$body}\n");
    exit(1);
}
if (strpos($body, 'default-character-set="utf8mb4"') === false) {
    fwrite(STDERR, "FAIL cnf charset line missing:\n{$body}\n");
    exit(1);
}
// Unquoted # must not appear as a comment truncator on the password line
foreach (explode("\n", $body) as $line) {
    if (stripos($line, 'password=') === 0 && preg_match('/^password=[^"]/', $line)) {
        fwrite(STDERR, "FAIL password value is not quoted: {$line}\n");
        exit(1);
    }
}

echo "backup_mysql_option_file_quote_test: OK\n";
