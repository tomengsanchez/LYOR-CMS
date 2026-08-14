<?php
namespace Core;

use PDOStatement;

/**
 * Wraps a PDOStatement to log execute() for the debug log.
 */
class LoggingStatement
{
    private PDOStatement $inner;
    private string $sql;

    public function __construct(PDOStatement $inner, string $sql)
    {
        $this->inner = $inner;
        $this->sql = $sql;
    }

    public function execute(?array $params = null): bool
    {
        $t0 = microtime(true);
        $result = $this->inner->execute($params);
        $duration = (microtime(true) - $t0);
        if (SystemDebug::isCollecting()) {
            SystemDebug::logQuery($this->sql, $params, $duration);
        }
        return $result;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->inner->$name(...$arguments);
    }
}
