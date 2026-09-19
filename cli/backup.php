#!/usr/bin/env php
<?php
/**
 * Create a single ZIP backup: SQL dump + public/uploads tree.
 *
 * Preferred: mysqldump (full fidelity). Fallback: PHP PDO exporter (tables + views).
 *
 * Usage:
 *   php cli/backup.php
 *   php cli/backup.php --output=D:\backups\my-backup.zip
 *   php cli/backup.php --mysqldump=C:\xampp\mysql\bin\mysqldump.exe
 *   php cli/backup.php --no-uploads
 *   php cli/backup.php --large-mode
 *
 * Env:
 *   MYSQLDUMP_PATH  Path to mysqldump executable (optional)
 *
 * Output default: storage/backups/paper-backup-YYYYmmdd-HHMMSS.zip
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';
require_once $root . '/cli/backup_schema_helper.php';
require_once $root . '/cli/backup_sql_helper.php';
require_once $root . '/cli/cli_script_args.php';

use Core\Database;
use App\Models\BackupArchive;

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

$argv = $argv ?? [];
$noUploads = paper_cli_has_flag($argv, 'no-uploads');
$largeMode = paper_cli_has_flag($argv, 'large-mode');
$outputArg = null;
$mysqldumpArg = null;
$reasonArg = null;
$restoreSourceArg = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--output=') === 0) {
        $outputArg = substr($arg, 9);
    }
    if (strpos($arg, '--mysqldump=') === 0) {
        $mysqldumpArg = substr($arg, 12);
    }
    if (strpos($arg, '--reason=') === 0) {
        $reasonArg = trim((string) substr($arg, 9));
    }
    if (strpos($arg, '--restore-source=') === 0) {
        $restoreSourceArg = trim((string) substr($arg, 17));
    }
}

$backupsDir = $root . '/storage/backups';
if (!is_dir($backupsDir)) {
    if (!mkdir($backupsDir, 0755, true) && !is_dir($backupsDir)) {
        fwrite(STDERR, "Cannot create {$backupsDir}\n");
        exit(1);
    }
}

$ts = date('Ymd-His');
$defaultZip = $backupsDir . '/paper-backup-' . $ts . '.zip';
$zipPath = $outputArg ?: $defaultZip;
if (is_dir($zipPath)) {
    $zipPath = rtrim($zipPath, '/\\') . '/paper-backup-' . $ts . '.zip';
}

$uploadsRoot = $root . '/public/uploads';
$workDir = sys_get_temp_dir() . '/paper-backup-' . $ts . '-' . getmypid();
if (!mkdir($workDir, 0755, true) && !is_dir($workDir)) {
    fwrite(STDERR, "Cannot create work dir {$workDir}\n");
    exit(1);
}

$sqlFile = $workDir . '/database.sql';

if ($largeMode) {
    fwrite(STDOUT, "[large-mode] enabled: streaming dump, preflight checks, lower zip compression.\n");
    @set_time_limit(0);
    ini_set('memory_limit', '-1');
}

register_shutdown_function(static function () use ($workDir): void {
    if (is_dir($workDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($workDir, RecursiveDirectoryIterator::SKIP_DOTS),
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
        @rmdir($workDir);
    }
});

if ($largeMode) {
    $dbSize = estimateDatabaseBytes($dbname);
    $uploadsSize = (!$noUploads && is_dir($uploadsRoot)) ? estimateDirectorySize($uploadsRoot) : 0;
    $estimatedNeed = $dbSize + $uploadsSize + (512 * 1024 * 1024); // overhead buffer
    $free = @disk_free_space(dirname($zipPath));
    if (is_float($free) || is_int($free)) {
        fwrite(STDOUT, "[large-mode] estimated source size: db=" . formatBytes($dbSize) . ", uploads=" . formatBytes($uploadsSize) . ", required~=" . formatBytes((int) $estimatedNeed) . ", free=" . formatBytes((int) $free) . "\n");
        if ((int) $free < (int) $estimatedNeed) {
            fwrite(STDERR, "[large-mode] insufficient free space for safe backup run.\n");
            exit(1);
        }
    } else {
        fwrite(STDOUT, "[large-mode] warning: could not determine free disk space.\n");
    }
}

$mysqldumpBin = resolveMysqldump($mysqldumpArg);
$dumpMethod = 'pdo_exporter';
if ($mysqldumpBin !== null) {
    $cnf = writeMysqlClientCnf($workDir, $host, $user, $pass);
    $cmd = array_merge(
        [
            $mysqldumpBin,
            '--defaults-extra-file=' . $cnf,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=' . $charset,
            '--add-drop-table',
        ],
        paper_mysqldump_extra_args($mysqldumpBin),
        [$dbname]
    );
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        @unlink($cnf);
        fwrite(STDERR, "Failed to start mysqldump; falling back to PHP PDO exporter.\n");
    } else {
        fclose($pipes[0]);
        $sqlOut = fopen($sqlFile, 'wb');
        if ($sqlOut === false) {
            fclose($pipes[1]);
            fclose($pipes[2]);
            @unlink($cnf);
            proc_close($proc);
            fwrite(STDERR, "Cannot open SQL output file.\n");
            exit(1);
        }
        $written = stream_copy_to_stream($pipes[1], $sqlOut);
        $err = stream_get_contents($pipes[2]);
        fclose($sqlOut);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        @unlink($cnf);
        if ($code !== 0) {
            fwrite(STDERR, "mysqldump failed (exit {$code}): {$err}\n");
            fwrite(STDOUT, "Falling back to PHP PDO exporter (same credentials as the web app).\n");
            if (is_file($sqlFile)) {
                @unlink($sqlFile);
            }
        } elseif ($written === false || $written <= 0 || !is_file($sqlFile) || filesize($sqlFile) <= 0) {
            fwrite(STDERR, "mysqldump produced empty output; falling back to PHP PDO exporter.\n");
            if (is_file($sqlFile)) {
                @unlink($sqlFile);
            }
        } else {
            $dumpMethod = 'mysqldump';
            fwrite(STDOUT, "Database dump: mysqldump OK (" . formatBytes((int) filesize($sqlFile)) . ")\n");
        }
    }
}
if ($dumpMethod !== 'mysqldump') {
    if ($mysqldumpBin === null) {
        fwrite(STDOUT, "mysqldump not found; using PHP PDO exporter (use mysqldump for best results).\n");
    }
    $pdo = Database::getInstance();
    exportDatabaseWithPdo($pdo, $sqlFile);
    fwrite(STDOUT, "Database dump: PDO exporter OK\n");
}

$pdoForManifest = Database::getInstance();
$schemaSnapshot = paper_backup_schema_snapshot($pdoForManifest);

$manifest = [
    'app' => 'SimpleCMS',
    'manifest_version' => 2,
    'created_at' => date('c'),
    'database' => [
        'host' => $host,
        'dbname' => $dbname,
        'charset' => $charset,
    ],
    'schema' => $schemaSnapshot,
    'dump_method' => $dumpMethod,
    'includes_uploads' => !$noUploads,
    'zip' => basename($zipPath),
];
if ($schemaSnapshot['profiles_invitation_ready']) {
    fwrite(STDOUT, "Schema snapshot: invitation card columns present (" . count($schemaSnapshot['profiles_invitation_columns']) . ").\n");
} else {
    fwrite(STDOUT, "Schema snapshot: invitation card columns incomplete — run php cli/migrate.php before relying on invitation backups.\n");
}
if ($reasonArg !== null && $reasonArg !== '') {
    $manifest['backup_reason'] = $reasonArg;
}
if ($restoreSourceArg !== null && $restoreSourceArg !== '') {
    $manifest['restore_source_file'] = $restoreSourceArg;
}

file_put_contents(
    $workDir . '/manifest.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
);

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "PHP ZipArchive extension is required.\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot open zip: {$zipPath}\n");
    exit(1);
}

$zip->addFile($workDir . '/manifest.json', 'manifest.json');
$zip->addFile($sqlFile, 'database.sql');
if ($largeMode && method_exists($zip, 'setCompressionName')) {
    $zip->setCompressionName('manifest.json', ZipArchive::CM_STORE);
    $zip->setCompressionName('database.sql', ZipArchive::CM_STORE);
}

$added = 0;
if ($noUploads) {
    fwrite(STDOUT, "Uploads: skipped (--no-uploads).\n");
} elseif (is_dir($uploadsRoot)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsRoot, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) {
            continue;
        }
        $full = $file->getRealPath();
        if ($full === false) {
            continue;
        }
        $rel = 'uploads/' . substr($full, strlen(realpath($uploadsRoot) ?: $uploadsRoot) + 1);
        $rel = str_replace('\\', '/', $rel);
        $zip->addFile($full, $rel);
        if ($largeMode && method_exists($zip, 'setCompressionName')) {
            $zip->setCompressionName($rel, ZipArchive::CM_STORE);
        }
        $added++;
        if ($largeMode && ($added % 500) === 0) {
            fwrite(STDOUT, "[large-mode] archived {$added} upload files...\n");
        }
    }
    fwrite(STDOUT, "Uploads: included ({$added} files).\n");
} else {
    fwrite(STDOUT, "Uploads: none (directory missing).\n");
}

$zip->close();

try {
    BackupArchive::ensureRecorded(
        basename($zipPath),
        (string) $zipPath,
        (int) (filesize($zipPath) ?: 0),
        $reasonArg !== '' ? $reasonArg : null,
        $restoreSourceArg !== '' ? $restoreSourceArg : null
    );
} catch (\Throwable $e) {
    fwrite(STDOUT, "Backup registry warning: could not write backup_archives record.\n");
}

fwrite(STDOUT, "Backup written: {$zipPath}\n");
if ($noUploads) {
    fwrite(STDOUT, "Keep this file secure; it contains the database dump (uploads excluded).\n");
} else {
    fwrite(STDOUT, "Keep this file secure; it contains the full database and uploads.\n");
}
exit(0);

/**
 * @return non-empty-string|null
 */
function resolveMysqldump(?string $explicit): ?string
{
    $candidates = [];
    if ($explicit !== null && $explicit !== '') {
        $candidates[] = $explicit;
    }
    $env = getenv('MYSQLDUMP_PATH');
    if ($env !== false && $env !== '') {
        $candidates[] = $env;
    }
    if (stripos(PHP_OS, 'WIN') === 0) {
        $candidates[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    }
    $candidates[] = 'mysqldump';

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

function writeMysqlClientCnf(string $workDir, string $host, string $user, string $pass): string
{
    $cnf = $workDir . '/backup.cnf';
    paper_write_mysql_client_cnf($cnf, $host, $user, $pass);

    return $cnf;
}

function exportDatabaseWithPdo(\PDO $pdo, string $outputFile): void
{
    $fp = fopen($outputFile, 'wb');
    if ($fp === false) {
        throw new RuntimeException('Cannot write ' . $outputFile);
    }

    fwrite($fp, "-- Simple CMS backup (PHP PDO exporter — prefer mysqldump when available)\n");
    fwrite($fp, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_NUM);
    $tableNames = array_map(static fn ($r) => $r[0], $tables);
    sort($tableNames);

    foreach ($tableNames as $table) {
        $q = '`' . str_replace('`', '``', $table) . '`';
        $create = $pdo->query('SHOW CREATE TABLE ' . $q)->fetch(\PDO::FETCH_ASSOC);
        if (!$create || empty($create['Create Table'])) {
            continue;
        }
        fwrite($fp, "\nDROP TABLE IF EXISTS {$q};\n");
        fwrite($fp, $create['Create Table'] . ";\n\n");

        $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . $q)->fetchColumn();
        if ($count === 0) {
            continue;
        }

        $colRows = $pdo->query('SHOW COLUMNS FROM ' . $q)->fetchAll(\PDO::FETCH_ASSOC);
        $insertCols = [];
        foreach ($colRows as $colRow) {
            if (paper_column_extra_is_generated($colRow['Extra'] ?? '')) {
                continue;
            }
            $insertCols[] = (string) $colRow['Field'];
        }
        if ($insertCols === []) {
            continue;
        }
        $colNames = array_map(
            static fn (string $c) => '`' . str_replace('`', '``', $c) . '`',
            $insertCols
        );
        $colList = implode(',', $colNames);

        $batch = 200;
        $offset = 0;
        while ($offset < $count) {
            $selectList = implode(',', $colNames);
            $stmt = $pdo->query(
                'SELECT ' . $selectList . ' FROM ' . $q . ' LIMIT ' . (int) $batch . ' OFFSET ' . (int) $offset
            );
            if (!$stmt) {
                break;
            }
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!$rows) {
                break;
            }
            $valuesChunks = [];
            foreach ($rows as $row) {
                $vals = [];
                foreach ($insertCols as $col) {
                    $v = $row[$col] ?? null;
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } elseif (is_numeric($v) && !is_string($v)) {
                        $vals[] = (string) $v;
                    } else {
                        $vals[] = $pdo->quote((string) $v);
                    }
                }
                $valuesChunks[] = '(' . implode(',', $vals) . ')';
            }
            fwrite($fp, 'INSERT INTO ' . $q . ' (' . $colList . ') VALUES ' . implode(',', $valuesChunks) . ";\n");
            $offset += $batch;
        }
    }

    $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(\PDO::FETCH_NUM);
    foreach ($views as $row) {
        $v = $row[0];
        $q = '`' . str_replace('`', '``', $v) . '`';
        $create = $pdo->query('SHOW CREATE VIEW ' . $q)->fetch(\PDO::FETCH_ASSOC);
        if (!$create || empty($create['Create View'])) {
            continue;
        }
        fwrite($fp, "\nDROP VIEW IF EXISTS {$q};\n");
        fwrite($fp, $create['Create View'] . ";\n");
    }

    fwrite($fp, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fp);
}

function estimateDirectorySize(string $path): int
{
    $size = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile()) {
            $s = $file->getSize();
            if ($s > 0) {
                $size += (int) $s;
            }
        }
    }
    return $size;
}

function estimateDatabaseBytes(string $dbname): int
{
    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(data_length + index_length), 0)
            FROM information_schema.tables
            WHERE table_schema = ?
        ");
        $stmt->execute([$dbname]);
        return (int) $stmt->fetchColumn();
    } catch (\Throwable $e) {
        return 0;
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
