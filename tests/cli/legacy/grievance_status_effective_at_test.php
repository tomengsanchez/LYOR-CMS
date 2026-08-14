<?php
/**
 * Smoke test for grievance status log effective_at (segment clock + validation).
 * Run: php tests/cli/grievance_status_effective_at_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Grievance;
use App\Models\GrievanceStatusLog;

$logs = [
    (object) [
        'id' => 1,
        'grievance_id' => 10,
        'status' => 'open',
        'progress_level' => 0,
        'effective_at' => '2026-01-01 09:00:00',
        'created_at' => '2026-01-05 09:00:00',
    ],
    (object) [
        'id' => 2,
        'grievance_id' => 10,
        'status' => 'in_progress',
        'progress_level' => 1,
        'effective_at' => '2026-01-02 10:00:00',
        'created_at' => '2026-01-05 10:00:00',
    ],
];

$map = Grievance::computeSegmentStartsFromLogs($logs);
assert(($map['10_1'] ?? null) === '2026-01-02 10:00:00', 'uses effective_at not created_at');

$withNote = array_merge($logs, [
    (object) [
        'id' => 5,
        'grievance_id' => 10,
        'status' => 'in_progress',
        'progress_level' => 1,
        'effective_at' => '2026-01-09 14:00:00',
        'created_at' => '2026-01-15 10:00:00',
    ],
]);
$mapNote = Grievance::computeSegmentStartsFromLogs($withNote);
assert(($mapNote['10_1'] ?? null) === '2026-01-02 10:00:00', 'note-only effective date must not move SLA segment');

assert(GrievanceStatusLog::segmentAtFor($withNote[2]) === '2026-01-09 14:00:00', 'note entry shows paper date');

assert(GrievanceStatusLog::segmentAtFor($logs[1]) === '2026-01-02 10:00:00');

$fallback = (object) [
    'effective_at' => null,
    'created_at' => '2026-01-03 12:00:00',
];
assert(GrievanceStatusLog::segmentAtFor($fallback) === '2026-01-03 12:00:00');

assert(GrievanceStatusLog::parseEffectiveAtInput('2026-01-02T08:30') === '2026-01-02 08:30:00');

echo "grievance_status_effective_at_test: OK\n";
