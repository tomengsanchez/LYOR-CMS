#!/usr/bin/env php
<?php
/**
 * Regression: --no-uploads must emit a single-dash flag from the UI helper
 * and produce a ZIP with no uploads/ entries.
 *
 * Usage: php tests/cli/backup_no_uploads_flag_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/cli/cli_script_args.php';

function fail_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

$fromUiKey = paper_cli_append_script_args(['php', 'backup.php'], ['--no-uploads' => true]);
if (!in_array('--no-uploads', $fromUiKey, true)) {
    fail_test('UI key --no-uploads must append --no-uploads');
}
if (in_array('----no-uploads', $fromUiKey, true)) {
    fail_test('UI key --no-uploads must not append ----no-uploads');
}

$fromBareKey = paper_cli_append_script_args(['php', 'backup.php'], ['no-uploads' => true]);
if (!in_array('--no-uploads', $fromBareKey, true)) {
    fail_test('key no-uploads => true must append --no-uploads');
}

$omitted = paper_cli_append_script_args(['php', 'backup.php'], ['no-uploads' => false]);
if (in_array('--no-uploads', $omitted, true)) {
    fail_test('key no-uploads => false must omit the flag');
}

if (!paper_cli_has_flag(['php', 'cli/backup.php', '--no-uploads'], 'no-uploads')) {
    fail_test('paper_cli_has_flag should detect --no-uploads');
}
if (paper_cli_has_flag(['php', 'cli/backup.php'], 'no-uploads')) {
    fail_test('paper_cli_has_flag should be false without the flag');
}
if (paper_cli_has_flag(['php', 'cli/backup.php', '--no-uploads=0'], 'no-uploads')) {
    fail_test('--no-uploads=0 should be treated as off');
}

$php = PHP_BINARY ?: 'php';
$zipPath = $root . '/storage/backups/paper-test-no-uploads-' . date('Ymd-His') . '-' . getmypid() . '.zip';
$desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$cmd = [$php, $root . '/cli/backup.php', '--no-uploads', '--output=' . $zipPath, '--reason=cli_test_no_uploads'];
$proc = proc_open($cmd, $desc, $pipes, $root, null, ['bypass_shell' => true]);
if (!is_resource($proc)) {
    fail_test('proc_open failed for backup.php');
}
fclose($pipes[0]);
$out = (string) stream_get_contents($pipes[1]);
$err = (string) stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$code = proc_close($proc);

$cleanup = static function () use ($zipPath): void {
    if (is_file($zipPath)) {
        @unlink($zipPath);
    }
};

if ($code !== 0 || !is_file($zipPath)) {
    $cleanup();
    fail_test("backup.php --no-uploads failed (exit {$code}):\n{$out}\n{$err}");
}

if (strpos($out, 'Uploads: skipped (--no-uploads).') === false) {
    $cleanup();
    fail_test("stdout should report uploads skipped:\n{$out}");
}
if (strpos($out, 'full database and uploads') !== false) {
    $cleanup();
    fail_test("stdout must not claim uploads were included:\n{$out}");
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    $cleanup();
    fail_test('could not open backup zip');
}

$names = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    if (!is_array($stat) || empty($stat['name'])) {
        continue;
    }
    $names[] = str_replace('\\', '/', (string) $stat['name']);
}

$manifestRaw = $zip->getFromName('manifest.json');
$zip->close();
$cleanup();

if (!in_array('manifest.json', $names, true) || !in_array('database.sql', $names, true)) {
    fail_test('zip must contain manifest.json and database.sql');
}

$uploadEntries = array_values(array_filter($names, static fn (string $n): bool => strpos($n, 'uploads/') === 0));
if ($uploadEntries !== []) {
    fail_test('--no-uploads zip still contains uploads/: ' . implode(', ', array_slice($uploadEntries, 0, 8)));
}

$manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
if (!is_array($manifest) || !array_key_exists('includes_uploads', $manifest)) {
    fail_test('manifest.json missing includes_uploads');
}
if ($manifest['includes_uploads'] !== false) {
    fail_test('manifest includes_uploads must be false when --no-uploads is set');
}

fwrite(STDOUT, "PASS: --no-uploads flag helper and ZIP contents.\n");
exit(0);
