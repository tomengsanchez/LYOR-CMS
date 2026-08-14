<?php
namespace Core;

/**
 * Helpers for database migrations.
 *
 * MySQL DDL (ALTER/CREATE) implicitly commits and cannot be rolled back.
 * Use transaction() for DML/backfills; keep DDL idempotent (IF NOT EXISTS checks).
 */
final class MigrationScope
{
    /**
     * Run DML inside a transaction (rolls back on failure).
     *
     * @param callable(\PDO): void $callback
     */
    public static function transaction(\PDO $db, callable $callback): void
    {
        if ($db->inTransaction()) {
            $callback($db);

            return;
        }

        $db->beginTransaction();
        try {
            $callback($db);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Idempotent DDL first, then optional DML in a transaction.
     * If DML fails, DDL changes remain (MySQL limitation); down() should reverse DDL.
     *
     * @param callable(\PDO): void $ddl
     * @param callable(\PDO): void|null $dml
     */
    public static function ddlThenTransactionalDml(\PDO $db, callable $ddl, ?callable $dml = null): void
    {
        $ddl($db);
        if ($dml !== null) {
            self::transaction($db, $dml);
        }
    }
}
