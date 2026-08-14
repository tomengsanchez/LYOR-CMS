<?php
/**
 * Smoke test: status log API payload shape and note parsing helpers.
 * Run: php tests/cli/grievance_status_log_api_test.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\GrievanceStatusLog;

$entry = (object) [
    'id' => 99,
    'grievance_id' => 10,
    'status' => 'in_progress',
    'progress_level' => 2,
    'progress_level_name' => 'Level 2',
    'note' => 'Follow-up with barangay.',
    'effective_at' => '2026-06-10 09:00:00',
    'created_at' => '2026-06-15 14:00:00',
    'created_by' => 1,
    'created_by_name' => 'admin',
    'attachments' => json_encode(['/uploads/grievance/status/att_test.pdf']),
];

$api = GrievanceStatusLog::entryForApi($entry, 10);
$required = [
    'id', 'status', 'status_label', 'progress_level', 'progress_level_name',
    'note', 'effective_at', 'created_at', 'created_by', 'created_by_name',
    'attachments', 'recorded_at_differs',
];
foreach ($required as $field) {
    if (!array_key_exists($field, $api)) {
        fwrite(STDERR, "FAIL: entryForApi missing {$field}\n");
        exit(1);
    }
}
if ($api['note'] !== 'Follow-up with barangay.') {
    fwrite(STDERR, "FAIL: note not preserved\n");
    exit(1);
}
if ($api['status_label'] !== 'In progress — Level 2') {
    fwrite(STDERR, "FAIL: unexpected status_label: {$api['status_label']}\n");
    exit(1);
}
if ($api['recorded_at_differs'] !== true) {
    fwrite(STDERR, "FAIL: recorded_at_differs should be true\n");
    exit(1);
}
if (!is_array($api['attachments']) || count($api['attachments']) !== 1) {
    fwrite(STDERR, "FAIL: attachments not mapped\n");
    exit(1);
}

$noteOnly = (object) [
    'id' => 100,
    'status' => 'open',
    'progress_level' => null,
    'progress_level_name' => null,
    'note' => 'Note on same status',
    'effective_at' => '2026-06-16 10:00:00',
    'created_at' => '2026-06-16 10:00:00',
    'attachments' => '[]',
];
$noteApi = GrievanceStatusLog::entryForApi($noteOnly, 10);
if ($noteApi['recorded_at_differs'] !== false) {
    fwrite(STDERR, "FAIL: same effective/created should not differ\n");
    exit(1);
}

echo "OK: grievance status log API payload\n";
