<?php
namespace App\Models;

use Core\Database;

class BackupArchive
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function ensureRecorded(
        string $fileName,
        string $filePath,
        int $fileSize,
        ?string $backupReason = null,
        ?string $restoreSourceFile = null
    ): void {
        $db = self::db();
        $stmt = $db->prepare('
            INSERT INTO backup_archives (file_name, file_path, file_size, backup_reason, restore_source_file)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                file_size = VALUES(file_size),
                backup_reason = VALUES(backup_reason),
                restore_source_file = VALUES(restore_source_file)
        ');
        $stmt->execute([
            $fileName,
            $filePath,
            $fileSize,
            $backupReason,
            $restoreSourceFile,
        ]);
    }

    public static function allDesc(): array
    {
        $stmt = self::db()->query('
            SELECT file_name, file_size, created_at, backup_reason, restore_source_file
            FROM backup_archives
            ORDER BY created_at DESC, id DESC
        ');
        return $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
    }
}
