#!/usr/bin/env php
<?php
/**
 * Remap grievance GRM channel / preferred language JSON IDs from global defaults
 * to project-specific option IDs (after initialize-project).
 *
 * When is Remap needed?
 * - NOT needed if the project still uses shared defaults (not Initialized).
 * - Needed after Initialize when that project already has grievances storing default IDs.
 * - NOT needed on a fresh install with no grievances yet (migrate + seed_grievance_options.php).
 * Same rule as In Progress Stages (cli/remap_progress_levels.php).
 *
 * Usage:
 *   php cli/remap_grm_language_options.php
 *   php cli/remap_grm_language_options.php --project=3
 *   php cli/remap_grm_language_options.php --only=grm
 *   php cli/remap_grm_language_options.php --only=language
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';

use App\Models\GrievanceGrmChannel;
use App\Models\GrievancePreferredLanguage;
use App\Models\Project;

$projectId = 0;
$only = 'both';
foreach ($argv as $arg) {
    if (preg_match('/^--project=(\d+)$/', $arg, $m)) {
        $projectId = (int) $m[1];
    }
    if (preg_match('/^--only=(grm|language|both)$/', $arg, $m)) {
        $only = $m[1];
    }
}

$doGrm = $only === 'both' || $only === 'grm';
$doLang = $only === 'both' || $only === 'language';

$ids = [];
if ($projectId > 0) {
    $ids = [$projectId];
} else {
    foreach (Project::all() as $p) {
        $id = (int) ($p->id ?? 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }
}

$totalGrm = 0;
$totalLang = 0;
foreach ($ids as $pid) {
    $g = 0;
    $l = 0;
    if ($doGrm) {
        $g = GrievanceGrmChannel::remapProjectReferences($pid);
        $totalGrm += $g;
    }
    if ($doLang) {
        $l = GrievancePreferredLanguage::remapProjectReferences($pid);
        $totalLang += $l;
    }
    echo "Project {$pid}: grm={$g} language={$l}\n";
}
echo "Total remapped grievance rows: grm={$totalGrm} language={$totalLang}\n";
