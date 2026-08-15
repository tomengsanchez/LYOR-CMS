<?php
namespace App\Models;

use App\AuditLog;
use App\LayoutBuilder;
use App\UserTime;
use Core\Auth;
use Core\Database;

class LayoutTemplate
{
    /** @return array<int, object> */
    public static function all(): array
    {
        return Database::getInstance()->query('
            SELECT t.id, t.name, t.created_at, t.updated_at, u.username AS created_by_name
            FROM cms_layout_templates t
            LEFT JOIN users u ON u.id = t.created_by
            ORDER BY t.name ASC
        ')->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_layout_templates WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(string $name, ?string $layoutJson): int
    {
        $name = mb_substr(trim($name), 0, 120);
        $normalized = LayoutBuilder::normalizeJson($layoutJson);
        if ($name === '' || $normalized === null) {
            return 0;
        }
        $now = UserTime::nowSql();
        $stmt = Database::getInstance()->prepare('
            INSERT INTO cms_layout_templates (name, layout_json, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$name, $normalized, Auth::id(), $now, $now]);
        $id = (int) Database::getInstance()->lastInsertId();
        if ($id > 0) {
            AuditLog::record('layout_template', $id, 'created');
        }
        return $id;
    }

    public static function update(int $id, string $name, ?string $layoutJson): bool
    {
        if (!self::find($id)) {
            return false;
        }
        $name = mb_substr(trim($name), 0, 120);
        $normalized = LayoutBuilder::normalizeJson($layoutJson);
        if ($name === '' || $normalized === null) {
            return false;
        }
        $stmt = Database::getInstance()->prepare('
            UPDATE cms_layout_templates SET name = ?, layout_json = ?, updated_at = ? WHERE id = ?
        ');
        $stmt->execute([$name, $normalized, UserTime::nowSql(), $id]);
        AuditLog::record('layout_template', $id, 'updated');
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM cms_layout_templates WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('layout_template', $id, 'deleted');
            return true;
        }
        return false;
    }

    /** @return array{version: int, sections: array}|null */
    public static function layoutArray(object $row): ?array
    {
        $parsed = LayoutBuilder::parse((string) ($row->layout_json ?? ''));
        return !empty($parsed['sections']) ? $parsed : null;
    }
}
