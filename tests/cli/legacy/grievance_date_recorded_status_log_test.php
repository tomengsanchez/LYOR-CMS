<?php
/**
 * date_recorded on grievance update must not be later than status log effective dates.
 * Run: php tests/cli/grievance_date_recorded_status_log_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Grievance;
use App\Models\GrievanceStatusLog;

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: $msg\n");
        exit(1);
    }
}

assertTrue(Grievance::validateDateRecorded(null) === 'Date Recorded is required.', 'requires date_recorded');
assertTrue(Grievance::validateDateRecorded('') === 'Date Recorded is required.', 'rejects blank date_recorded');
assertTrue(
    Grievance::validateDateRecorded('not-a-date') === 'Date Recorded must be a valid date and time.',
    'rejects invalid date_recorded'
);
assertTrue(Grievance::validateDateRecorded('2026-01-01 09:00:00') === null, 'accepts valid date_recorded');

$tag = 'TEST-DR-STATUS-' . time();
$id = Grievance::create([
    'date_recorded' => '2026-01-01 09:00:00',
    'grievance_case_number' => $tag,
    'status' => 'open',
]);
assertTrue($id > 0, 'grievance created');

GrievanceStatusLog::create($id, 'open', null, '', [], null, '2026-01-05 10:00:00');
GrievanceStatusLog::create($id, 'in_progress', 1, '', [], null, '2026-01-08 14:00:00');

assertTrue(
    Grievance::validateDateRecordedAgainstStatusLog('2026-01-06 12:00:00', $id) !== null,
    'rejects date_recorded after earliest effective date'
);
assertTrue(
    Grievance::validateDateRecordedAgainstStatusLog('2026-01-05 10:00:00', $id) === null,
    'allows date_recorded equal to earliest effective date'
);
assertTrue(
    Grievance::validateDateRecordedAgainstStatusLog('2026-01-01 09:00:00', $id) === null,
    'allows date_recorded before all effective dates'
);

echo "grievance_date_recorded_status_log_test: OK\n";
