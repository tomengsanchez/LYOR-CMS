<?php
/**
 * Smoke test for denormalized grievance escalation clock.
 * Run: php tests/cli/grievance_escalation_clock_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Grievance;
use App\Models\AppSettings;
use App\GrievanceEscalation;

$prevWeekends = AppSettings::get('grievance_escalation_exclude_weekends', '0');
$prevHolidays = AppSettings::get('grievance_escalation_exclude_holidays', '0');
AppSettings::set('grievance_escalation_exclude_weekends', '0');
AppSettings::set('grievance_escalation_exclude_holidays', '0');
GrievanceEscalation::resetCache();

$logs = [
    (object) [
        'id' => 1,
        'grievance_id' => 10,
        'status' => 'open',
        'progress_level' => 0,
        'created_at' => '2026-01-01 09:00:00',
    ],
    (object) [
        'id' => 2,
        'grievance_id' => 10,
        'status' => 'in_progress',
        'progress_level' => 1,
        'created_at' => '2026-01-05 10:00:00',
    ],
    (object) [
        'id' => 3,
        'grievance_id' => 10,
        'status' => 'in_progress',
        'progress_level' => 1,
        'created_at' => '2026-01-06 11:00:00',
    ],
    (object) [
        'id' => 4,
        'grievance_id' => 10,
        'status' => 'in_progress',
        'progress_level' => 2,
        'created_at' => '2026-01-10 12:00:00',
    ],
];

$map = Grievance::computeSegmentStartsFromLogs($logs);
assert(($map['10_1'] ?? null) === '2026-01-05 10:00:00', 'level 1 segment start');
assert(($map['10_2'] ?? null) === '2026-01-10 12:00:00', 'level 2 segment start');

assert(Grievance::escalationClockForStatus('open', 1) === null);
assert(Grievance::escalationClockForStatus('in_progress', null) === null);
assert(
    Grievance::escalationClockForStatus('in_progress', 2, '2026-02-01 08:00:00') === '2026-02-01 08:00:00'
);

$sql = Grievance::sqlDaysOpenExceedsSla('g', 'pl');
assert(str_contains($sql, 'g.current_level_started_at'), 'uses denormalized column');
if (!\App\GrievanceEscalation::usesAdjustedCalendar()) {
    assert(str_contains($sql, 'DATEDIFF'), 'uses datediff');
    assert(str_contains($sql, 'escalation_count_start'), 'uses per-project count start');
}
assert(!str_contains($sql, 'grievance_status_log'), 'no correlated status-log subquery');

AppSettings::set('grievance_escalation_exclude_weekends', $prevWeekends);
AppSettings::set('grievance_escalation_exclude_holidays', $prevHolidays);
GrievanceEscalation::resetCache();

echo "grievance_escalation_clock_test: OK\n";
