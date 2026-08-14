<?php
/**
 * Smoke test: post featured image column and public share helpers.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\AppSettings;
use App\Models\Media;
use App\SocialShare;
use Core\Database;

$db = Database::getInstance();
$cols = $db->query('SHOW COLUMNS FROM cms_posts LIKE ' . $db->quote('featured_image_id'))->fetchAll();
assert(count($cols) === 1, 'cms_posts.featured_image_id column should exist');

$mediaCols = $db->query('SHOW COLUMNS FROM cms_media LIKE ' . $db->quote('width'))->fetchAll();
assert(count($mediaCols) === 1, 'cms_media.width column should exist');

assert(Media::isImageMime('image/jpeg'), 'jpeg is image mime');
assert(!Media::isImageMime('image/svg+xml'), 'svg excluded from featured');
assert(Media::publicShareUrl(5) === rtrim(BASE_URL, '/') . '/share/media/5', 'public share url');

$branding = AppSettings::getBrandingConfig();
$share = SocialShare::forBlog($branding);
assert(!empty($share['title']), 'blog share meta has title');
assert(isset($share['url']), 'blog share meta has url');

echo "cms_posts_featured_smoke_test: OK\n";
