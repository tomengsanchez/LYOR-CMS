<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;
use Core\SystemDebug;

class DebugLogController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
        }
    }

    public function index(): void
    {
        $requestStart = SystemDebug::getRequestStartTime();
        $loadTimeMs = SystemDebug::getLoadTimeMs();
        $timestamp = date('Y-m-d H:i:s', (int) $requestStart) . sprintf('.%03d', ($requestStart - floor($requestStart)) * 1000);

        $queries = SystemDebug::getQueries();
        $classesLoaded = SystemDebug::getClassesLoaded();
        $functions = SystemDebug::getDefinedFunctions();

        $logsDir = dirname(__DIR__, 2) . '/logs';
        $logFiles = [];
        if (is_dir($logsDir)) {
            foreach (glob($logsDir . '/*.log') as $file) {
                $logFiles[] = [
                    'name' => basename($file),
                    'size' => @filesize($file) ?: 0,
                    'modified' => @filemtime($file) ?: null,
                ];
            }
        }

        $this->view('debug_log/index', [
            'timestamp'      => $timestamp,
            'loadTimeMs'     => $loadTimeMs,
            'queries'        => $queries,
            'classesLoaded'  => $classesLoaded,
            'functions'      => $functions,
            'logFiles'       => $logFiles,
        ]);
    }

    public function log(): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            $this->redirect(AdminPath::url());
        }
        $name = isset($_GET['name']) ? (string) $_GET['name'] : '';
        $name = basename($name);
        if ($name === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            http_response_code(400);
            $this->json(['error' => 'Bad Request', 'message' => 'Invalid log name']);
        }
        $logsDir = dirname(__DIR__, 2) . '/logs';
        $path = $logsDir . '/' . $name;
        if (!is_file($path)) {
            http_response_code(404);
            $this->json(['error' => 'Not Found', 'message' => 'Log file not found']);
        }
        $size = @filesize($path) ?: 0;
        $maxBytes = 200000;
        $truncated = false;
        $content = '';
        $fh = @fopen($path, 'rb');
        if ($fh !== false) {
            if ($size > $maxBytes) {
                $truncated = true;
                fseek($fh, -$maxBytes, SEEK_END);
            }
            $content = stream_get_contents($fh) ?: '';
            fclose($fh);
        }
        $this->json([
            'name'      => $name,
            'size'      => $size,
            'modified'  => @filemtime($path) ?: null,
            'truncated' => $truncated,
            'content'   => $content,
        ]);
    }
}
