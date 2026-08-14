<?php
/**
 * Smoke test for grievance JSON membership SQL helper (MEMBER OF or JSON_CONTAINS).
 * Run: php tests/cli/grievance_json_mvi_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\GrievanceJsonSql;

$db = Core\Database::getInstance();

$version = (string) $db->query('SELECT VERSION()')->fetchColumn();
$usesMemberOf = GrievanceJsonSql::supportsMemberOf($db);

$memberSql = GrievanceJsonSql::memberOf('t.id', 'g.grievance_type_ids', $db);
if ($usesMemberOf) {
    assert(str_contains($memberSql, 'MEMBER OF'), 'Expected MEMBER OF on MySQL 8.0.17+');
    assert(str_contains($memberSql, 'CAST(t.id AS UNSIGNED)'));
} else {
    assert(str_contains($memberSql, 'JSON_CONTAINS'), 'Expected JSON_CONTAINS fallback on MariaDB / older MySQL');
    assert(str_contains($memberSql, 'CAST(t.id AS CHAR)'), 'MariaDB-compatible JSON_CONTAINS candidate');
    assert(!str_contains($memberSql, 'AS JSON'), 'MariaDB does not support CAST AS JSON');
}

$joinSql = 'SELECT g.id FROM grievances g
     JOIN grievance_types t ON ' . $memberSql . '
     WHERE g.is_deleted = 0 LIMIT 1';
$explain = $db->query('EXPLAIN ' . $joinSql)->fetch(\PDO::FETCH_ASSOC);
assert(is_array($explain), 'EXPLAIN failed for membership join SQL');

if ($usesMemberOf) {
    $indexNames = ['idx_grievance_type_ids_mvi', 'idx_grievance_category_ids_mvi'];
    foreach ($indexNames as $name) {
        $stmt = $db->prepare('SHOW INDEX FROM grievances WHERE Key_name = ?');
        $stmt->execute([$name]);
        assert((bool) $stmt->fetch(\PDO::FETCH_ASSOC), "Missing index {$name} — run php cli/migrate.php");
    }
}

echo 'grievance_json_mvi_test: OK (' . ($usesMemberOf ? 'MEMBER OF' : 'JSON_CONTAINS') . ", {$version})\n";
