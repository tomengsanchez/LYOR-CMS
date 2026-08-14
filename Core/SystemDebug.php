<?php
namespace Core;

/**
 * Collects debug data for the current request: DB queries, class loads, and PHP function info.
 * Used by the System Debug Log page.
 */
class SystemDebug
{
    private static bool $collecting = false;
    private static float $requestStart;
    private static array $queries = [];
    private static array $classesLoaded = [];

    public static function startRequest(): void
    {
        self::$collecting = true;
        self::$requestStart = microtime(true);
        self::$queries = [];
        self::$classesLoaded = [];
    }

    public static function isCollecting(): bool
    {
        return self::$collecting;
    }

    /** @param float $durationSeconds Duration in seconds (e.g. from microtime diff). */
    public static function logQuery(string $sql, $params, float $durationSeconds): void
    {
        if (!self::$collecting) {
            return;
        }
        self::$queries[] = [
            'sql'      => $sql,
            'params'   => $params,
            'duration' => round($durationSeconds * 1000, 2),
            'time'     => round((microtime(true) - self::$requestStart) * 1000, 2),
        ];
    }

    public static function logClass(string $class): void
    {
        if (!self::$collecting) {
            return;
        }
        $t = round((microtime(true) - self::$requestStart) * 1000, 2);
        self::$classesLoaded[] = [
            'class' => $class,
            'time'  => $t,
        ];
    }

    public static function getRequestStartTime(): float
    {
        return self::$requestStart ?? microtime(true);
    }

    public static function getLoadTimeMs(): float
    {
        return round((microtime(true) - (self::$requestStart ?? microtime(true))) * 1000, 2);
    }

    public static function getQueries(): array
    {
        return self::$queries;
    }

    public static function getClassesLoaded(): array
    {
        return self::$classesLoaded;
    }

    /**
     * User-defined functions with file and line (for display).
     * @return array<array{function: string, file: string, line: int}>
     */
    public static function getDefinedFunctions(): array
    {
        $out = [];
        $user = get_defined_functions()['user'] ?? [];
        foreach ($user as $name) {
            try {
                $r = new \ReflectionFunction($name);
                $out[] = [
                    'function' => $name,
                    'file'    => $r->getFileName() ?: '',
                    'line'    => $r->getStartLine() ?: 0,
                ];
            } catch (\Throwable $e) {
                $out[] = ['function' => $name, 'file' => '', 'line' => 0];
            }
        }
        usort($out, fn($a, $b) => strcasecmp($a['function'], $b['function']));
        return $out;
    }
}
