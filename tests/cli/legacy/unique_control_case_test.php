<?php
/**
 * Unique control number and grievance case number validation.
 * Run: php tests/cli/unique_control_case_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Grievance;
use App\Models\Profile;
use Core\Database;

$db = Database::getInstance();

$probe = 'UNIQUE-CLI-' . time();
$stmt = $db->prepare('SELECT id FROM profiles WHERE is_deleted = 0 AND control_number = ? LIMIT 1');
$stmt->execute([$probe]);
assert(!$stmt->fetch(\PDO::FETCH_ASSOC));

$caseProbe = 'GRV-CLI-' . time();
$stmt2 = $db->prepare('SELECT id FROM grievances WHERE is_deleted = 0 AND grievance_case_number = ? LIMIT 1');
$stmt2->execute([$caseProbe]);
assert(!$stmt2->fetch(\PDO::FETCH_ASSOC));

assert(Profile::normalizeControlNumber('  ') === null);
assert(Profile::normalizeControlNumber(' ABC ') === 'ABC');
assert(Grievance::normalizeCaseNumber('') === null);

echo "unique_control_case_test: OK\n";
