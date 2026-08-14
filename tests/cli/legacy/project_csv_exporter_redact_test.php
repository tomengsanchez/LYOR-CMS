#!/usr/bin/env php
<?php
/**
 * Unit checks for ProjectCsvExporter PII redaction (SES household member names, etc.).
 *
 * Usage: php tests/cli/project_csv_exporter_redact_test.php
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\ProjectCsvExporter;

function fail_redact_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function assertPiiColumn(ProjectCsvExporter $exporter, ReflectionMethod $method, string $column, bool $expected, string $label): void
{
    $actual = (bool) $method->invoke($exporter, strtolower($column));
    if ($actual !== $expected) {
        fail_redact_test(sprintf('%s: column "%s" expected %s got %s', $label, $column, $expected ? 'PII' : 'non-PII', $actual ? 'PII' : 'non-PII'));
    }
}

$exporter = new ProjectCsvExporter();
$ref = new ReflectionClass($exporter);
$isPii = $ref->getMethod('isPiiColumn');
$isPii->setAccessible(true);
$redactJson = $ref->getMethod('redactJsonPayload');
$redactJson->setAccessible(true);

assertPiiColumn($exporter, $isPii, 'F1.1.4 Name', true, 'SES household member name');
assertPiiColumn($exporter, $isPii, 'Q2.1.1 Name', true, 'SES non-res household member');
assertPiiColumn($exporter, $isPii, 'A1. Name', true, 'SES PAP name');
assertPiiColumn($exporter, $isPii, 'A4. Birthdate', true, 'SES birthdate');
assertPiiColumn($exporter, $isPii, 'F1.1.6 Birthday', true, 'SES member birthday');
assertPiiColumn($exporter, $isPii, 'Project Name', false, 'SES project metadata');
assertPiiColumn($exporter, $isPii, '1. Timber - Name', false, 'SES crop name');
assertPiiColumn($exporter, $isPii, 'Employer/Business Name', false, 'SES business name');
assertPiiColumn($exporter, $isPii, 'Contact person', true, 'SES contact person');
assertPiiColumn($exporter, $isPii, 'gps_latitude', false, 'GPS coordinate');

$rowsJson = json_encode([
    [
        'Project Name' => 'SMAI MALEX',
        'F1.1.4 Name' => 'Juan Dela Cruz',
        'F1.1.6 Birthday' => '1990-01-15',
        '1. Timber - Name' => 'Mahogany',
        'EntryID' => '1',
    ],
], JSON_UNESCAPED_UNICODE);
$redacted = $redactJson->invoke($exporter, $rowsJson);
$decoded = json_decode((string) $redacted, true);
if (!is_array($decoded) || !isset($decoded[0]) || !is_array($decoded[0])) {
    fail_redact_test('redactJsonPayload did not return expected array');
}
$row = $decoded[0];
if (($row['Project Name'] ?? '') !== 'SMAI MALEX') {
    fail_redact_test('Project Name should not be redacted');
}
if (($row['F1.1.4 Name'] ?? '') !== '[REDACTED]') {
    fail_redact_test('F1.1.4 Name should be redacted');
}
if (($row['F1.1.6 Birthday'] ?? '') !== '[REDACTED]') {
    fail_redact_test('F1.1.6 Birthday should be redacted');
}
if (($row['1. Timber - Name'] ?? '') !== 'Mahogany') {
    fail_redact_test('Timber name should not be redacted');
}
if (($row['EntryID'] ?? '') !== '1') {
    fail_redact_test('EntryID should not be redacted');
}

fwrite(STDOUT, "OK: ProjectCsvExporter redaction rules passed.\n");
exit(0);
