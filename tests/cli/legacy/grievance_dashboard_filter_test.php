<?php
/**
 * Smoke test for grievance dashboard filter helper.
 * Run: php tests/cli/grievance_dashboard_filter_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\GrievanceDashboardFilter;

$filter = new GrievanceDashboardFilter();
$filter->selectedProjectId = 3;
$filter->allowedProjects = [1, 2, 3];
[$sql, $params] = $filter->sqlAnd('g');
assert(str_contains($sql, 'g.is_deleted = 0'));
assert(str_contains($sql, 'g.project_id = ?'));
assert($params === [3]);

$filter2 = new GrievanceDashboardFilter();
$filter2->allowedProjects = [10, 20];
[$sql2, $params2] = $filter2->sqlAnd('');
assert(str_contains($sql2, 'project_id IN (?,?)'));
assert($params2 === [10, 20]);

$filter3 = new GrievanceDashboardFilter();
$filter3->allowedProjects = [];
[$sql3] = $filter3->sqlAnd('g');
assert(str_contains($sql3, '1=0'));

$filter4 = new GrievanceDashboardFilter();
$filter4->dateFrom = '2026-01-01';
$filter4->dateTo = '2026-01-31';
[$sql4, $params4] = $filter4->sqlAnd('g');
assert(str_contains($sql4, 'COALESCE(g.date_recorded, g.created_at) >='));
assert(str_contains($sql4, 'COALESCE(g.date_recorded, g.created_at) <='));
assert($params4[0] === '2026-01-01 00:00:00');
assert($params4[1] === '2026-01-31 23:59:59');

$where = $filter4->whereClause('g');
assert(str_starts_with($where, ' WHERE 1=1'));

echo "grievance_dashboard_filter_test: OK\n";
