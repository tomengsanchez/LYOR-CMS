<?php
/**
 * CLI tests for grievance closure denormalization and dashboard closed-by-stage stats.
 * Run: php tests/cli/grievance_closed_by_stage_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\GrievanceDashboardFilter;
use App\GrievanceDashboardStats;
use App\GrievanceStatusChange;
use App\Models\Grievance;
use App\Models\GrievanceStatusLog;
use Core\Database;

$db = Database::getInstance();

// Unit: closureFieldsFromLogRows
$logs = [
    (object) ['id' => 1, 'grievance_id' => 1, 'status' => 'open', 'progress_level' => null, 'effective_at' => '2026-01-01 10:00:00', 'created_at' => '2026-01-01 10:00:00'],
    (object) ['id' => 2, 'grievance_id' => 1, 'status' => 'in_progress', 'progress_level' => 5, 'effective_at' => '2026-01-02 10:00:00', 'created_at' => '2026-01-02 10:00:00'],
    (object) ['id' => 3, 'grievance_id' => 1, 'status' => 'closed', 'progress_level' => null, 'effective_at' => '2026-01-05 15:00:00', 'created_at' => '2026-01-05 15:00:00'],
];
$closure = Grievance::closureFieldsFromLogRows($logs);
assert($closure['closed_at'] === '2026-01-05 15:00:00', 'closed_at from latest closed log');
assert($closure['closed_at_progress_level'] === 5, 'last in_progress level before close');

$openCloseLogs = [
    (object) ['id' => 10, 'grievance_id' => 2, 'status' => 'open', 'progress_level' => null, 'effective_at' => '2026-01-01 10:00:00', 'created_at' => '2026-01-01 10:00:00'],
    (object) ['id' => 11, 'grievance_id' => 2, 'status' => 'closed', 'progress_level' => null, 'effective_at' => '2026-01-03 12:00:00', 'created_at' => '2026-01-03 12:00:00'],
];
$openClosure = Grievance::closureFieldsFromLogRows($openCloseLogs);
assert($openClosure['closed_at_progress_level'] === null, 'closed from open has no stage');

$reopenLogs = [
    (object) ['id' => 20, 'grievance_id' => 3, 'status' => 'in_progress', 'progress_level' => 2, 'effective_at' => '2026-01-01 10:00:00', 'created_at' => '2026-01-01 10:00:00'],
    (object) ['id' => 21, 'grievance_id' => 3, 'status' => 'closed', 'progress_level' => null, 'effective_at' => '2026-01-02 10:00:00', 'created_at' => '2026-01-02 10:00:00'],
    (object) ['id' => 22, 'grievance_id' => 3, 'status' => 'open', 'progress_level' => null, 'effective_at' => '2026-01-10 10:00:00', 'created_at' => '2026-01-10 10:00:00'],
    (object) ['id' => 23, 'grievance_id' => 3, 'status' => 'in_progress', 'progress_level' => 7, 'effective_at' => '2026-01-11 10:00:00', 'created_at' => '2026-01-11 10:00:00'],
    (object) ['id' => 24, 'grievance_id' => 3, 'status' => 'closed', 'progress_level' => null, 'effective_at' => '2026-01-15 10:00:00', 'created_at' => '2026-01-15 10:00:00'],
];
$reopenClosure = Grievance::closureFieldsFromLogRows($reopenLogs);
assert($reopenClosure['closed_at'] === '2026-01-15 10:00:00', 'uses latest close');
assert($reopenClosure['closed_at_progress_level'] === 7, 'uses level before latest close only');

// Filter: sqlClosedAnd uses closed_at not date_recorded
$filter = new GrievanceDashboardFilter();
$filter->dateFrom = '2026-02-01';
$filter->dateTo = '2026-02-28';
[$closedSql, $closedParams] = $filter->sqlClosedAnd('g');
assert(str_contains($closedSql, 'g.closed_at >='), 'closed filter uses closed_at');
assert(!str_contains($closedSql, 'date_recorded'), 'closed filter does not use date_recorded');
assert($closedParams[0] === '2026-02-01 00:00:00');

// Integration: if grievances table has closure columns, backfill runs without error
$colStmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
$colStmt->execute(['closed_at']);
if ($colStmt->fetch(\PDO::FETCH_ASSOC)) {
    $updated = Grievance::backfillClosedAtFields($db);
    assert($updated >= 0, 'backfill returns non-negative count');

    $stats = new GrievanceDashboardStats($db, GrievanceDashboardFilter::fromQuery([]));
    $payload = $stats->buildPayload();
    assert(array_key_exists('closedByStage', $payload), 'dashboard payload has closedByStage');
    assert(array_key_exists('closedInRangeTotal', $payload), 'dashboard payload has closedInRangeTotal');
    assert(is_array($payload['closedByStage']), 'closedByStage is array');
}

echo "grievance_closed_by_stage_test: OK\n";
