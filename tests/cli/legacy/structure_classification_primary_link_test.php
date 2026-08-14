<?php
/**
 * CLI smoke test: Secondary primary-link + legacy Associated → Secondary (no migration).
 * Run: php tests/cli/structure_classification_primary_link_test.php
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Structure;

if (Structure::normalizeClassification('associated') !== Structure::CLASSIFICATION_SECONDARY) {
    fwrite(STDERR, "associated should normalize to secondary\n");
    exit(1);
}
if (array_key_exists(Structure::CLASSIFICATION_ASSOCIATED, Structure::classificationOptions())) {
    fwrite(STDERR, "Associated must not appear in classificationOptions\n");
    exit(1);
}
if (Structure::classificationLabel('associated') !== 'Secondary') {
    fwrite(STDERR, "associated label should be Secondary\n");
    exit(1);
}
if (!Structure::usesPrimaryLink('secondary') || !Structure::usesPrimaryLink('associated')) {
    fwrite(STDERR, "usesPrimaryLink should be true for secondary/associated\n");
    exit(1);
}
if (Structure::usesPrimaryLink('primary')) {
    fwrite(STDERR, "usesPrimaryLink should be false for primary\n");
    exit(1);
}

$secondary = Structure::expandTaggingPayload([
    'structure_classification' => 'secondary',
    'associated_primary_structure_id' => 0,
]);
if ($secondary['structure_classification'] !== 'secondary') {
    fwrite(STDERR, "secondary classification not preserved\n");
    exit(1);
}
if ($secondary['associated_primary_structure_id'] !== null) {
    fwrite(STDERR, "empty primary link should clear for secondary\n");
    exit(1);
}

$primary = Structure::expandTaggingPayload([
    'structure_classification' => 'primary',
    'associated_primary_structure_id' => 999,
]);
if ($primary['associated_primary_structure_id'] !== null) {
    fwrite(STDERR, "primary classification must clear associated_primary_structure_id\n");
    exit(1);
}

$legacy = Structure::expandTaggingPayload([
    'structure_classification' => 'associated',
    'associated_primary_structure_id' => 0,
]);
if ($legacy['structure_classification'] !== 'secondary') {
    fwrite(STDERR, "legacy associated should save as secondary\n");
    exit(1);
}

echo "structure_classification_primary_link_test: OK\n";
