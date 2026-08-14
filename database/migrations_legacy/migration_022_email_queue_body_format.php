<?php
/**
 * Migration 022: Add body_format to email_queue (plain | html) for rich notification emails.
 */
return [
    'name' => 'migration_022_email_queue_body_format',
    'up' => function (\PDO $db): void {
        $db->exec("ALTER TABLE email_queue ADD COLUMN body_format VARCHAR(10) NOT NULL DEFAULT 'plain' AFTER body");
    },
    'down' => function (\PDO $db): void {
        $db->exec("ALTER TABLE email_queue DROP COLUMN body_format");
    },
];
