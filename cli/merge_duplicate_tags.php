<?php
/**
 * One-shot: merge duplicate tags that share the same name (case-insensitive).
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Tag;
use Core\Database;

$db = Database::getInstance();
$before = (int) $db->query("SELECT COUNT(*) FROM cms_tags WHERE LOWER(name) = 'encouragements'")->fetchColumn();
$result = Tag::mergeDuplicatesByName();
$after = (int) $db->query("SELECT COUNT(*) FROM cms_tags WHERE LOWER(name) = 'encouragements'")->fetchColumn();
$slug = $db->query("SELECT slug FROM cms_tags WHERE LOWER(name) = 'encouragements' LIMIT 1")->fetchColumn();

echo "encouragements_before={$before}\n";
echo "encouragements_after={$after}\n";
echo "encouragements_slug=" . (string) $slug . "\n";
echo "merge=" . json_encode($result) . "\n";
