<?php
require __DIR__ . '/../../bootstrap.php';

$zip = $argv[1] ?? '';
if ($zip === '' || !is_file($zip)) {
    fwrite(STDERR, "Usage: php tests/cli/ses_import_preview_smoke_test.php path/to/flat.zip\n");
    exit(1);
}

$importer = new App\SocioEconomicImporter();
$result = $importer->preview($zip);
if (empty($result['ok'])) {
    fwrite(STDERR, ($result['message'] ?? 'preview failed') . "\n");
    exit(1);
}
$p = $result['preview'];
echo "OK files={$p['file_count']} profiles={$p['counts']['profiles_affected']} unmatched={$p['counts']['unmatched_control_numbers']} skipped_empty={$p['counts']['rows_skipped_empty_control']}\n";
exit(0);
