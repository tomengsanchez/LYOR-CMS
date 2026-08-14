<?php
namespace App;

use Core\Database;

/**
 * Run DML inside a PDO transaction (rolls back on failure).
 */
final class DbTransaction
{
    /**
     * @template T
     * @param callable(\PDO): T $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        $db = Database::getInstance();
        if ($db->inTransaction()) {
            return $callback($db);
        }

        $db->beginTransaction();
        try {
            $result = $callback($db);
            $db->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
