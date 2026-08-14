#!/usr/bin/env php
<?php
/**
 * Verifies full backup + restore round-trip preserves profile invitation fields.
 *
 * WARNING: restore replaces the entire database configured in config/database.php.
 * Run only against a local/dev database (not production).
 *
 * Usage (from project root):
 *   php tests/cli/backup_restore_invitation_roundtrip_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';
require_once $root . '/cli/backup_schema_helper.php';

use App\Models\Profile;
use Core\Database;
use Core\MigrationRunner;

function fail_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function run_cli(string $root, array $args): array
{
    $php = PHP_BINARY ?: 'php';
    $cmd = array_merge([$php], $args);
    $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $desc, $pipes, $root, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        return ['code' => 1, 'out' => '', 'err' => 'proc_open failed'];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    return ['code' => $code, 'out' => (string) $out, 'err' => (string) $err];
}

$db = Database::getInstance();
$runner = new MigrationRunner($db);
$migrateResult = $runner->runPending();
if (!empty($migrateResult['errors'])) {
    fail_test('migrate.php errors: ' . implode('; ', $migrateResult['errors']));
}

$liveSchema = paper_backup_schema_snapshot($db);
if (!$liveSchema['profiles_invitation_ready']) {
    fail_test('invitation columns missing — run php cli/migrate.php first');
}

$profileId = (int) $db->query(
    'SELECT id FROM profiles WHERE (is_deleted = 0 OR is_deleted IS NULL) ORDER BY id DESC LIMIT 1'
)->fetchColumn();
if ($profileId < 1) {
    fail_test('no active profile row to test — seed or create a profile first');
}

$markerRsvp = Profile::INVITATION_RSVP_ATTEND;
$markerOther = 'Backup roundtrip distribution other ' . date('His');
$corruptRsvp = Profile::INVITATION_RSVP_DID_NOT_ACCEPT;

$updated = Profile::update($profileId, [
    'invitation_rsvp' => $markerRsvp,
    'invitation_distribution_status' => Profile::INVITATION_DISTRIBUTION_STATUS_OTHER,
    'invitation_distribution_status_other' => $markerOther,
]);
if (!$updated) {
    fail_test("Profile::update failed for profile id {$profileId}");
}

$before = Profile::find($profileId);
if (!$before || (string) ($before->invitation_rsvp ?? '') !== $markerRsvp) {
    fail_test('marker invitation_rsvp not saved before backup');
}

$ts = date('Ymd-His');
$zipPath = $root . '/storage/backups/paper-e2e-roundtrip-' . $ts . '.zip';
$backupRun = run_cli($root, [
    $root . '/cli/backup.php',
    '--output=' . $zipPath,
    '--no-uploads',
]);
if ($backupRun['code'] !== 0 || !is_file($zipPath)) {
    fail_test("backup failed:\n" . $backupRun['out'] . "\n" . $backupRun['err']);
}

$corrupted = Profile::update($profileId, [
    'invitation_rsvp' => $corruptRsvp,
    'invitation_distribution_status_other' => 'CORRUPTED-BY-TEST',
]);
if (!$corrupted) {
    @unlink($zipPath);
    fail_test('could not corrupt profile before restore');
}

$restoreRun = run_cli($root, [
    $root . '/cli/restore.php',
    '--from=' . $zipPath,
    '--yes',
    '--no-uploads',
    '--skip-safety-backup',
]);
@unlink($zipPath);

if ($restoreRun['code'] !== 0) {
    fail_test("restore failed:\n" . $restoreRun['out'] . "\n" . $restoreRun['err']);
}

$after = Profile::find($profileId);
if (!$after) {
    fail_test("profile id {$profileId} missing after restore");
}

$rsvpAfter = (string) ($after->invitation_rsvp ?? '');
$otherAfter = (string) ($after->invitation_distribution_status_other ?? '');
if ($rsvpAfter !== $markerRsvp) {
    fail_test("invitation_rsvp mismatch: expected `{$markerRsvp}`, got `{$rsvpAfter}`");
}
if ($otherAfter !== $markerOther) {
    fail_test("invitation_distribution_status_other mismatch: expected `{$markerOther}`, got `{$otherAfter}`");
}

$schemaAfter = paper_backup_schema_snapshot($db);
if (!$schemaAfter['profiles_invitation_ready']) {
    fail_test('invitation columns missing after restore + migrate');
}

fwrite(STDOUT, "PASS: backup/restore preserved invitation fields for profile #{$profileId}.\n");
exit(0);
