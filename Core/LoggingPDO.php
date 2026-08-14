<?php
namespace Core;

use PDO;
use PDOStatement;

class LoggingPDO extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|LoggingStatement|false
    {
        $stmt = parent::prepare($query, $options);
        if ($stmt === false) {
            return false;
        }
        return new LoggingStatement($stmt, $query);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$args): PDOStatement|false
    {
        $t0 = microtime(true);
        $stmt = $args !== [] ? parent::query($query, $fetchMode, ...$args) : parent::query($query, $fetchMode);
        $duration = (microtime(true) - $t0) * 1000;
        if (SystemDebug::isCollecting()) {
            SystemDebug::logQuery($query, null, $duration / 1000);
        }
        return $stmt;
    }

    public function exec(string $statement): int|false
    {
        $t0 = microtime(true);
        $result = parent::exec($statement);
        $duration = (microtime(true) - $t0) * 1000;
        if (SystemDebug::isCollecting()) {
            SystemDebug::logQuery($statement, null, $duration / 1000);
        }
        return $result;
    }
}
