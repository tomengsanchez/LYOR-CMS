<?php
namespace App\Controllers;

use App\AdminPath;

use App\Models\BackupArchive;
use Core\Auth;
use Core\Controller;

class BackupRestoreController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }

        $saved = !empty($_SESSION['backup_restore_saved']);
        $error = $_SESSION['backup_restore_error'] ?? '';
        $output = $_SESSION['backup_restore_output'] ?? '';
        $latestBackup = $_SESSION['backup_restore_latest'] ?? '';
        unset($_SESSION['backup_restore_saved'], $_SESSION['backup_restore_error'], $_SESSION['backup_restore_output'], $_SESSION['backup_restore_latest']);

        $files = $this->listBackupFiles();

        $this->view('backup_restore/index', [
            'backups' => $files,
            'saved' => $saved,
            'error' => $error,
            'output' => $output,
            'latestBackup' => $latestBackup,
        ]);
    }

    public function createBackup(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }
        @set_time_limit(0);
        $res = $this->runPhpCliScript('backup.php', ['no-uploads' => !empty($_POST['no_uploads'])]);
        if ($res['ok']) {
            $_SESSION['backup_restore_saved'] = true;
            $_SESSION['backup_restore_output'] = $res['output'];
            $files = $this->listBackupFiles();
            $_SESSION['backup_restore_latest'] = !empty($files) ? (string) $files[0]->name : '';
        } else {
            $_SESSION['backup_restore_error'] = 'Backup failed.';
            $_SESSION['backup_restore_output'] = $res['output'];
        }
        $this->redirect(AdminPath::url('system/backup-restore'));
    }

    public function downloadBackup(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
            return;
        }

        $name = basename((string) ($_POST['file'] ?? ''));
        if ($name === '' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
            $_SESSION['backup_restore_error'] = 'Invalid backup file.';
            $this->redirect(AdminPath::url('system/backup-restore'));
            return;
        }
        $path = dirname(__DIR__, 2) . '/storage/backups/' . $name;
        if (!is_file($path)) {
            $_SESSION['backup_restore_error'] = 'Backup file not found.';
            $this->redirect(AdminPath::url('system/backup-restore'));
            return;
        }
        $size = filesize($path);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $name) . '"');
        if ($size !== false) {
            header('Content-Length: ' . $size);
        }
        readfile($path);
        exit;
    }

    /**
     * @param array<int|string,mixed> $args
     * @return array{ok:bool,output:string}
     */
    private function runPhpCliScript(string $scriptName, array $args): array
    {
        $php = $this->resolvePhpCliBinary();
        if ($php === null) {
            return [
                'ok' => false,
                'output' => 'Could not find a working PHP CLI binary. Set PHP_CLI_PATH to your php.exe (example: C:\\xampp\\php\\php.exe).',
            ];
        }
        $script = dirname(__DIR__, 2) . '/cli/' . $scriptName;
        require_once dirname(__DIR__, 2) . '/cli/cli_script_args.php';
        $cmd = paper_cli_append_script_args([$php, $script], $args);

        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $desc, $pipes, dirname(__DIR__, 2), null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            return ['ok' => false, 'output' => 'Could not start CLI process.'];
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
        if ($combined === '') {
            $combined = $code === 0 ? 'Done.' : 'Command failed with no output.';
        }

        return ['ok' => $code === 0, 'output' => $combined];
    }

    private function resolvePhpCliBinary(): ?string
    {
        $candidates = [];
        $env = getenv('PHP_CLI_PATH');
        if (is_string($env) && trim($env) !== '') {
            $candidates[] = trim($env);
        }

        if (stripos(PHP_OS, 'WIN') === 0) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? (string) $_SERVER['DOCUMENT_ROOT'] : '';
            if ($docRoot !== '') {
                $candidates[] = rtrim(dirname($docRoot), '/\\') . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
            }
            $candidates[] = dirname(dirname(__DIR__, 3)) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
            $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe';
        } else {
            $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';
        }

        $candidates[] = 'php';

        foreach ($candidates as $bin) {
            if (!is_string($bin) || trim($bin) === '') {
                continue;
            }
            $bin = trim($bin);
            if (!$this->isValidPhpCliBinary($bin)) {
                continue;
            }
            return $bin;
        }

        return null;
    }

    private function isValidPhpCliBinary(string $bin): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0 && strpos($bin, DIRECTORY_SEPARATOR) !== false && !is_file($bin)) {
            return false;
        }

        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open([$bin, '-r', 'echo PHP_SAPI;'], $desc, $pipes, dirname(__DIR__, 2), null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            return false;
        }
        fclose($pipes[0]);
        $out = trim((string) stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        return $code === 0 && $out === 'cli';
    }

    /**
     * @return array<int,object{name:string,size:int,mtime:int,restoreFile:string}>
     */
    private function listBackupFiles(): array
    {
        $files = [];
        $seen = [];

        try {
            foreach (BackupArchive::allDesc() as $row) {
                $name = (string) ($row->file_name ?? '');
                if ($name === '') {
                    continue;
                }
                $path = dirname(__DIR__, 2) . '/storage/backups/' . $name;
                if (!is_file($path)) {
                    continue;
                }
                $files[] = (object) [
                    'name' => $name,
                    'size' => (int) ($row->file_size ?? (filesize($path) ?: 0)),
                    'mtime' => filemtime($path) ?: 0,
                    'restoreFile' => (string) ($row->restore_source_file ?? ''),
                ];
                $seen[$name] = true;
            }
        } catch (\Throwable $e) {
            // Migration might not be applied yet; fallback to filesystem scan below.
        }

        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        if (is_dir($backupDir)) {
            $items = scandir($backupDir) ?: [];
            foreach ($items as $name) {
                if ($name === '.' || $name === '..' || $name === '.gitignore' || isset($seen[$name])) {
                    continue;
                }
                $full = $backupDir . '/' . $name;
                if (!is_file($full) || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
                    continue;
                }
                $meta = $this->readBackupMetadata($full);
                $files[] = (object) [
                    'name' => $name,
                    'size' => filesize($full) ?: 0,
                    'mtime' => filemtime($full) ?: 0,
                    'restoreFile' => (string) ($meta['restore_file'] ?? ''),
                ];
            }
        }

        usort($files, static fn ($a, $b) => $b->mtime <=> $a->mtime);
        return $files;
    }

    /**
     * @return array{restore_file:string}
     */
    private function readBackupMetadata(string $zipPath): array
    {
        $fallback = ['restore_file' => ''];
        if (!class_exists('ZipArchive')) {
            return $fallback;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return $fallback;
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        $zip->close();
        if (!is_string($manifestRaw) || trim($manifestRaw) === '') {
            return $fallback;
        }

        $manifest = json_decode($manifestRaw, true);
        if (!is_array($manifest)) {
            return $fallback;
        }

        $restoreFile = '';
        if (($manifest['backup_reason'] ?? '') === 'pre_restore') {
            $restoreFile = (string) ($manifest['restore_source_file'] ?? '');
        }

        return ['restore_file' => $restoreFile];
    }
}
