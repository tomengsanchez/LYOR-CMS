<?php
namespace Core;

abstract class Model
{
    protected static string $table = '';
    protected static string $entityType = '';
    /** Attribute definitions: ['attr_name' => 'data_type'] - registered at runtime, not in schema */
    protected static array $attributes = [];

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    protected static function ensureAttribute(string $name): ?int
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM eav_attributes WHERE name = ? AND entity_type = ?');
        $stmt->execute([$name, static::$entityType]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return (int) $row['id'];

        $dataType = static::$attributes[$name] ?? 'string';
        $db->prepare('INSERT INTO eav_attributes (entity_type, name, data_type) VALUES (?, ?, ?)')
            ->execute([static::$entityType, $name, $dataType]);
        return (int) $db->lastInsertId();
    }

    protected static function getAttributeId(string $name): ?int
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM eav_attributes WHERE name = ? AND entity_type = ?');
        $stmt->execute([$name, static::$entityType]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return (int) $row['id'];

        if (isset(static::$attributes[$name])) {
            return self::ensureAttribute($name);
        }
        return null;
    }

    protected static function getValue(int $entityId, string $attrName)
    {
        $attrId = self::getAttributeId($attrName);
        if (!$attrId) return null;
        $stmt = self::db()->prepare('SELECT value FROM eav_values WHERE entity_id = ? AND attribute_id = ?');
        $stmt->execute([$entityId, $attrId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['value'] : null;
    }

    protected static function setValue(int $entityId, string $attrName, $value): void
    {
        $attrId = isset(static::$attributes[$attrName]) ? self::ensureAttribute($attrName) : self::getAttributeId($attrName);
        if (!$attrId) return;
        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM eav_values WHERE entity_id = ? AND attribute_id = ?');
        $stmt->execute([$entityId, $attrId]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($existing) {
            $upd = $db->prepare('UPDATE eav_values SET value = ? WHERE id = ?');
            $upd->execute([$value, $existing['id']]);
        } else {
            $ins = $db->prepare('INSERT INTO eav_values (entity_id, attribute_id, value) VALUES (?, ?, ?)');
            $ins->execute([$entityId, $attrId, $value]);
        }
    }
}
