<?php
/**
 * Verify auto grievance case numbers are unique under sequential creates (lock + insert).
 * Run: php tests/cli/grievance_case_number_lock_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Grievance;
use Core\Database;
use Core\MySqlNamedLock;

$db = Database::getInstance();

assert(defined(MySqlNamedLock::class . '::LOCK_GRIEVANCE_CASE'));

$base = [
    'date_recorded' => date('Y-m-d H:i:s'),
    'grievance_case_number' => '',
    'project_id' => null,
    'is_paps' => 0,
    'status' => 'open',
    'description_complaint' => 'CLI lock test',
    'desired_resolution' => 'N/A',
];

$id1 = Grievance::create($base);
$id2 = Grievance::create($base);

$g1 = Grievance::find($id1);
$g2 = Grievance::find($id2);
assert($g1 && $g2);

$c1 = trim((string) ($g1->grievance_case_number ?? ''));
$c2 = trim((string) ($g2->grievance_case_number ?? ''));
assert($c1 !== '' && $c2 !== '', 'auto case numbers must be assigned');
assert($c1 !== $c2, "duplicate case number: {$c1}");

$stmt = $db->prepare('SELECT COUNT(*) FROM grievances WHERE grievance_case_number = ? AND is_deleted = 0');
foreach ([$c1, $c2] as $caseNum) {
    $stmt->execute([$caseNum]);
    assert((int) $stmt->fetchColumn() === 1, "case number not unique in DB: {$caseNum}");
}

$db->prepare('UPDATE grievances SET is_deleted = 1 WHERE id IN (?, ?)')->execute([$id1, $id2]);

echo "grievance_case_number_lock_test: OK ({$c1}, {$c2})\n";
