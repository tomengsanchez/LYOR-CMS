<?php
/**
 * Smoke test: progress levels for API options are project-scoped (or defaults), not merged.
 * Run: php tests/cli/grievance_progress_level_options_api_test.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\GrievanceProgressLevel;

function assertNoMixedScope(array $levels, int $expectedProjectId): void
{
    $hasDefault = false;
    $hasProject = false;
    foreach ($levels as $row) {
        $pid = isset($row->project_id) ? (int) $row->project_id : 0;
        if ($pid <= 0) {
            $hasDefault = true;
        } elseif ($pid === $expectedProjectId) {
            $hasProject = true;
        } else {
            fwrite(STDERR, "FAIL: unexpected project_id {$pid} in scoped levels\n");
            exit(1);
        }
    }
    if ($hasDefault && $hasProject) {
        fwrite(STDERR, "FAIL: merged default + project levels (old API bug)\n");
        exit(1);
    }
}

$defaults = GrievanceProgressLevel::defaults();
if (empty($defaults)) {
    fwrite(STDERR, "FAIL: no default progress levels\n");
    exit(1);
}

$noProject = GrievanceProgressLevel::resolveForOptionsApi(0);
if ($noProject['scope'] !== 'default') {
    fwrite(STDERR, "FAIL: scope without project_id must be default\n");
    exit(1);
}
foreach ($noProject['levels'] as $row) {
    if (isset($row->project_id) && (int) $row->project_id > 0) {
        fwrite(STDERR, "FAIL: resolveForOptionsApi(0) must return defaults only\n");
        exit(1);
    }
}

$all = GrievanceProgressLevel::all();
$projectIds = [];
foreach ($all as $row) {
    $pid = isset($row->project_id) ? (int) $row->project_id : 0;
    if ($pid > 0) {
        $projectIds[$pid] = true;
    }
}

foreach (array_keys($projectIds) as $projectId) {
    $resolved = GrievanceProgressLevel::resolveForOptionsApi($projectId);
    $levels = $resolved['levels'];
    if (empty($levels)) {
        fwrite(STDERR, "FAIL: empty levels for project {$projectId}\n");
        exit(1);
    }
    assertNoMixedScope($levels, $projectId);
    $expectedScope = GrievanceProgressLevel::hasForProject($projectId) ? 'project' : 'default';
    if ($resolved['scope'] !== $expectedScope) {
        fwrite(STDERR, "FAIL: scope mismatch for project {$projectId}\n");
        exit(1);
    }
    if ($expectedScope === 'project') {
        foreach ($levels as $row) {
            if ((int) ($row->project_id ?? 0) !== $projectId) {
                fwrite(STDERR, "FAIL: project scope must only include project {$projectId} levels\n");
                exit(1);
            }
        }
    } else {
        foreach ($levels as $row) {
            if (isset($row->project_id) && (int) $row->project_id > 0) {
                fwrite(STDERR, "FAIL: uninitialized project must use defaults only\n");
                exit(1);
            }
        }
    }
}

$forProjectOrDefault = GrievanceProgressLevel::forProjectOrDefault(array_key_first($projectIds) ?: 1);
$resolvedFirst = GrievanceProgressLevel::resolveForOptionsApi(array_key_first($projectIds) ?: 1);
if (count($forProjectOrDefault) !== count($resolvedFirst['levels'])) {
    fwrite(STDERR, "FAIL: resolveForOptionsApi levels count must match forProjectOrDefault\n");
    exit(1);
}

echo 'OK: progress level options API scope (' . count($projectIds) . " project(s) checked)\n";
