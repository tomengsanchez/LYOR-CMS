<?php
/**
 * Smoke test for main dashboard date range helper.
 * Run: php tests/cli/dashboard_date_range_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\DashboardDateRange;

$all = DashboardDateRange::fromQuery([]);
assert(!$all->hasFilter());
assert($all->displayFrom() === null);

$range = DashboardDateRange::fromQuery([
    'date_from' => '2026-01-01',
    'date_to' => '2026-01-31',
]);
assert($range->hasFilter());
assert($range->displayFrom() === '2026-01-01');
assert($range->displayTo() === '2026-01-31');

$params = [];
$sql = $range->appendSql('p.created_at', $params);
assert(str_contains($sql, 'p.created_at >='));
assert(str_contains($sql, 'p.created_at <='));
assert(count($params) === 2);

$swapped = DashboardDateRange::fromQuery([
    'date_from' => '2026-02-01',
    'date_to' => '2026-01-01',
]);
assert($swapped->displayFrom() === '2026-01-01');
assert($swapped->displayTo() === '2026-02-01');

echo "dashboard_date_range_test: OK\n";
