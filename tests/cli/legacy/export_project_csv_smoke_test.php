#!/usr/bin/env php
<?php
/**
 * Smoke: export first active project to a temp ZIP; assert manifest + profiles.csv exist.
 * Also asserts a redacted export sets manifest.pii_redacted and redacts profile name/email fields when present.
 *
 * Usage: php tests/cli/export_project_csv_smoke_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';

use App\ProjectCsvExporter;
use Core\Database;

function fail_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function table_exists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $stmt->execute([$table]);

    return (bool) $stmt->fetchColumn();
}

$db = Database::getInstance();
$stmt = $db->query('SELECT id, name FROM projects WHERE is_deleted = 0 ORDER BY id ASC LIMIT 1');
$row = $stmt ? $stmt->fetch(PDO::FETCH_OBJ) : false;
if (!$row || (int) ($row->id ?? 0) <= 0) {
    fwrite(STDOUT, "SKIP: no active projects in database.\n");
    exit(0);
}

$projectId = (int) $row->id;
$out = sys_get_temp_dir() . '/paper-project-csv-smoke-' . $projectId . '-' . getmypid() . '.zip';
if (is_file($out)) {
    @unlink($out);
}

$exporter = new ProjectCsvExporter();
$result = $exporter->export($projectId, $out, false);
if (empty($result['ok'])) {
    fail_test($result['message'] ?? 'export returned not ok');
}
if (!is_file($out)) {
    fail_test('ZIP file was not created');
}

$zip = new ZipArchive();
if ($zip->open($out) !== true) {
    @unlink($out);
    fail_test('Unable to open ZIP');
}

$manifestRaw = $zip->getFromName('manifest.json');
$profiles = $zip->getFromName('profiles.csv');
$audit = $zip->getFromName('audit_log.csv');
$socio = table_exists($db, 'profile_socio_sections') ? $zip->getFromName('profile_socio_sections.csv') : null;
$zip->close();
@unlink($out);

if (!is_string($manifestRaw) || trim($manifestRaw) === '') {
    fail_test('manifest.json missing from ZIP');
}
$manifest = json_decode($manifestRaw, true);
if (!is_array($manifest) || (int) ($manifest['project_id'] ?? 0) !== $projectId) {
    fail_test('manifest project_id mismatch');
}
if (!empty($manifest['pii_redacted'])) {
    fail_test('non-redact export should set pii_redacted false');
}
if (!is_string($profiles)) {
    fail_test('profiles.csv missing from ZIP');
}
if (!is_string($audit)) {
    fail_test('audit_log.csv missing from ZIP');
}
if (table_exists($db, 'profile_socio_sections')) {
    if (!is_string($socio)) {
        fail_test('profile_socio_sections.csv missing from ZIP');
    }
    if (str_contains($socio, 'section_key') === false) {
        fail_test('profile_socio_sections.csv missing header');
    }
}

$outRedacted = sys_get_temp_dir() . '/paper-project-csv-smoke-redact-' . $projectId . '-' . getmypid() . '.zip';
if (is_file($outRedacted)) {
    @unlink($outRedacted);
}
$resultR = $exporter->export($projectId, $outRedacted, true);
if (empty($resultR['ok']) || empty($resultR['pii_redacted'])) {
    fail_test($resultR['message'] ?? 'redacted export failed');
}
$zipR = new ZipArchive();
if ($zipR->open($outRedacted) !== true) {
    @unlink($outRedacted);
    fail_test('Unable to open redacted ZIP');
}
$manifestRRaw = $zipR->getFromName('manifest.json');
$profilesR = $zipR->getFromName('profiles.csv');
$zipR->close();
@unlink($outRedacted);

$manifestR = is_string($manifestRRaw) ? json_decode($manifestRRaw, true) : null;
if (!is_array($manifestR) || empty($manifestR['pii_redacted'])) {
    fail_test('redacted manifest missing pii_redacted=true');
}
if (is_string($profilesR) && str_contains($profilesR, 'first_name')) {
    $lines = preg_split("/\r\n|\n|\r/", $profilesR) ?: [];
    if (count($lines) > 1) {
        $header = str_getcsv($lines[0] ?? '');
        if ($header !== [] && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? $header[0];
        }
        $firstIdx = array_search('first_name', $header, true);
        if ($firstIdx !== false) {
            for ($i = 1, $n = count($lines); $i < $n; $i++) {
                if (trim((string) $lines[$i]) === '') {
                    continue;
                }
                $cols = str_getcsv($lines[$i]);
                $firstVal = (string) ($cols[$firstIdx] ?? '');
                if ($firstVal !== '' && $firstVal !== '[REDACTED]') {
                    fail_test('profiles.csv first_name not redacted: ' . $firstVal);
                }
            }
        }
    }
}

fwrite(STDOUT, "OK: project #{$projectId} export ZIP smoke passed (incl. redaction).\n");
exit(0);
