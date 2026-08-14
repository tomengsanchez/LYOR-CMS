#!/usr/bin/env php
<?php
/**
 * Export one Library project as a ZIP of CSV files (core data + audit/history + user activity).
 *
 * Usage:
 *   php cli/export_project_csv.php --project-id=123
 *   php cli/export_project_csv.php --project-id=123 --out=D:\exports\proj-123.zip
 *   php cli/export_project_csv.php --project-id=123 --redact
 *
 * Default output: storage/project-csv-exports/paper-project-{name-slug}[-redacted]-YYYYmmdd-HHMMSS.zip
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';
require_once $root . '/cli/cli_script_args.php';

use App\Models\Project;
use App\ProjectCsvExporter;

$argv = $argv ?? [];
$projectId = 0;
$out = '';
foreach ($argv as $arg) {
    if (!is_string($arg)) {
        continue;
    }
    if (strpos($arg, '--project-id=') === 0) {
        $projectId = (int) substr($arg, strlen('--project-id='));
    } elseif (strpos($arg, '--project_id=') === 0) {
        $projectId = (int) substr($arg, strlen('--project_id='));
    } elseif (strpos($arg, '--out=') === 0) {
        $out = trim(substr($arg, strlen('--out=')));
    } elseif (strpos($arg, '--output=') === 0) {
        $out = trim(substr($arg, strlen('--output=')));
    }
}
$redact = paper_cli_has_flag($argv, 'redact') || paper_cli_has_flag($argv, 'redact-pii');

if ($projectId <= 0) {
    fwrite(STDERR, "Usage: php cli/export_project_csv.php --project-id=N [--out=path.zip] [--redact]\n");
    exit(1);
}

if ($out === '') {
    $dir = $root . '/storage/project-csv-exports';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        fwrite(STDERR, "Unable to create $dir\n");
        exit(1);
    }
    $project = Project::find($projectId);
    $projectName = $project !== null ? (string) ($project->name ?? '') : '';
    $out = $dir . '/' . ProjectCsvExporter::defaultZipBasename($projectName, $redact);
}

@set_time_limit(0);
$exporter = new ProjectCsvExporter();
$result = $exporter->export($projectId, $out, $redact);
if (empty($result['ok'])) {
    fwrite(STDERR, ($result['message'] ?? 'Export failed.') . "\n");
    exit(1);
}

echo "Project CSV export OK\n";
echo 'ZIP: ' . ($result['zip'] ?? $out) . "\n";
echo 'PII redacted: ' . (!empty($result['pii_redacted']) ? 'yes' : 'no') . "\n";
$counts = $result['counts'] ?? [];
if (is_array($counts) && $counts !== []) {
    echo "Row counts:\n";
    foreach ($counts as $file => $n) {
        echo '  ' . $file . ': ' . (int) $n . "\n";
    }
}
exit(0);
