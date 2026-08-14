<?php
/**
 * Verify JSON_CONTAINS fallback SQL executes (MariaDB-compatible CAST AS CHAR).
 * Run: php tests/cli/grievance_json_mvi_test.php && php tests/cli/grievance_json_contains_fallback_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$db = Core\Database::getInstance();

$cond = 'JSON_CONTAINS(g.grievance_category_ids, CAST(c.id AS CHAR), \'$\')';
$sql = "SELECT c.id, c.name, COUNT(g.id) AS cnt
        FROM grievance_categories c
        LEFT JOIN grievances g ON {$cond} AND g.is_deleted = 0
        WHERE c.is_deleted = 0
        GROUP BY c.id, c.name
        LIMIT 1";

$db->query($sql)->fetch(\PDO::FETCH_ASSOC);

echo "grievance_json_contains_fallback_test: OK\n";
