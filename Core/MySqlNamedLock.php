<?php
namespace Core;

/**
 * MySQL user-level locks (GET_LOCK / RELEASE_LOCK).
 *
 * Safe across multiple PHP app servers when every server uses the same MySQL primary.
 * Locks are released automatically when the acquiring connection closes.
 *
 * Not suitable for: read-replica writes, Galera multi-primary without external coordination,
 * or sharded databases where sequences are per shard.
 */
final class MySqlNamedLock
{
    public const LOCK_PAPSID = 'papsid_generate';

    public const LOCK_STRID = 'strid_generate';

    public const LOCK_GRIEVANCE_CASE = 'grievance_case_generate';

    public const LOCK_MIGRATE = 'paper_migrate';

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function run(\PDO $db, string $name, int $timeoutSeconds, callable $callback): mixed
    {
        self::assertValidName($name);
        $timeoutSeconds = max(1, $timeoutSeconds);

        $stmt = $db->prepare('SELECT GET_LOCK(?, ?)');
        $stmt->execute([$name, $timeoutSeconds]);
        $got = $stmt->fetchColumn();
        if ((int) $got !== 1) {
            throw new \RuntimeException(
                "Could not acquire MySQL named lock [{$name}] within {$timeoutSeconds}s"
            );
        }

        try {
            return $callback();
        } finally {
            $release = $db->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$name]);
        }
    }

    private static function assertValidName(string $name): void
    {
        if ($name === '' || !preg_match('/^[a-zA-Z0-9_.]{1,64}$/', $name)) {
            throw new \InvalidArgumentException('Invalid MySQL lock name');
        }
    }
}
