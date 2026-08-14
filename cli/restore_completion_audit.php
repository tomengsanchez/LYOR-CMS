<?php
/**
 * Post-restore data-completion audit: compare INSERT row counts in database.sql
 * to live COUNT(*) per table. Used by cli/restore.php.
 */

declare(strict_types=1);

/**
 * Count value tuples per table from INSERT statements in a SQL dump.
 *
 * @return array<string, int> table => row count
 */
function paper_count_insert_rows_by_table_from_sql(string $sql): array
{
    $sql = paper_strip_mariadb_sandbox_banner($sql);
    $counts = [];
    $offset = 0;
    $len = strlen($sql);

    while ($offset < $len) {
        $pos = stripos($sql, 'INSERT INTO `', $offset);
        if ($pos === false) {
            break;
        }
        $semi = paper_find_sql_statement_end($sql, $pos);
        if ($semi === false) {
            break;
        }
        $stmt = substr($sql, $pos, $semi - $pos + 1);
        $offset = $semi + 1;

        if (!preg_match('/^INSERT INTO `([^`]+)`/i', $stmt, $tableMatch)) {
            continue;
        }
        $table = (string) $tableMatch[1];
        $body = rtrim($stmt, " \t\n\r;");
        $valuesSql = null;
        if (preg_match('/^INSERT INTO `[^`]+`\s*\([^)]+\)\s*VALUES\s*(.+)$/is', $body, $parts)) {
            $valuesSql = trim($parts[1]);
        } elseif (preg_match('/^INSERT INTO `[^`]+`\s*VALUES\s*(.+)$/is', $body, $parts)) {
            $valuesSql = trim($parts[1]);
        }
        if ($valuesSql === null || $valuesSql === '') {
            continue;
        }
        $tuples = paper_sql_parse_value_tuples($valuesSql);
        $counts[$table] = ($counts[$table] ?? 0) + count($tuples);
    }

    ksort($counts);

    return $counts;
}

/**
 * @return array<string, int>
 */
function paper_count_insert_rows_by_table_from_sql_file(string $sqlPath): array
{
    $sql = file_get_contents($sqlPath);
    if ($sql === false) {
        throw new RuntimeException('Cannot read SQL dump for completion audit: ' . $sqlPath);
    }

    return paper_count_insert_rows_by_table_from_sql($sql);
}

/**
 * Live row counts for the given tables (missing tables reported as -1).
 *
 * @param list<string> $tables
 * @return array<string, int>
 */
function paper_live_table_row_counts(\PDO $db, array $tables): array
{
    $out = [];
    foreach ($tables as $table) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            $out[$table] = -1;
            continue;
        }
        try {
            $stmt = $db->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`');
            $out[$table] = $stmt ? (int) $stmt->fetchColumn() : -1;
        } catch (\Throwable $e) {
            $out[$table] = -1;
        }
    }

    return $out;
}

/**
 * Compare dump INSERT row counts to live DB.
 *
 * @param array<string, int> $dumpCounts
 * @return array{
 *   ok: bool,
 *   compared: int,
 *   mismatches: list<array{table: string, dump: int, live: int}>,
 *   tables: array<string, array{dump: int, live: int, ok: bool}>
 * }
 */
function paper_compare_dump_counts_to_live(array $dumpCounts, \PDO $db): array
{
    $live = paper_live_table_row_counts($db, array_keys($dumpCounts));
    $tables = [];
    $mismatches = [];

    foreach ($dumpCounts as $table => $dumpRows) {
        $liveRows = $live[$table] ?? -1;
        $ok = ($liveRows === (int) $dumpRows);
        $tables[$table] = [
            'dump' => (int) $dumpRows,
            'live' => (int) $liveRows,
            'ok' => $ok,
        ];
        if (!$ok) {
            $mismatches[] = [
                'table' => $table,
                'dump' => (int) $dumpRows,
                'live' => (int) $liveRows,
            ];
        }
    }

    return [
        'ok' => $mismatches === [],
        'compared' => count($tables),
        'mismatches' => $mismatches,
        'tables' => $tables,
    ];
}

/**
 * Full audit: read SQL file, compare to live DB.
 *
 * @return array{
 *   ok: bool,
 *   compared: int,
 *   mismatches: list<array{table: string, dump: int, live: int}>,
 *   tables: array<string, array{dump: int, live: int, ok: bool}>,
 *   dump_counts: array<string, int>
 * }
 */
function paper_audit_sql_file_vs_database(string $sqlPath, \PDO $db): array
{
    $dumpCounts = paper_count_insert_rows_by_table_from_sql_file($sqlPath);
    $result = paper_compare_dump_counts_to_live($dumpCounts, $db);
    $result['dump_counts'] = $dumpCounts;

    return $result;
}

/**
 * Print a short completion-audit report to STDOUT/STDERR.
 *
 * @param array{ok: bool, compared: int, mismatches: list<array{table: string, dump: int, live: int}>} $audit
 */
function paper_print_completion_audit_report(array $audit): void
{
    $compared = (int) ($audit['compared'] ?? 0);
    if (!empty($audit['ok'])) {
        fwrite(STDOUT, "Data completion audit: OK ({$compared} table(s) matched dump INSERT row counts).\n");

        return;
    }

    fwrite(STDERR, "Data completion audit: FAILED ({$compared} table(s) compared).\n");
    foreach ($audit['mismatches'] ?? [] as $row) {
        $table = (string) ($row['table'] ?? '?');
        $dump = (int) ($row['dump'] ?? 0);
        $live = (int) ($row['live'] ?? 0);
        fwrite(STDERR, "  - {$table}: dump={$dump}, live={$live}\n");
    }
}

/**
 * Newest paper-backup / paper-before-restore ZIP under storage/backups.
 *
 * @param list<string> $excludeBasenames
 */
function paper_find_latest_backup_zip(string $backupsDir, array $excludeBasenames = []): ?string
{
    if (!is_dir($backupsDir)) {
        return null;
    }
    $exclude = [];
    foreach ($excludeBasenames as $name) {
        $base = basename((string) $name);
        if ($base !== '') {
            $exclude[$base] = true;
        }
    }

    $candidates = [];
    foreach (glob($backupsDir . '/paper-backup-*.zip') ?: [] as $path) {
        $candidates[] = $path;
    }
    foreach (glob($backupsDir . '/paper-before-restore-*.zip') ?: [] as $path) {
        $candidates[] = $path;
    }

    $best = null;
    $bestMtime = -1;
    foreach ($candidates as $path) {
        if (!is_file($path)) {
            continue;
        }
        $base = basename($path);
        if (isset($exclude[$base])) {
            continue;
        }
        $mtime = (int) filemtime($path);
        if ($mtime > $bestMtime) {
            $bestMtime = $mtime;
            $best = $path;
        }
    }

    return $best;
}

/**
 * Allowed backup manifest app ids (Simple CMS + legacy PAPeR archives).
 *
 * @return list<string>
 */
function paper_backup_allowed_apps(): array
{
    return ['SimpleCMS', 'PAPeR'];
}

function paper_backup_app_is_allowed(string $app): bool
{
    $app = trim($app);
    if ($app === '') {
        return false;
    }
    foreach (paper_backup_allowed_apps() as $allowed) {
        if (strcasecmp($app, $allowed) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Primary app id written by current backups.
 */
function paper_backup_expected_app(): string
{
    return 'SimpleCMS';
}

/**
 * Validate backup manifest app + dbname against restore target.
 *
 * @param array<string, mixed> $manifest
 * @return array{ok: bool, errors: list<string>, warnings: list<string>}
 */
function paper_validate_restore_manifest(array $manifest, string $targetDbname, bool $force): array
{
    $errors = [];
    $warnings = [];

    $app = isset($manifest['app']) ? trim((string) $manifest['app']) : '';
    $expected = paper_backup_expected_app();
    $allowed = implode('`, `', paper_backup_allowed_apps());
    if ($app === '') {
        $msg = 'Backup manifest is missing app id (expected `' . $expected . '`).';
        if ($force) {
            $warnings[] = $msg . ' Continuing because --force was set.';
        } else {
            $errors[] = $msg . ' Use a Simple CMS backup or pass --force.';
        }
    } elseif (!paper_backup_app_is_allowed($app)) {
        $msg = "Backup manifest app `{$app}` is not recognized (allowed: `{$allowed}`).";
        if ($force) {
            $warnings[] = $msg . ' Continuing because --force was set.';
        } else {
            $errors[] = $msg . ' Refusing foreign backup. Pass --force to override.';
        }
    }

    $manifestDb = isset($manifest['database']['dbname']) ? trim((string) $manifest['database']['dbname']) : '';
    if ($manifestDb !== '' && $manifestDb !== $targetDbname) {
        $msg = "Backup dbname in manifest ({$manifestDb}) differs from config ({$targetDbname}).";
        if ($force) {
            $warnings[] = $msg . ' Continuing because --force was set; import target remains `' . $targetDbname . '`.';
        } else {
            $errors[] = $msg . ' Pass --force to allow restoring into a differently named database.';
        }
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'warnings' => $warnings,
    ];
}

/**
 * Drop all base tables and views in the current database so leftovers cannot survive restore.
 *
 * @return array{tables: int, views: int}
 */
function paper_wipe_database_schema(\PDO $db): array
{
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = 0;
    $views = 0;

    try {
        $stmt = $db->query('SHOW FULL TABLES');
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_NUM) : [];
        foreach ($rows as $row) {
            $name = (string) ($row[0] ?? '');
            $type = strtoupper((string) ($row[1] ?? 'BASE TABLE'));
            if ($name === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                continue;
            }
            $safe = '`' . str_replace('`', '``', $name) . '`';
            if ($type === 'VIEW') {
                $db->exec('DROP VIEW IF EXISTS ' . $safe);
                $views++;
            } else {
                $db->exec('DROP TABLE IF EXISTS ' . $safe);
                $tables++;
            }
        }
    } finally {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    return ['tables' => $tables, 'views' => $views];
}
