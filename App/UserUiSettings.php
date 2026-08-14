<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Per-user UI preferences (theme color, sidebar vs top menu).
 * Stored in user_dashboard_config with module = 'ui'.
 */
class UserUiSettings
{
    public const MODULE_UI = 'ui';

    public const THEME_DEFAULT = 'default';
    public const THEME_GREEN = 'green';
    public const THEME_VIOLET = 'violet';
    public const THEME_AMBER = 'amber';
    public const THEME_SLATE = 'slate';

    public const LAYOUT_SIDEBAR = 'sidebar';
    public const LAYOUT_TOP = 'top';

    public const COLOR_MODE_LIGHT = 'light';
    public const COLOR_MODE_DARK = 'dark';
    public const COLOR_MODE_SYSTEM = 'system';

    /** When true, enables super mobile-friendly UI: columns stack on small screens, responsive tables, optional sidebar drawer on mobile. */
    public const MOBILE_FRIENDLY_DEFAULT = true;

    /** All theme keys for validation */
    public static function themes(): array
    {
        return [
            self::THEME_DEFAULT => 'Default (Blue)',
            self::THEME_GREEN   => 'Green',
            self::THEME_VIOLET  => 'Violet',
            self::THEME_AMBER   => 'Amber',
            self::THEME_SLATE   => 'Slate',
        ];
    }

    /** All layout keys */
    public static function layouts(): array
    {
        return [
            self::LAYOUT_SIDEBAR => 'Sidebar',
            self::LAYOUT_TOP     => 'Top menu',
        ];
    }

    /** Admin panel light/dark appearance */
    public static function colorModes(): array
    {
        return [
            self::COLOR_MODE_LIGHT  => 'Light',
            self::COLOR_MODE_DARK   => 'Dark',
            self::COLOR_MODE_SYSTEM => 'System preference',
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'theme'          => self::THEME_DEFAULT,
            'layout'         => self::LAYOUT_SIDEBAR,
            'mobile_friendly' => self::MOBILE_FRIENDLY_DEFAULT,
            'color_mode'     => self::COLOR_MODE_LIGHT,
        ];
    }

    public static function get(): array
    {
        $userId = Auth::id();
        if (!$userId) {
            return self::defaultConfig();
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, self::MODULE_UI]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || $row->config === null || $row->config === '') {
            return self::defaultConfig();
        }
        $decoded = json_decode($row->config, true);
        return is_array($decoded) ? array_merge(self::defaultConfig(), $decoded) : self::defaultConfig();
    }

    public static function save(array $config): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }
        $theme = $config['theme'] ?? self::THEME_DEFAULT;
        $layout = $config['layout'] ?? self::LAYOUT_SIDEBAR;
        $mobileFriendly = !empty($config['mobile_friendly']);
        $colorMode = $config['color_mode'] ?? self::COLOR_MODE_LIGHT;
        $themes = array_keys(self::themes());
        $layouts = array_keys(self::layouts());
        $colorModes = array_keys(self::colorModes());
        if (!in_array($theme, $themes, true)) {
            $theme = self::THEME_DEFAULT;
        }
        if (!in_array($layout, $layouts, true)) {
            $layout = self::LAYOUT_SIDEBAR;
        }
        if (!in_array($colorMode, $colorModes, true)) {
            $colorMode = self::COLOR_MODE_LIGHT;
        }
        $db = Database::getInstance();
        $json = json_encode([
            'theme' => $theme,
            'layout' => $layout,
            'mobile_friendly' => $mobileFriendly,
            'color_mode' => $colorMode,
        ]);
        $stmt = $db->prepare('INSERT INTO user_dashboard_config (user_id, module, config) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config = VALUES(config)');
        $stmt->execute([$userId, self::MODULE_UI, $json]);
    }
}
