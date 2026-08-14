<?php
/**
 * Optimistic locking helpers, idempotency storage, and transaction rollback.
 * Run: php tests/cli/concurrency_optimistic_idempotency_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\ApiIdempotency;
use App\DbTransaction;
use App\OptimisticLock;
use App\StaleRecordException;
use Core\Database;

assert(OptimisticLock::normalize('2026-06-17 10:30:00') === '2026-06-17 10:30:00');
assert(OptimisticLock::normalize('') === null);
assert(OptimisticLock::expectedFromData(['record_updated_at' => '2026-06-17 10:30:00']) === '2026-06-17 10:30:00');
assert(OptimisticLock::expectedFromData(['expected_updated_at' => '2026-06-17 10:30:00']) === '2026-06-17 10:30:00');

$stmt = Database::getInstance()->query('SELECT id FROM users ORDER BY id ASC LIMIT 1');
$userId = (int) ($stmt->fetchColumn() ?: 0);
assert($userId > 0, 'Need at least one user for idempotency test');

$scope = 'cli:test:' . time();
$key = 'cli-key-' . bin2hex(random_bytes(8));
$envelope = ['success' => true, 'data' => ['probe' => 'ok'], 'error' => null];
ApiIdempotency::store($userId, $scope, $key, 201, $envelope);
$replay = ApiIdempotency::replay($userId, $scope, $key);
assert(is_array($replay));
assert((int) ($replay['http_status'] ?? 0) === 201);
assert(($replay['envelope']['data']['probe'] ?? '') === 'ok');
assert(ApiIdempotency::replay($userId, $scope, 'other-key') === null);

$rolledBack = false;
try {
    DbTransaction::run(static function (): void {
        throw new \RuntimeException('rollback probe');
    });
} catch (\RuntimeException $e) {
    $rolledBack = $e->getMessage() === 'rollback probe';
}
assert($rolledBack);
assert(!Database::getInstance()->inTransaction());

$ex = new StaleRecordException();
assert(str_contains($ex->getMessage(), 'updated by someone else'));

echo "concurrency_optimistic_idempotency_test: OK\n";
