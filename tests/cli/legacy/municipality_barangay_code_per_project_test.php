<?php
/**
 * Municipality and barangay codes are unique per project, not globally.
 * Run: php tests/cli/municipality_barangay_code_per_project_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\Project;
use Core\Database;

$migration = require dirname(__DIR__, 2) . '/database/migration_072_municipality_barangay_code_per_project.php';
$migration['up'](Database::getInstance());

$tag = 'CODE-PER-PROJ-' . time();
$p1 = Project::create(['name' => $tag . '-P1', 'description' => 'test']);
$p2 = Project::create(['name' => $tag . '-P2', 'description' => 'test']);
assert($p1 > 0 && $p2 > 0);

$mun1 = Municipality::create([
    'name' => $tag . '-Mun-A',
    'code' => '001',
    'description' => '',
    'project_id' => $p1,
]);
$mun2 = Municipality::create([
    'name' => $tag . '-Mun-B',
    'code' => '001',
    'description' => '',
    'project_id' => $p2,
]);
assert($mun1 > 0 && $mun2 > 0);

$dupMun = false;
try {
    Municipality::create([
        'name' => $tag . '-Mun-C',
        'code' => '001',
        'description' => '',
        'project_id' => $p1,
    ]);
} catch (\InvalidArgumentException $e) {
    $dupMun = str_contains($e->getMessage(), 'code already exists for this project');
}
assert($dupMun, 'duplicate municipality code in same project must fail');

$br1 = Barangay::create([
    'municipality_id' => $mun1,
    'name' => $tag . '-Brgy-A',
    'code' => '001',
    'description' => '',
]);
$br2 = Barangay::create([
    'municipality_id' => $mun2,
    'name' => $tag . '-Brgy-B',
    'code' => '001',
    'description' => '',
]);
assert($br1 > 0 && $br2 > 0);

$dupBr = false;
try {
    Barangay::create([
        'municipality_id' => $mun1,
        'name' => $tag . '-Brgy-C',
        'code' => '001',
        'description' => '',
    ]);
} catch (\InvalidArgumentException $e) {
    $dupBr = str_contains($e->getMessage(), 'code already exists for this project');
}
assert($dupBr, 'duplicate barangay code in same project must fail');

$db = Database::getInstance();
$db->prepare('DELETE FROM barangays WHERE id IN (?, ?)')->execute([$br1, $br2]);
$db->prepare('DELETE FROM municipalities WHERE id IN (?, ?)')->execute([$mun1, $mun2]);
$db->prepare('DELETE FROM municipality_projects WHERE municipality_id IN (?, ?)')->execute([$mun1, $mun2]);
$db->prepare('DELETE FROM projects WHERE id IN (?, ?)')->execute([$p1, $p2]);

echo "municipality_barangay_code_per_project_test: OK\n";
