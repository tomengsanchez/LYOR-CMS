<?php
/**
 * Smoke test: branding config and accent color normalization.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\AppSettings;

assert(AppSettings::normalizeAccentColor('#2563eb') === '#2563eb', 'valid hex preserved');
assert(AppSettings::normalizeAccentColor('#ABCDEF') === '#abcdef', 'hex lowercased');
assert(AppSettings::normalizeAccentColor('red') === '#2563eb', 'invalid falls back');
assert(AppSettings::normalizeAccentColor('') === '#2563eb', 'empty falls back');

$branding = AppSettings::getBrandingConfig();
assert(isset($branding->public_accent_color), 'branding includes public_accent_color');
assert(preg_match('/^#[0-9a-f]{6}$/', $branding->public_accent_color), 'accent is valid hex');

echo "cms_branding_smoke_test: OK\n";
