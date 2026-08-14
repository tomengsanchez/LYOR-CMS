<?php
/**
 * Smoke test: respondent type API payload includes category fields.
 * Run: php tests/cli/grievance_respondent_type_api_test.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\GrievanceRespondentType;

$items = GrievanceRespondentType::allForApi();
$categories = GrievanceRespondentType::categoriesForApi();

if (!is_array($items)) {
    fwrite(STDERR, "FAIL: allForApi must return array\n");
    exit(1);
}

if (count($categories) !== 3) {
    fwrite(STDERR, "FAIL: expected 3 categories, got " . count($categories) . "\n");
    exit(1);
}

$keys = array_column($categories, 'key');
$expectedKeys = ['directly_affected', 'indirectly_affected', 'others'];
if ($keys !== $expectedKeys) {
    fwrite(STDERR, "FAIL: category keys mismatch: " . json_encode($keys) . "\n");
    exit(1);
}

$others = $categories[2];
if (empty($others['allow_other_specify'])) {
    fwrite(STDERR, "FAIL: others category must allow_other_specify\n");
    exit(1);
}

foreach ($items as $row) {
    foreach (['id', 'name', 'type', 'sort_order'] as $field) {
        if (!array_key_exists($field, $row)) {
            fwrite(STDERR, "FAIL: respondent_types row missing {$field}\n");
            exit(1);
        }
    }
    if (!in_array($row['type'], GrievanceRespondentType::TYPE_ORDER, true)) {
        fwrite(STDERR, "FAIL: invalid type on row id {$row['id']}\n");
        exit(1);
    }
}

$itemCount = count($items);
$catCount = 0;
foreach ($categories as $cat) {
    $catCount += count($cat['items']);
}
if ($catCount !== $itemCount) {
    fwrite(STDERR, "FAIL: category item count {$catCount} != flat count {$itemCount}\n");
    exit(1);
}

echo "OK: respondent type API payload ({$itemCount} items, 3 categories)\n";
