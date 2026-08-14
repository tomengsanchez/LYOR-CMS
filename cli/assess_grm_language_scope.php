#!/usr/bin/env php
<?php
/**
 * Assess impact of making GRM Channel + Preferred Language required and per-project.
 * Read-only. Usage: php cli/assess_grm_language_scope.php
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';

use Core\Database;

$db = Database::getInstance();
$dbConfig = require $root . '/config/database.php';

function jsonIdsEmpty(?string $raw): bool
{
    if ($raw === null || trim($raw) === '' || trim($raw) === '[]' || trim($raw) === 'null') {
        return true;
    }
    $d = json_decode($raw, true);
    if (!is_array($d)) {
        return true;
    }
    $ids = array_values(array_filter(array_map('intval', $d), static fn ($id) => $id > 0));
    return $ids === [];
}

/** @return list<int> */
function jsonIds(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $d = json_decode($raw, true);
    if (!is_array($d)) {
        return [];
    }
    return array_values(array_filter(array_map('intval', $d), static fn ($id) => $id > 0));
}

echo '=== DB: ' . ($dbConfig['dbname'] ?? '?') . " ===\n";
echo 'Host: ' . ($dbConfig['host'] ?? '?') . "\n";
echo 'Assessed at: ' . date('Y-m-d H:i:s') . "\n\n";

$total = (int) $db->query('SELECT COUNT(*) FROM grievances')->fetchColumn();
$active = (int) $db->query('SELECT COUNT(*) FROM grievances WHERE is_deleted = 0')->fetchColumn();
$deleted = (int) $db->query('SELECT COUNT(*) FROM grievances WHERE is_deleted = 1')->fetchColumn();
echo "Grievances: total={$total} active={$active} soft_deleted={$deleted}\n\n";

foreach ([
    'grievance_grm_channels' => 'GRM channels',
    'grievance_preferred_languages' => 'Preferred languages',
] as $table => $label) {
    $all = (int) $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $live = (int) $db->query("SELECT COUNT(*) FROM {$table} WHERE is_deleted = 0")->fetchColumn();
    echo "{$label}: total={$all} active={$live} soft_deleted=" . ($all - $live) . "\n";
    $rows = $db->query("SELECT id, name, is_deleted FROM {$table} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $flag = ((int) $r['is_deleted'] === 1) ? ' [DELETED]' : '';
        echo "  id={$r['id']} {$r['name']}{$flag}\n";
    }
    $col = $db->query("SHOW COLUMNS FROM {$table} LIKE 'project_id'")->fetch(PDO::FETCH_ASSOC);
    echo "  project_id column: " . ($col ? 'EXISTS' : 'MISSING (still global)') . "\n\n";
}

$nullProj = (int) $db->query(
    'SELECT COUNT(*) FROM grievances WHERE is_deleted = 0 AND (project_id IS NULL OR project_id = 0)'
)->fetchColumn();
$withProj = (int) $db->query(
    'SELECT COUNT(*) FROM grievances WHERE is_deleted = 0 AND project_id IS NOT NULL AND project_id > 0'
)->fetchColumn();
echo "Active with project_id: {$withProj}\n";
echo "Active WITHOUT project_id: {$nullProj}\n\n";

$grmLive = array_map('intval', $db->query(
    'SELECT id FROM grievance_grm_channels WHERE is_deleted = 0'
)->fetchAll(PDO::FETCH_COLUMN));
$langLive = array_map('intval', $db->query(
    'SELECT id FROM grievance_preferred_languages WHERE is_deleted = 0'
)->fetchAll(PDO::FETCH_COLUMN));
$grmSet = array_flip($grmLive);
$langSet = array_flip($langLive);

$stmt = $db->query("
    SELECT g.id, g.project_id, g.is_deleted, g.grm_channel_ids, g.preferred_language_ids,
           COALESCE(p.name, '(no project)') AS project_name, g.status, g.grievance_case_number
    FROM grievances g
    LEFT JOIN projects p ON p.id = g.project_id
    ORDER BY g.project_id IS NULL DESC, g.project_id, g.id
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$emptyGrmActive = 0;
$emptyLangActive = 0;
$emptyBothActive = 0;
$emptyGrmAll = 0;
$emptyLangAll = 0;
$orphanGrm = [];
$orphanLang = [];
$usedGrm = [];
$usedLang = [];
$byProject = [];
$sampleEmpty = [];

foreach ($rows as $r) {
    $pid = (int) ($r['project_id'] ?? 0);
    $pname = (string) $r['project_name'];
    $isActive = ((int) $r['is_deleted'] === 0);
    if (!isset($byProject[$pid])) {
        $byProject[$pid] = [
            'name' => $pname,
            'active' => 0,
            'empty_grm' => 0,
            'empty_lang' => 0,
            'orphan_grm' => 0,
            'orphan_lang' => 0,
        ];
    }
    if ($isActive) {
        $byProject[$pid]['active']++;
    }

    $grmEmpty = jsonIdsEmpty($r['grm_channel_ids'] ?? null);
    $langEmpty = jsonIdsEmpty($r['preferred_language_ids'] ?? null);
    if ($grmEmpty) {
        $emptyGrmAll++;
        if ($isActive) {
            $emptyGrmActive++;
            $byProject[$pid]['empty_grm']++;
        }
    }
    if ($langEmpty) {
        $emptyLangAll++;
        if ($isActive) {
            $emptyLangActive++;
            $byProject[$pid]['empty_lang']++;
        }
    }
    if ($isActive && $grmEmpty && $langEmpty) {
        $emptyBothActive++;
    }
    if ($isActive && ($grmEmpty || $langEmpty) && count($sampleEmpty) < 15) {
        $sampleEmpty[] = sprintf(
            'id=%d case=%s project=%s status=%s empty_grm=%s empty_lang=%s',
            (int) $r['id'],
            (string) ($r['grievance_case_number'] ?: '-'),
            $pname,
            (string) ($r['status'] ?? ''),
            $grmEmpty ? 'Y' : 'N',
            $langEmpty ? 'Y' : 'N'
        );
    }

    foreach (jsonIds($r['grm_channel_ids'] ?? null) as $id) {
        if ($isActive) {
            $usedGrm[$id] = ($usedGrm[$id] ?? 0) + 1;
            if (!isset($grmSet[$id])) {
                $orphanGrm[$id] = ($orphanGrm[$id] ?? 0) + 1;
                $byProject[$pid]['orphan_grm']++;
            }
        }
    }
    foreach (jsonIds($r['preferred_language_ids'] ?? null) as $id) {
        if ($isActive) {
            $usedLang[$id] = ($usedLang[$id] ?? 0) + 1;
            if (!isset($langSet[$id])) {
                $orphanLang[$id] = ($orphanLang[$id] ?? 0) + 1;
                $byProject[$pid]['orphan_lang']++;
            }
        }
    }
}

echo "=== Required-field risk (active grievances) ===\n";
echo "Empty GRM channel: {$emptyGrmActive} / {$active}\n";
echo "Empty Preferred language: {$emptyLangActive} / {$active}\n";
echo "Empty BOTH: {$emptyBothActive}\n";
echo "(Including soft-deleted: empty GRM={$emptyGrmAll}, empty Lang={$emptyLangAll})\n\n";

if ($sampleEmpty !== []) {
    echo "Sample incomplete active rows (max 15):\n";
    foreach ($sampleEmpty as $line) {
        echo "  {$line}\n";
    }
    echo "\n";
}

echo "=== Orphan IDs (active → missing/soft-deleted options) ===\n";
echo 'Orphan GRM channel ids: ' . (empty($orphanGrm) ? 'none' : json_encode($orphanGrm, JSON_UNESCAPED_UNICODE)) . "\n";
echo 'Orphan language ids: ' . (empty($orphanLang) ? 'none' : json_encode($orphanLang, JSON_UNESCAPED_UNICODE)) . "\n\n";

ksort($usedGrm);
ksort($usedLang);
echo "=== Option usage counts (active grievances) ===\n";
echo 'GRM channels used: ' . json_encode($usedGrm, JSON_UNESCAPED_UNICODE) . "\n";
echo 'Languages used: ' . json_encode($usedLang, JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== By project (active) ===\n";
ksort($byProject);
foreach ($byProject as $pid => $info) {
    if ($info['active'] === 0 && $info['empty_grm'] === 0 && $info['empty_lang'] === 0) {
        continue;
    }
    echo sprintf(
        "project_id=%d (%s): active=%d empty_grm=%d empty_lang=%d orphan_grm_refs=%d orphan_lang_refs=%d\n",
        $pid,
        $info['name'],
        $info['active'],
        $info['empty_grm'],
        $info['empty_lang'],
        $info['orphan_grm'],
        $info['orphan_lang']
    );
}

echo "\n=== Projects catalog ===\n";
$projects = $db->query('SELECT id, name, is_deleted FROM projects ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
foreach ($projects as $p) {
    $flag = ((int) $p['is_deleted'] === 1) ? ' [DELETED]' : '';
    echo "  id={$p['id']} {$p['name']}{$flag}\n";
}

echo "\n=== Progress levels (existing per-project pattern) ===\n";
$plNull = (int) $db->query(
    'SELECT COUNT(*) FROM grievance_progress_levels WHERE project_id IS NULL AND is_deleted = 0'
)->fetchColumn();
$plProj = (int) $db->query(
    'SELECT COUNT(*) FROM grievance_progress_levels WHERE project_id IS NOT NULL AND is_deleted = 0'
)->fetchColumn();
$plProjects = (int) $db->query(
    'SELECT COUNT(DISTINCT project_id) FROM grievance_progress_levels WHERE project_id IS NOT NULL AND is_deleted = 0'
)->fetchColumn();
echo "Progress levels: global_defaults={$plNull} project_scoped_rows={$plProj} projects_initialized={$plProjects}\n";

echo "\n=== Verdict helpers ===\n";
$riskRequired = $emptyGrmActive + $emptyLangActive;
$riskOrphan = array_sum($orphanGrm) + array_sum($orphanLang);
echo "Rows that would fail stricter required validation on edit/save: empty_grm={$emptyGrmActive}, empty_lang={$emptyLangActive}\n";
echo "Rows with orphan option refs (edit picker mismatch risk if globals removed): {$riskOrphan}\n";
echo "Safe scoping (keep globals as defaults, no ID remap): " . ($riskOrphan === 0 ? 'YES — no orphan refs' : 'NEEDS CARE — orphan refs exist') . "\n";
echo "Note: this assesses the configured local DB ({$dbConfig['dbname']}), not necessarily live production.\n";
