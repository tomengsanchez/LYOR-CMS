<?php
/**
 * Read-only SQL helper for Playwright E2E assertions.
 * Usage: echo '{"sql":"SELECT 1","params":[]}' | php tests/e2e/support/db-query.php
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

require_once __DIR__ . '/../../../bootstrap.php';

use Core\Database;

$raw = stream_get_contents(STDIN);
if ($raw === false || trim($raw) === '') {
    fwrite(STDERR, "Missing JSON on stdin\n");
    exit(1);
}

$payload = json_decode($raw, true);
if (!is_array($payload) || empty($payload['sql']) || !is_string($payload['sql'])) {
    fwrite(STDERR, "Invalid payload: expected {\"sql\":\"...\",\"params\":[]}\n");
    exit(1);
}

$sql = trim($payload['sql']);
if (!preg_match('/^\s*SELECT\b/i', $sql)) {
    fwrite(STDERR, "Only SELECT queries are allowed\n");
    exit(1);
}

$params = $payload['params'] ?? [];
if (!is_array($params)) {
    fwrite(STDERR, "params must be an array\n");
    exit(1);
}

$db = Database::getInstance();
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
