<?php
/**
 * Migration 088: AI Help Chat settings (app_settings defaults).
 *
 * Floating Ask Help widget answers from in-app help content.
 * Optional OpenAI-compatible API key; without a key, local help-text matching is used.
 */
return [
    'name' => 'migration_088_help_chat',
    'up' => function (\PDO $db): void {
        $ins = $db->prepare(
            'INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)'
        );
        $defaults = [
            'help_chat_enabled' => '1',
            'help_chat_api_key' => '',
            'help_chat_api_base' => 'https://api.openai.com/v1',
            'help_chat_model' => 'gpt-4o-mini',
            'help_chat_max_per_hour' => '30',
        ];
        foreach ($defaults as $key => $value) {
            $ins->execute([$key, $value]);
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec("DELETE FROM app_settings WHERE setting_key IN (
            'help_chat_enabled',
            'help_chat_api_key',
            'help_chat_api_base',
            'help_chat_model',
            'help_chat_max_per_hour'
        )");
    },
];
