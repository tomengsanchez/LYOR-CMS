<?php
/**
 * Smoke test for escalation day-count message formatting (via reflection).
 * Run: php tests/cli/grievance_escalation_display_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$controller = new \App\Controllers\GrievanceController();
$ref = new ReflectionClass($controller);
$format = $ref->getMethod('formatEscalationDayCount');
$format->setAccessible(true);

$apply = $ref->getMethod('applyEscalationDisplay');
$apply->setAccessible(true);

assert($format->invoke($controller, 1, 'left') === '1 day left');
assert($format->invoke($controller, 10, 'left') === '10 days left');
assert($format->invoke($controller, 3, 'overdue') === '3 days overdue');

$g = (object) [
    'status' => 'in_progress',
    'progress_level' => 1,
];
$level = (object) ['id' => 1, 'name' => 'Level 1', 'days_to_address' => 10, 'sort_order' => 1];
$map = [
    'byId' => [1 => $level],
    'nextById' => [1 => (object) ['id' => 2, 'name' => 'Level 2']],
    'lastId' => 2,
];
$apply->invoke($controller, $g, $map, null);
assert($g->escalation_message === '10 days left', 'new case: ' . ($g->escalation_message ?? 'null'));
assert($g->escalation_variant === 'warning');

echo "grievance_escalation_display_test: OK\n";
