<?php
/**
 * Business-day escalation counting.
 * Run: php tests/cli/grievance_escalation_business_days_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\GrievanceEscalation;
use App\Models\AppSettings;

$prevWeekends = AppSettings::get('grievance_escalation_exclude_weekends', '0');
$prevHolidays = AppSettings::get('grievance_escalation_exclude_holidays', '0');

AppSettings::set('grievance_escalation_exclude_weekends', '0');
AppSettings::set('grievance_escalation_exclude_holidays', '0');
GrievanceEscalation::resetCache();

// 2026-06-01 Mon .. 2026-06-07 Sun
assert(
    GrievanceEscalation::daysOpen('2026-06-01 09:00:00', new \DateTimeImmutable('2026-06-07')) === 6,
    'calendar days'
);

assert(
    GrievanceEscalation::deadlineDate('2026-06-01 09:00:00', 3) === '2026-06-04',
    'deadline calendar days next_day'
);

assert(
    GrievanceEscalation::daysOpenAsOf('2026-06-01 09:00:00', new \DateTimeImmutable('2026-06-07'), GrievanceEscalation::COUNT_START_EFFECTIVE_DATE) === 7,
    'effective_date calendar days'
);

assert(
    GrievanceEscalation::deadlineDate('2026-06-01 09:00:00', 3, null, GrievanceEscalation::COUNT_START_EFFECTIVE_DATE) === '2026-06-03',
    'deadline calendar days effective_date'
);

assert(
    GrievanceEscalation::daysOpen('2026-06-01 09:00:00', new \DateTimeImmutable('2026-06-01')) === 0,
    'next_day same day is zero'
);

assert(
    GrievanceEscalation::daysOpen('2026-06-01 09:00:00', new \DateTimeImmutable('2026-06-01'), null, GrievanceEscalation::COUNT_START_EFFECTIVE_DATE) === 1,
    'effective_date same day is one'
);

AppSettings::set('grievance_escalation_exclude_weekends', '1');
GrievanceEscalation::resetCache();

// Mon start to Sun end: Tue–Fri = 4 business days (Sat/Sun skipped)
assert(
    GrievanceEscalation::daysOpen('2026-06-01 09:00:00', new \DateTimeImmutable('2026-06-07')) === 4,
    'exclude weekends'
);

assert(
    GrievanceEscalation::deadlineDate('2026-06-01 09:00:00', 4) === '2026-06-05',
    'deadline exclude weekends'
);

// Fri start to Mon end: only Mon counts
assert(
    GrievanceEscalation::daysOpen('2026-06-05 09:00:00', new \DateTimeImmutable('2026-06-08')) === 1,
    'weekend gap'
);

AppSettings::set('grievance_escalation_exclude_weekends', '0');
AppSettings::set('grievance_escalation_exclude_holidays', '1');
GrievanceEscalation::resetCache();

$db = \Core\Database::getInstance();
$holidayName = '__e2e_cli_holiday_' . bin2hex(random_bytes(4));
$ins = $db->prepare('INSERT INTO holidays (name, description, holiday_date) VALUES (?, ?, ?)');
$ins->execute([$holidayName, 'CLI test', '2026-06-04']);
$holidayId = (int) $db->lastInsertId();
GrievanceEscalation::resetCache();

// Wed start to Fri end with Thu holiday excluded: only Fri counts
assert(
    GrievanceEscalation::daysOpen('2026-06-03 09:00:00', new \DateTimeImmutable('2026-06-05')) === 1,
    'exclude holiday'
);

$db->prepare('DELETE FROM holidays WHERE id = ?')->execute([$holidayId]);
GrievanceEscalation::resetCache();

AppSettings::set('grievance_escalation_exclude_weekends', '0');
AppSettings::set('grievance_escalation_exclude_holidays', '0');
GrievanceEscalation::resetCache();

AppSettings::set('grievance_escalation_exclude_weekends', $prevWeekends);
AppSettings::set('grievance_escalation_exclude_holidays', $prevHolidays);
GrievanceEscalation::resetCache();

echo "grievance_escalation_business_days_test: OK\n";
