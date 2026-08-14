<?php
namespace Core;

/**
 * Database migration runner.
 *
 * Scans database/migration_*.php files, runs those not yet applied.
 * Migration format: return ['name' => string, 'up' => callable, 'down' => ?callable]
 *
 * Safety:
 * - Exclusive migrate lock (one runner at a time across app servers).
 * - Stops on first failure (does not apply later migrations).
 * - Best-effort down() on up() failure when defined — except idempotent
 *   "already exists" DDL conflicts, which are recorded as applied without down().
 * - Use MigrationScope::transaction() for DML/backfills (DDL cannot roll back on MySQL).
 */
class MigrationRunner
{
    private \PDO $db;
    private string $migrationsDir;
    private string $table = 'migrations';

    public function __construct(\PDO $db, string $migrationsDir = null)
    {
        $this->db = $db;
        $this->migrationsDir = $migrationsDir ?? (ROOT . '/database');
    }

    public function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /** @return string[] */
    public function getRanMigrations(): array
    {
        $this->ensureMigrationsTable();
        $stmt = $this->db->query("SELECT name FROM {$this->table} ORDER BY id");

        return $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
    }

    /** @return string[] Migration file paths sorted by name */
    public function getPendingMigrations(): array
    {
        $ran = array_flip($this->getRanMigrations());
        $files = glob($this->migrationsDir . '/migration_*.php');
        $pending = [];
        foreach ($files as $f) {
            $m = require $f;
            $name = is_array($m) && isset($m['name']) ? $m['name'] : basename($f, '.php');
            if (!isset($ran[$name])) {
                $pending[$name] = $f;
            }
        }
        ksort($pending);

        return array_values($pending);
    }

    /** @return array{ran: int, errors: array, warnings: list<string>} */
    public function runPending(): array
    {
        return MySqlNamedLock::run($this->db, MySqlNamedLock::LOCK_MIGRATE, 300, function (): array {
            $pending = $this->getPendingMigrations();
            $ran = 0;
            $errors = [];
            $warnings = [];

            foreach ($pending as $file) {
                $result = $this->runUpFile($file);
                if ($result['ok']) {
                    $ran++;
                    if (!empty($result['warning'])) {
                        $warnings[] = (string) $result['warning'];
                    }
                    continue;
                }
                $errors = array_merge($errors, $result['errors']);
                break;
            }

            return ['ran' => $ran, 'errors' => $errors, 'warnings' => $warnings];
        });
    }

    /**
     * @return array{ok: bool, errors: list<string>, warning?: string}
     */
    private function runUpFile(string $file): array
    {
        $basename = basename($file);
        try {
            $m = require $file;
            if (!is_array($m) || !isset($m['up']) || !is_callable($m['up'])) {
                return ['ok' => false, 'errors' => ["{$basename}: invalid migration (missing \"up\" callable)"]];
            }
            $name = $m['name'] ?? basename($file, '.php');
            ($m['up'])($this->db);
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (name) VALUES (?)");
            $stmt->execute([$name]);

            return ['ok' => true, 'errors' => []];
        } catch (\Throwable $e) {
            $m = require $file;
            $name = is_array($m) && isset($m['name']) ? (string) $m['name'] : basename($file, '.php');

            // Additive DDL already present (common after restore of a dump that includes the column
            // but not the migrations ledger row). Mark applied; do NOT run down() (would drop data).
            if ($this->isIdempotentSchemaConflict($e)) {
                $this->ensureMigrationsTable();
                $stmt = $this->db->prepare("INSERT IGNORE INTO {$this->table} (name) VALUES (?)");
                $stmt->execute([$name]);

                return [
                    'ok' => true,
                    'errors' => [],
                    'warning' => "{$basename}: schema already present (" . $e->getMessage() . "); recorded as applied without down()",
                ];
            }

            $errors = ["{$basename}: " . $e->getMessage()];
            $rollbackErrors = $this->attemptDown($file);
            if ($rollbackErrors !== []) {
                $errors = array_merge($errors, $rollbackErrors);
            }

            return ['ok' => false, 'errors' => $errors];
        }
    }

    private function isIdempotentSchemaConflict(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        return (bool) preg_match(
            '/Duplicate column name|Duplicate key name|already exists|1050 Duplicate table|1060 Duplicate column|1061 Duplicate key/i',
            $msg
        );
    }

    /** @return list<string> */
    private function attemptDown(string $file): array
    {
        $basename = basename($file);
        $name = basename($file, '.php');
        try {
            $m = require $file;
            $name = is_array($m) && isset($m['name']) ? (string) $m['name'] : $name;
            if (!is_array($m) || !isset($m['down']) || !is_callable($m['down'])) {
                return ["{$basename}: up() failed — no down() to roll back schema changes"];
            }
            ($m['down'])($this->db);

            return ["{$basename}: up() failed — down() ran (review DB before retrying migrate)"];
        } catch (\Throwable $e) {
            return [
                "{$basename}: up() failed — down() also failed: " . $e->getMessage(),
            ];
        }
    }

    public function status(): array
    {
        $ran = $this->getRanMigrations();
        $files = glob($this->migrationsDir . '/migration_*.php');
        $all = [];
        foreach ($files as $f) {
            $m = require $f;
            $name = is_array($m) && isset($m['name']) ? $m['name'] : basename($f, '.php');
            $all[] = [
                'name' => $name,
                'file' => basename($f),
                'status' => in_array($name, $ran) ? 'ran' : 'pending',
            ];
        }
        usort($all, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $all;
    }

    /**
     * @return array<string, string>
     */
    private function getNameToFileMap(): array
    {
        $files = glob($this->migrationsDir . '/migration_*.php');
        $map = [];
        foreach ($files as $f) {
            $m = require $f;
            $name = is_array($m) && isset($m['name']) ? $m['name'] : basename($f, '.php');
            $map[$name] = $f;
        }

        return $map;
    }

    /**
     * @return array{rolled: int, errors: array}
     */
    public function rollback(int $steps = 1): array
    {
        return MySqlNamedLock::run($this->db, MySqlNamedLock::LOCK_MIGRATE, 300, function () use ($steps): array {
            $this->ensureMigrationsTable();
            $ran = $this->getRanMigrations();
            if ($ran === []) {
                return ['rolled' => 0, 'errors' => []];
            }
            $toRoll = array_slice(array_reverse($ran), 0, max(1, $steps));
            $nameToFile = $this->getNameToFileMap();
            $rolled = 0;
            $errors = [];

            $deleteStmt = $this->db->prepare("DELETE FROM {$this->table} WHERE name = ?");

            foreach ($toRoll as $name) {
                $file = $nameToFile[$name] ?? null;
                if (!$file) {
                    $errors[] = "{$name}: migration file not found (cannot roll back)";
                    break;
                }
                try {
                    $m = require $file;
                    if (!is_array($m) || !isset($m['down']) || !is_callable($m['down'])) {
                        $errors[] = "{$name}: no callable 'down' (cannot roll back)";
                        break;
                    }
                    ($m['down'])($this->db);
                    $deleteStmt->execute([$name]);
                    $rolled++;
                } catch (\Throwable $e) {
                    $errors[] = "{$name}: " . $e->getMessage();
                    break;
                }
            }

            return ['rolled' => $rolled, 'errors' => $errors];
        });
    }
}
