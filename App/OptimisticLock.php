<?php
namespace App;

use Core\Database;

/**
 * Compare client-supplied updated_at with the row before applying updates.
 */
final class OptimisticLock
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        try {
            $dt = new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /** @param object|null $record */
    public static function fromRecord(?object $record): ?string
    {
        if (!$record || !isset($record->updated_at)) {
            return null;
        }

        return self::normalize((string) $record->updated_at);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function expectedFromData(array $data): ?string
    {
        foreach (['expected_updated_at', 'record_updated_at', 'updated_at'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $normalized = self::normalize(is_scalar($data[$key]) ? (string) $data[$key] : null);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    public static function assertCurrent(string $table, int $id, ?string $expectedUpdatedAt, string $deletedClause = ''): void
    {
        $expectedUpdatedAt = self::normalize($expectedUpdatedAt);
        if ($expectedUpdatedAt === null || $id <= 0) {
            return;
        }

        $sql = "SELECT updated_at FROM {$table} WHERE id = ?";
        if ($deletedClause !== '') {
            $sql .= ' AND ' . $deletedClause;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        if ($current === false) {
            return;
        }

        $currentNorm = self::normalize((string) $current);
        if ($currentNorm !== null && $currentNorm !== $expectedUpdatedAt) {
            throw new StaleRecordException();
        }
    }

    public static function verifyUpdate(int $rowCount, string $table, int $id, ?string $expectedUpdatedAt, string $deletedClause = ''): void
    {
        $expectedUpdatedAt = self::normalize($expectedUpdatedAt);
        if ($expectedUpdatedAt === null || $rowCount > 0) {
            return;
        }

        self::assertCurrent($table, $id, $expectedUpdatedAt, $deletedClause);
    }

    public static function formatForInput(?string $updatedAt): string
    {
        $normalized = self::normalize($updatedAt);

        return $normalized ?? '';
    }
}
