<?php
/**
 * Smoke test: core CMS tables exist after migrations.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use Core\Database;

$db = Database::getInstance();
$tables = ['cms_pages', 'cms_posts', 'cms_categories', 'cms_media', 'user_dashboard_config', 'roles', 'users'];
foreach ($tables as $table) {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
    assert($stmt && $stmt->fetchColumn(), "missing table: {$table}");
}

echo "cms_schema_smoke_test: OK\n";
