<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\Api\GrievanceController;
use App\Models\Grievance;

$id = (int) ($argv[1] ?? 0);
if ($id <= 0) {
    fwrite(STDERR, "Usage: php tests/cli/closure_api_smoke.php <grievance_id>\n");
    exit(1);
}

$g = Grievance::find($id);
if (!$g) {
    fwrite(STDERR, "Grievance not found\n");
    exit(1);
}

$ref = new ReflectionClass(GrievanceController::class);
$method = $ref->getMethod('transformGrievance');
$method->setAccessible(true);
$ctrl = $ref->newInstanceWithoutConstructor();
$payload = $method->invoke($ctrl, $g);

echo json_encode([
    'closed_at' => $g->closed_at ?? null,
    'closed_at_progress_level' => $g->closed_at_progress_level ?? null,
    'closure_summary' => $payload['closure_summary'] ?? 'MISSING',
], JSON_PRETTY_PRINT) . "\n";
