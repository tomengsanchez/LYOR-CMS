<?php
/**
 * Shared CLI argv helpers for backup/restore and web-spawned PHP scripts.
 *
 * Boolean flags must be emitted as `--name` (never `----name`). Keys may be
 * passed with or without a leading `--`.
 */

/**
 * @param list<string> $cmd
 * @param array<int|string, mixed> $args
 * @return list<string>
 */
function paper_cli_append_script_args(array $cmd, array $args): array
{
    foreach ($args as $k => $v) {
        if (is_bool($v)) {
            if ($v && is_string($k) && $k !== '') {
                $cmd[] = paper_cli_normalize_long_flag($k);
            }
            continue;
        }
        if ($v === null || $v === false || $v === '') {
            continue;
        }
        if (is_int($k)) {
            $cmd[] = (string) $v;
            continue;
        }
        $cmd[] = paper_cli_normalize_long_flag((string) $k) . '=' . (string) $v;
    }
    return $cmd;
}

function paper_cli_normalize_long_flag(string $name): string
{
    return '--' . ltrim($name, '-');
}

/**
 * @param list<mixed> $argv
 */
function paper_cli_has_flag(array $argv, string $name): bool
{
    $flag = paper_cli_normalize_long_flag($name);
    $prefix = $flag . '=';
    foreach ($argv as $arg) {
        if (!is_string($arg)) {
            continue;
        }
        $arg = trim($arg);
        if ($arg === $flag) {
            return true;
        }
        if (strpos($arg, $prefix) === 0) {
            $v = strtolower(substr($arg, strlen($prefix)));
            return !in_array($v, ['0', 'false', 'no', 'off', ''], true);
        }
    }
    return false;
}

/**
 * @param list<mixed> $argv
 */
function paper_cli_arg_value(array $argv, string $name, ?string $default = null): ?string
{
    $flag = paper_cli_normalize_long_flag($name);
    $prefix = $flag . '=';
    foreach ($argv as $arg) {
        if (!is_string($arg)) {
            continue;
        }
        $arg = trim($arg);
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}
