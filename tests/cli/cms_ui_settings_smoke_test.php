<?php
/**
 * Smoke test: UserUiSettings defaults include color_mode.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\UserUiSettings;

$defaults = UserUiSettings::defaultConfig();
assert(isset($defaults['color_mode']), 'default config includes color_mode');
assert($defaults['color_mode'] === UserUiSettings::COLOR_MODE_LIGHT, 'default color_mode is light');

$modes = UserUiSettings::colorModes();
assert(count($modes) === 3, 'three color modes defined');
assert(isset($modes[UserUiSettings::COLOR_MODE_DARK]), 'dark mode defined');

echo "cms_ui_settings_smoke_test: OK\n";
