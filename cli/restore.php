#!/usr/bin/env php
<?php
/**
 * Restore from a ZIP created by cli/backup.php (manifest.json + database.sql + uploads/).
 *
 * WARNING: Overwrites data in the database named in config/database.php and merges/replaces public/uploads.
 *
 * Usage:
 *   php cli/restore.php --from=storage/backups/paper-backup-20260417-120000.zip
 *   php cli/restore.php --from=D:\backups\paper-backup.zip --yes
 *   php cli/restore.php --from=file.zip --mysql=C:\xampp\mysql\bin\mysql.exe
 *   php cli/restore.php --from=file.zip --no-uploads
 *   php cli/restore.php --from=file.zip --large-mode
 *   php cli/restore.php --from=file.zip --yes --no-completion-audit
 *   php cli/restore.php --from=file.zip --yes --no-auto-rollback
 *   php cli/restore.php --from=file.zip --yes --force
 *   php cli/restore.php --from=file.zip --yes --keep-extra-tables
 *
 * After SQL import, restore audits INSERT row counts in database.sql against the
 * live DB. On mismatch it rolls back by restoring the latest safety/backup ZIP
 * (prefers the pre-restore snapshot from this run).
 * By default the target schema is wiped before import (no leftover tables) and
 * manifest app/dbname mismatches abort unless --force.
 *
 * Env:
 *   MYSQL_PATH  Path to mysql client executable (optional)
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);

$configPath = $root . '/config/database.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Missing config/database.php\n");
    exit(1);
}
$dbConfig = require $configPath;
$host = (string) ($dbConfig['host'] ?? 'localhost');
$dbname = (string) ($dbConfig['dbname'] ?? '');
$user = (string) ($dbConfig['username'] ?? '');
$pass = (string) ($dbConfig['password'] ?? '');
$charset = (string) ($dbConfig['charset'] ?? 'utf8mb4');

if ($dbname === '') {
    fwrite(STDERR, "config/database.php: dbname is required.\n");
    exit(1);
}

require_once $root . '/bootstrap.php';
require_once $root . '/cli/backup_schema_helper.php';
require_once $root . '/cli/backup_sql_helper.php';
require_once $root . '/cli/restore_sql_helper.php';
require_once $root . '/cli/restore_completion_audit.php';
require_once $root . '/cli/cli_script_args.php';

$argv = $argv ?? [];
$from = null;
$mysqlArg = null;
$assumeYes = paper_cli_has_flag($argv, 'yes');
$noUploads = paper_cli_has_flag($argv, 'no-uploads');
$largeMode = paper_cli_has_flag($argv, 'large-mode');
$skipSafetyBackup = paper_cli_has_flag($argv, 'skip-safety-backup');
$noMigrate = paper_cli_has_flag($argv, 'no-migrate');
$noCompletionAudit = paper_cli_has_flag($argv, 'no-completion-audit');
$noAutoRollback = paper_cli_has_flag($argv, 'no-auto-rollback');
$forceRestore = paper_cli_has_flag($argv, 'force');
$keepExtraTables = paper_cli_has_flag($argv, 'keep-extra-tables');

foreach ($argv as $arg) {
    if (strpos($arg, '--from=') === 0) {
        $from = substr($arg, 7);
    }
    if (strpos($arg, '--mysql=') === 0) {
        $mysqlArg = substr($arg, 8);
    }
}

if ($from === null || $from === '' || !is_file($from)) {
    fwrite(STDERR, "Usage: php cli/restore.php --from=path/to/paper-backup-*.zip [--yes] [--no-uploads] [--large-mode] [--mysql=...] [--no-migrate] [--skip-safety-backup] [--no-completion-audit] [--no-auto-rollback] [--force] [--keep-extra-tables]\n");
    exit(1);
}

if ($largeMode) {
    fwrite(STDOUT, "[large-mode] enabled: restore preflight checks and progress output.\n");
    @set_time_limit(0);
    ini_set('memory_limit', '-1');
}

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "PHP ZipArchive extension is required.\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($from) !== true) {
    fwrite(STDERR, "Cannot open zip: {$from}\n");
    exit(1);
}

if ($largeMode) {
    $compressed = 0;
    $uncompressed = 0;
    $numFiles = $zip->numFiles;
    for ($i = 0; $i < $numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!is_array($stat)) {
            continue;
        }
        $compressed += (int) ($stat['comp_size'] ?? 0);
        $uncompressed += (int) ($stat['size'] ?? 0);
    }
    $extractTargetFree = @disk_free_space(sys_get_temp_dir());
    fwrite(STDOUT, "[large-mode] archive entries={$numFiles}, compressed=" . formatBytes($compressed) . ", uncompressed=" . formatBytes($uncompressed) . "\n");
    if (is_float($extractTargetFree) || is_int($extractTargetFree)) {
        fwrite(STDOUT, "[large-mode] temp free space: " . formatBytes((int) $extractTargetFree) . "\n");
        if ((int) $extractTargetFree < ($uncompressed + (256 * 1024 * 1024))) {
            $zip->close();
            fwrite(STDERR, "[large-mode] insufficient temp free space for extraction.\n");
            exit(1);
        }
    }
}

$workDir = sys_get_temp_dir() . '/paper-restore-' . date('YmdHis') . '-' . getmypid();
if (!mkdir($workDir, 0755, true)) {
    $zip->close();
    fwrite(STDERR, "Cannot create work dir.\n");
    exit(1);
}

if (!$zip->extractTo($workDir)) {
    $zip->close();
    cleanupDir($workDir);
    fwrite(STDERR, "Extract failed.\n");
    exit(1);
}
$zip->close();

$manifestPath = $workDir . '/manifest.json';
$sqlPath = $workDir . '/database.sql';
if (!is_file($manifestPath) || !is_file($sqlPath)) {
    cleanupDir($workDir);
    fwrite(STDERR, "Backup zip must contain manifest.json and database.sql\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($manifest)) {
    cleanupDir($workDir);
    fwrite(STDERR, "Invalid manifest.json\n");
    exit(1);
}

$manifestCheck = paper_validate_restore_manifest($manifest, $dbname, $forceRestore);
foreach ($manifestCheck['warnings'] as $warn) {
    fwrite(STDERR, "WARNING: {$warn}\n");
}
if (!$manifestCheck['ok']) {
    cleanupDir($workDir);
    foreach ($manifestCheck['errors'] as $err) {
        fwrite(STDERR, "ERROR: {$err}\n");
    }
    exit(1);
}

$sqlSize = filesize($sqlPath);
if ($sqlSize === false || $sqlSize < 10) {
    cleanupDir($workDir);
    fwrite(STDERR, "database.sql missing or empty.\n");
    exit(1);
}

$dbPreview = analyzeSqlPreview($sqlPath);
$destDbSummary = getDatabaseSummary($dbname);
$uploadsPreview = null;
if (!$noUploads) {
    $uploadsPreview = analyzeUploadsPreview($workDir . '/uploads', $root . '/public/uploads');
}

fwrite(STDOUT, "\n=== PRE-RESTORE PREVIEW ===\n");
fwrite(STDOUT, "Target DB: {$dbname} @ {$host}\n");
fwrite(STDOUT, "SQL file size: " . formatBytes((int) $sqlSize) . "\n");
fwrite(STDOUT, "SQL preview: DROP TABLE=" . (int) $dbPreview['dropTableCount']
    . ", CREATE TABLE=" . (int) $dbPreview['createTableCount']
    . ", INSERT INTO=" . (int) $dbPreview['insertCount']
    . ", CREATE VIEW=" . (int) $dbPreview['createViewCount'] . "\n");
fwrite(STDOUT, "Destination DB now: tables=" . (int) $destDbSummary['tableCount']
    . ", rows~=" . number_format((int) $destDbSummary['rowCount'])
    . ", size~=" . formatBytes((int) $destDbSummary['dbBytes']) . "\n");
if (!$noUploads) {
    fwrite(STDOUT, "Uploads preview: from-backup files=" . (int) $uploadsPreview['sourceFiles']
        . " (" . formatBytes((int) $uploadsPreview['sourceBytes']) . "), existing files="
        . (int) $uploadsPreview['destFiles'] . " (" . formatBytes((int) $uploadsPreview['destBytes']) . ")\n");
    fwrite(STDOUT, "Uploads changes: replace=" . (int) $uploadsPreview['replaceCount']
        . ", add=" . (int) $uploadsPreview['addCount']
        . ", retain(existing not in backup)=" . (int) $uploadsPreview['retainCount'] . "\n");
    if (!empty($uploadsPreview['sampleReplaced'])) {
        fwrite(STDOUT, "Sample replaced paths:\n");
        foreach ($uploadsPreview['sampleReplaced'] as $p) {
            fwrite(STDOUT, "  - {$p}\n");
        }
    }
    if (!empty($uploadsPreview['sampleRetained'])) {
        fwrite(STDOUT, "Sample retained paths:\n");
        foreach ($uploadsPreview['sampleRetained'] as $p) {
            fwrite(STDOUT, "  - {$p}\n");
        }
    }
} else {
    fwrite(STDOUT, "Uploads: --no-uploads set (no file copy changes).\n");
}
fwrite(STDOUT, "Warnings:\n");
fwrite(STDOUT, "  - Database tables in target `{$dbname}` will be replaced by statements in database.sql.\n");
if (!$keepExtraTables) {
    fwrite(STDOUT, "  - Target schema will be wiped (all tables/views dropped) before import so leftovers cannot survive.\n");
} else {
    fwrite(STDOUT, "  - --keep-extra-tables: tables not in the dump may remain after restore.\n");
}
if (!$noUploads) {
    fwrite(STDOUT, "  - Uploads restore is merge+overwrite: same relative paths replaced, unmatched existing files retained.\n");
}
fwrite(STDOUT, "  - This operation is destructive for DB contents. Ensure you have a current backup.\n");
fwrite(STDOUT, "===========================\n\n");

fwrite(STDOUT, "This will REPLACE tables in `{$dbname}` on `{$host}` and " . ($noUploads ? 'skip uploads' : 'restore uploads under public/uploads') . ".\n");
if (!$assumeYes) {
    fwrite(STDOUT, "Type YES to continue: ");
    $confirm = trim((string) fgets(STDIN));
    if ($confirm !== 'YES') {
        cleanupDir($workDir);
        fwrite(STDOUT, "Aborted.\n");
        exit(1);
    }
}

$preRestoreBackupPath = null;
if ($skipSafetyBackup) {
    fwrite(STDOUT, "Skipping pre-restore safety backup (--skip-safety-backup).\n");
} else {
    fwrite(STDOUT, "Creating pre-restore backup snapshot...\n");
    $preRestoreBackup = createBeforeRestoreBackup($root, $largeMode, basename($from));
    if (!$preRestoreBackup['ok']) {
        cleanupDir($workDir);
        fwrite(STDERR, "Pre-restore backup failed. Restore aborted for safety.\n");
        fwrite(STDERR, $preRestoreBackup['output'] . "\n");
        exit(1);
    }
    fwrite(STDOUT, "Pre-restore backup created: " . $preRestoreBackup['file'] . "\n");
    $candidate = $root . '/storage/backups/' . $preRestoreBackup['file'];
    if (is_file($candidate)) {
        $preRestoreBackupPath = $candidate;
    }
}

fwrite(STDOUT, "Computing expected row counts from backup database.sql...\n");
try {
    $expectedDumpCounts = paper_count_insert_rows_by_table_from_sql_file($sqlPath);
} catch (\Throwable $e) {
    cleanupDir($workDir);
    fwrite(STDERR, "Cannot read database.sql for completion baseline: " . $e->getMessage() . "\n");
    exit(1);
}
fwrite(STDOUT, 'Baseline: ' . count($expectedDumpCounts) . " table(s) with INSERT data in dump.\n");

if (!$keepExtraTables) {
    fwrite(STDOUT, "Wiping target schema (drop all tables/views) before import...\n");
    $wipe = paper_wipe_database_schema(\Core\Database::getInstance());
    fwrite(STDOUT, "Wiped tables={$wipe['tables']}, views={$wipe['views']}.\n");
} else {
    fwrite(STDOUT, "Skipped schema wipe (--keep-extra-tables).\n");
}

$sanitize = paper_sanitize_sql_dump_file($sqlPath);
if ($sanitize['changed']) {
    $parts = [];
    if (!empty($sanitize['stripped_sandbox'])) {
        $parts[] = 'stripped MariaDB sandbox banner';
    }
    if (!empty($sanitize['stripped_generated'])) {
        $parts[] = 'stripped generated column values from INSERT statements (legacy backups)';
    }
    if ($parts === []) {
        $parts[] = 'normalized dump for import';
    }
    fwrite(STDOUT, 'Sanitized SQL dump: ' . implode('; ', $parts) . ".\n");
    $sqlSize = filesize($sqlPath);
    if ($sqlSize === false) {
        $sqlSize = 0;
    }
}

$mysql = resolveMysql($mysqlArg);
if ($mysql !== null) {
    $cnf = $workDir . '/restore.cnf';
    file_put_contents(
        $cnf,
        "[client]\nhost={$host}\nuser={$user}\npassword=" . str_replace(["\n", "\r"], '', $pass) . "\ndefault-character-set={$charset}\n"
    );
    if (function_exists('chmod')) {
        @chmod($cnf, 0600);
    }

    $mysqlVersion = mysqlClientVersionLine($mysql);
    fwrite(STDOUT, 'import_path=mysql' . ($mysqlVersion !== '' ? " ({$mysqlVersion})" : ' (' . $mysql . ')') . "\n");

    $descriptors = [0 => ['file', $sqlPath, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $cmd = [$mysql, '--defaults-extra-file=' . $cnf, $dbname];
    if ($largeMode) {
        fwrite(STDOUT, "[large-mode] importing SQL via mysql client: " . formatBytes((int) $sqlSize) . "\n");
    } else {
        fwrite(STDOUT, 'Importing SQL via mysql client: ' . formatBytes((int) $sqlSize) . "\n");
    }
    $proc = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        @unlink($cnf);
        cleanupDir($workDir);
        fwrite(STDERR, "Failed to start mysql client.\n");
        exit(1);
    }
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    @unlink($cnf);
    if ($code !== 0) {
        cleanupDir($workDir);
        fwrite(STDERR, "mysql import failed (exit {$code}):\n{$err}\n{$out}\n");
        exit(1);
    }
    fwrite(STDOUT, "Database import: mysql client OK\n");
} else {
    fwrite(STDOUT, "import_path=pdo (mysql client not found; slower; large dumps may fail)\n");
    $pdo = \Core\Database::getInstance();
    paper_import_sql_file_with_pdo($pdo, $sqlPath, $largeMode);
    fwrite(STDOUT, "Database import: PDO OK\n");
}

if (!$noCompletionAudit) {
    fwrite(STDOUT, "Running data completion audit (pre-sanitize dump INSERT rows vs live DB)...\n");
    $pdoAudit = \Core\Database::getInstance();
    $completionAudit = paper_compare_dump_counts_to_live($expectedDumpCounts, $pdoAudit);
    $completionAudit['dump_counts'] = $expectedDumpCounts;
    paper_print_completion_audit_report($completionAudit);
    if (empty($completionAudit['ok'])) {
        if ($noAutoRollback) {
            cleanupDir($workDir);
            fwrite(STDERR, "Restore aborted: data completion audit failed (--no-auto-rollback).\n");
            exit(1);
        }

        $exclude = [basename($from)];
        if ($preRestoreBackupPath !== null) {
            $rollbackZip = $preRestoreBackupPath;
        } else {
            $rollbackZip = paper_find_latest_backup_zip($root . '/storage/backups', $exclude);
        }

        if ($rollbackZip === null || !is_file($rollbackZip)) {
            cleanupDir($workDir);
            fwrite(STDERR, "Data completion audit failed and no rollback ZIP was found in storage/backups.\n");
            exit(1);
        }

        fwrite(STDERR, "Data completion audit failed. Auto-rolling back from latest backup: " . basename($rollbackZip) . "\n");
        cleanupDir($workDir);
        $rollback = runAutoRollbackRestore(
            $root,
            $rollbackZip,
            $mysqlArg,
            $noUploads,
            $largeMode,
            $forceRestore,
            $keepExtraTables
        );
        fwrite(STDOUT, $rollback['output']);
        if ($rollback['code'] !== 0) {
            fwrite(STDERR, $rollback['error'] !== '' ? $rollback['error'] . "\n" : "Auto-rollback restore failed.\n");
            exit(1);
        }
        fwrite(STDOUT, "Auto-rollback restore finished. Original restore aborted due to incomplete data.\n");
        exit(1);
    }
} else {
    fwrite(STDOUT, "Skipped data completion audit (--no-completion-audit).\n");
}

if (!$noUploads) {
    $uploadsSrc = $workDir . '/uploads';
    if (is_dir($uploadsSrc)) {
        $destRoot = $root . '/public/uploads';
        if (!is_dir($destRoot)) {
            mkdir($destRoot, 0755, true);
        }
        mirrorDirectory($uploadsSrc, $destRoot, $largeMode);
        fwrite(STDOUT, "Uploads restored to public/uploads\n");
    } else {
        fwrite(STDOUT, "No uploads/ tree in archive (skipped).\n");
    }
}

$pdoAfterRestore = \Core\Database::getInstance();
$manifestSchema = is_array($manifest['schema'] ?? null) ? $manifest['schema'] : [];

if (!$noMigrate) {
    fwrite(STDOUT, "Running pending migrations (post-restore schema alignment)...\n");
    $migrateResult = paper_run_pending_migrations($root);
    if (!empty($migrateResult['errors'])) {
        cleanupDir($workDir);
        foreach ($migrateResult['errors'] as $migrateErr) {
            fwrite(STDERR, "Migration error: {$migrateErr}\n");
        }
        exit(1);
    }
    foreach ($migrateResult['warnings'] ?? [] as $migrateWarn) {
        fwrite(STDOUT, "Migration note: {$migrateWarn}\n");
    }
    if ($migrateResult['ran'] > 0) {
        fwrite(STDOUT, "Ran {$migrateResult['ran']} pending migration(s) after restore.\n");
    } else {
        fwrite(STDOUT, "No pending migrations after restore.\n");
    }
} else {
    fwrite(STDOUT, "Skipped post-restore migrations (--no-migrate).\n");
}

paper_print_schema_restore_report($manifestSchema, $pdoAfterRestore);

cleanupDir($workDir);
fwrite(STDOUT, "Restore finished.\n");
exit(0);

function cleanupDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $path = $fileinfo->getRealPath();
        if ($path && is_file($path)) {
            @unlink($path);
        } elseif ($path && is_dir($path)) {
            @rmdir($path);
        }
    }
    @rmdir($dir);
}

/**
 * @return non-empty-string|null
 */
function resolveMysql(?string $explicit): ?string
{
    $candidates = [];
    if ($explicit !== null && $explicit !== '') {
        $candidates[] = $explicit;
    }
    $env = getenv('MYSQL_PATH');
    if ($env !== false && $env !== '') {
        $candidates[] = $env;
    }
    if (stripos(PHP_OS, 'WIN') === 0) {
        $candidates[] = 'C:\\xampp\\mysql\\bin\\mysql.exe';
    }
    $candidates[] = 'mysql';

    foreach ($candidates as $bin) {
        if ($bin === '') {
            continue;
        }
        if (stripos(PHP_OS, 'WIN') === 0 && is_file($bin)) {
            return $bin;
        }
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open([$bin, '--version'], $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (is_resource($proc)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);
            if ($code === 0) {
                return $bin;
            }
        }
    }
    return null;
}

function mysqlClientVersionLine(string $mysql): string
{
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open([$mysql, '--version'], $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        return '';
    }
    fclose($pipes[0]);
    $out = trim((string) stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    if ($out === '') {
        return '';
    }
    $first = preg_split('/\r\n|\n|\r/', $out)[0] ?? $out;

    return trim((string) $first);
}

function mirrorDirectory(string $src, string $dest, bool $largeMode = false): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $copied = 0;
    foreach ($iterator as $item) {
        /** @var SplFileInfo $item */
        $sub = $iterator->getSubPathname();
        $sub = str_replace('\\', '/', $sub);
        $target = $dest . '/' . $sub;
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            $parent = dirname($target);
            if (!is_dir($parent)) {
                mkdir($parent, 0755, true);
            }
            copy($item->getRealPath(), $target);
            $copied++;
            if ($largeMode && ($copied % 500) === 0) {
                fwrite(STDOUT, "[large-mode] restored {$copied} upload files...\n");
            }
        }
    }
    if ($largeMode) {
        fwrite(STDOUT, "[large-mode] restored total upload files: {$copied}\n");
    }
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $value = (float) max(0, $bytes);
    $i = 0;
    while ($value >= 1024 && $i < count($units) - 1) {
        $value /= 1024;
        $i++;
    }
    return number_format($value, 2) . ' ' . $units[$i];
}

/**
 * Nested restore used for completion-audit auto-rollback (no nested audit/safety loop).
 *
 * @return array{code:int,output:string,error:string}
 */
function runAutoRollbackRestore(
    string $root,
    string $zipPath,
    ?string $mysqlArg,
    bool $noUploads,
    bool $largeMode,
    bool $forceRestore = false,
    bool $keepExtraTables = false
): array {
    $php = PHP_BINARY ?: 'php';
    $cmd = [
        $php,
        $root . '/cli/restore.php',
        '--from=' . $zipPath,
        '--yes',
        '--skip-safety-backup',
        '--no-completion-audit',
        '--no-auto-rollback',
        '--no-migrate',
    ];
    if ($noUploads) {
        $cmd[] = '--no-uploads';
    }
    if ($largeMode) {
        $cmd[] = '--large-mode';
    }
    if ($forceRestore) {
        $cmd[] = '--force';
    }
    if ($keepExtraTables) {
        $cmd[] = '--keep-extra-tables';
    }
    if ($mysqlArg !== null && $mysqlArg !== '') {
        $cmd[] = '--mysql=' . $mysqlArg;
    }

    $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $desc, $pipes, $root, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        return ['code' => 1, 'output' => '', 'error' => 'Could not start auto-rollback restore process.'];
    }
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    return ['code' => $code, 'output' => $stdout, 'error' => $stderr];
}

/**
 * @return array{ok:bool,file:string,output:string}
 */
function createBeforeRestoreBackup(string $root, bool $largeMode, string $restoreSourceFile): array
{
    $php = PHP_BINARY ?: 'php';
    $ts = date('Ymd-His');
    $backupFile = $root . '/storage/backups/paper-before-restore-' . $ts . '.zip';

    $cmd = [
        $php,
        $root . '/cli/backup.php',
        '--output=' . $backupFile,
        '--reason=pre_restore',
        '--restore-source=' . $restoreSourceFile,
    ];
    if ($largeMode) {
        $cmd[] = '--large-mode';
    }

    $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $desc, $pipes, $root, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        return ['ok' => false, 'file' => '', 'output' => 'Could not start backup process.'];
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    $out = trim((string) $stdout);
    $err = trim((string) $stderr);
    $combined = $out;
    if ($err !== '') {
        $combined .= ($combined !== '' ? "\n\n" : '') . "stderr:\n" . $err;
    }

    if ($code !== 0 || !is_file($backupFile)) {
        if ($combined === '') {
            $combined = 'backup.php failed with no output.';
        }
        return ['ok' => false, 'file' => '', 'output' => $combined];
    }

    if ($combined === '') {
        $combined = 'Pre-restore backup completed.';
    }
    return ['ok' => true, 'file' => basename($backupFile), 'output' => $combined];
}

/**
 * @return array{dropTableCount:int,createTableCount:int,insertCount:int,createViewCount:int}
 */
function analyzeSqlPreview(string $sqlPath): array
{
    $stats = [
        'dropTableCount' => 0,
        'createTableCount' => 0,
        'insertCount' => 0,
        'createViewCount' => 0,
    ];
    $fh = fopen($sqlPath, 'rb');
    if ($fh === false) {
        return $stats;
    }
    while (($line = fgets($fh)) !== false) {
        $u = strtoupper(trim($line));
        if (strpos($u, 'DROP TABLE') === 0) {
            $stats['dropTableCount']++;
        } elseif (strpos($u, 'CREATE TABLE') === 0) {
            $stats['createTableCount']++;
        } elseif (strpos($u, 'INSERT INTO') === 0) {
            $stats['insertCount']++;
        } elseif (strpos($u, 'CREATE VIEW') === 0) {
            $stats['createViewCount']++;
        }
    }
    fclose($fh);
    return $stats;
}

/**
 * @return array{tableCount:int,rowCount:int,dbBytes:int}
 */
function getDatabaseSummary(string $dbname): array
{
    try {
        $pdo = \Core\Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS table_count,
                COALESCE(SUM(table_rows),0) AS row_count,
                COALESCE(SUM(data_length + index_length),0) AS db_bytes
            FROM information_schema.tables
            WHERE table_schema = ?
        ");
        $stmt->execute([$dbname]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        return [
            'tableCount' => (int) ($row['table_count'] ?? 0),
            'rowCount' => (int) ($row['row_count'] ?? 0),
            'dbBytes' => (int) ($row['db_bytes'] ?? 0),
        ];
    } catch (\Throwable $e) {
        return ['tableCount' => 0, 'rowCount' => 0, 'dbBytes' => 0];
    }
}

/**
 * @return array{
 *   sourceFiles:int,sourceBytes:int,destFiles:int,destBytes:int,
 *   replaceCount:int,addCount:int,retainCount:int,
 *   sampleReplaced:array<int,string>,sampleRetained:array<int,string>
 * }
 */
function analyzeUploadsPreview(string $src, string $dest): array
{
    $sourceMap = buildRelativeFileMap($src);
    $destMap = buildRelativeFileMap($dest);

    $replace = 0;
    $add = 0;
    $retain = 0;
    $sampleReplaced = [];
    $sampleRetained = [];

    foreach ($sourceMap['files'] as $rel => $_size) {
        if (isset($destMap['files'][$rel])) {
            $replace++;
            if (count($sampleReplaced) < 3) {
                $sampleReplaced[] = $rel;
            }
        } else {
            $add++;
        }
    }
    foreach ($destMap['files'] as $rel => $_size) {
        if (!isset($sourceMap['files'][$rel])) {
            $retain++;
            if (count($sampleRetained) < 3) {
                $sampleRetained[] = $rel;
            }
        }
    }

    return [
        'sourceFiles' => $sourceMap['count'],
        'sourceBytes' => $sourceMap['bytes'],
        'destFiles' => $destMap['count'],
        'destBytes' => $destMap['bytes'],
        'replaceCount' => $replace,
        'addCount' => $add,
        'retainCount' => $retain,
        'sampleReplaced' => $sampleReplaced,
        'sampleRetained' => $sampleRetained,
    ];
}

/**
 * @return array{count:int,bytes:int,files:array<string,int>}
 */
function buildRelativeFileMap(string $baseDir): array
{
    $result = ['count' => 0, 'bytes' => 0, 'files' => []];
    if (!is_dir($baseDir)) {
        return $result;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $base = str_replace('\\', '/', realpath($baseDir) ?: $baseDir);
    $baseLen = strlen($base);

    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) {
            continue;
        }
        $full = str_replace('\\', '/', $file->getRealPath() ?: '');
        if ($full === '') {
            continue;
        }
        $rel = ltrim(substr($full, $baseLen), '/');
        if ($rel === '') {
            continue;
        }
        $size = (int) $file->getSize();
        $result['files'][$rel] = $size;
        $result['count']++;
        $result['bytes'] += max(0, $size);
    }

    return $result;
}
