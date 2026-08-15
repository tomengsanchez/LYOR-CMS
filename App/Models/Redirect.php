<?php
namespace App\Models;

use App\AuditLog;
use App\UserTime;
use Core\Auth;
use Core\Database;

class Redirect
{
    public static function all(): array
    {
        return Database::getInstance()->query('
            SELECT * FROM cms_redirects ORDER BY from_path ASC
        ')->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_redirects WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findActiveByPath(string $path): ?object
    {
        $path = self::normalizePath($path);
        if ($path === '' || $path === '/') {
            return null;
        }
        $stmt = Database::getInstance()->prepare('
            SELECT * FROM cms_redirects WHERE from_path = ? AND is_active = 1 LIMIT 1
        ');
        $stmt->execute([$path]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        // Absolute URL → keep path only for from_path
        if (preg_match('#^https?://#i', $path)) {
            $parts = parse_url($path);
            $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }
        $path = preg_replace('#\s+#', '', $path) ?? $path;
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }
        // Strip fragment
        $hash = strpos($path, '#');
        if ($hash !== false) {
            $path = substr($path, 0, $hash);
        }
        // Collapse duplicate slashes (keep protocol-less)
        $path = preg_replace('#/{2,}#', '/', $path) ?? $path;
        // Drop trailing slash except root
        if (strlen($path) > 1 && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return mb_substr($path, 0, 500);
    }

    public static function normalizeTarget(string $to): string
    {
        $to = trim($to);
        if ($to === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $to)) {
            return mb_substr($to, 0, 1000);
        }
        return self::normalizePath($to) ?: '/';
    }

    public static function isProtectedSource(string $path): bool
    {
        $path = strtolower(self::normalizePath($path));
        $blocked = ['/admin', '/api', '/serve', '/share', '/public'];
        foreach ($blocked as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    public static function create(array $data): int
    {
        $from = self::normalizePath((string) ($data['from_path'] ?? ''));
        $to = self::normalizeTarget((string) ($data['to_url'] ?? ''));
        if ($from === '' || $from === '/' || $to === '' || self::isProtectedSource($from)) {
            return 0;
        }
        if ($from === self::normalizePath($to)) {
            return 0;
        }
        $code = (int) ($data['status_code'] ?? 301);
        if (!in_array($code, [301, 302], true)) {
            $code = 301;
        }
        $now = UserTime::nowSql();
        try {
            $stmt = Database::getInstance()->prepare('
                INSERT INTO cms_redirects (from_path, to_url, status_code, is_active, hit_count, note, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $from,
                $to,
                $code,
                !empty($data['is_active']) ? 1 : 0,
                mb_substr(trim((string) ($data['note'] ?? '')), 0, 255) ?: null,
                Auth::id(),
                $now,
                $now,
            ]);
            $id = (int) Database::getInstance()->lastInsertId();
            if ($id > 0) {
                AuditLog::record('redirect', $id, 'created');
            }
            return $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::find($id);
        if (!$existing) {
            return false;
        }
        $from = self::normalizePath((string) ($data['from_path'] ?? $existing->from_path));
        $to = self::normalizeTarget((string) ($data['to_url'] ?? $existing->to_url));
        if ($from === '' || $from === '/' || $to === '' || self::isProtectedSource($from)) {
            return false;
        }
        $code = (int) ($data['status_code'] ?? $existing->status_code);
        if (!in_array($code, [301, 302], true)) {
            $code = 301;
        }
        try {
            $stmt = Database::getInstance()->prepare('
                UPDATE cms_redirects SET from_path = ?, to_url = ?, status_code = ?, is_active = ?, note = ?, updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $from,
                $to,
                $code,
                !empty($data['is_active']) ? 1 : 0,
                mb_substr(trim((string) ($data['note'] ?? '')), 0, 255) ?: null,
                UserTime::nowSql(),
                $id,
            ]);
            AuditLog::record('redirect', $id, 'updated');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM cms_redirects WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('redirect', $id, 'deleted');
            return true;
        }
        return false;
    }

    public static function recordHit(int $id): void
    {
        Database::getInstance()->prepare('UPDATE cms_redirects SET hit_count = hit_count + 1 WHERE id = ?')->execute([$id]);
    }

    /**
     * If an active redirect matches the request path, send Location and exit.
     */
    public static function applyForRequestPath(?string $path = null): bool
    {
        if ($path === null) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $path = is_string($path) ? $path : '/';
        }
        // Also try without .json
        $candidates = [self::normalizePath($path)];
        if (str_ends_with(strtolower($candidates[0]), '.json')) {
            $candidates[] = self::normalizePath(substr($candidates[0], 0, -5));
        }
        foreach ($candidates as $candidate) {
            $row = self::findActiveByPath($candidate);
            if (!$row) {
                continue;
            }
            self::recordHit((int) $row->id);
            $code = in_array((int) $row->status_code, [301, 302], true) ? (int) $row->status_code : 301;
            $target = (string) $row->to_url;
            header('Location: ' . $target, true, $code);
            exit;
        }
        return false;
    }
}
