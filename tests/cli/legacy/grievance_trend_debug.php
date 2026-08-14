<?php
require dirname(__DIR__, 2) . '/bootstrap.php';

$db = Core\Database::getInstance();
$total = (int) $db->query('SELECT COUNT(*) FROM grievances WHERE is_deleted = 0')->fetchColumn();
echo "total_active={$total}\n";

$rows = $db->query(
    'SELECT DATE_FORMAT(COALESCE(date_recorded, created_at), "%Y-%m") AS ym, COUNT(*) AS cnt
     FROM grievances WHERE is_deleted = 0
     GROUP BY ym ORDER BY ym DESC LIMIT 15'
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo ($row['ym'] ?? 'NULL') . '=' . ($row['cnt'] ?? 0) . "\n";
}

$nullDates = (int) $db->query(
    'SELECT COUNT(*) FROM grievances WHERE is_deleted = 0 AND date_recorded IS NULL'
)->fetchColumn();
echo "null_date_recorded={$nullDates}\n";
