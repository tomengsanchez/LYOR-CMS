#!/usr/bin/env php
<?php
/**
 * Reset DB close to fresh-install state (post-migrations baseline).
 *
 * Keeps:
 * - migrations table
 * - roles and role_capabilities
 * - admin user account
 *
 * Clears:
 * - all Simple CMS content (pages, posts, media, menus, …)
 * - app_settings, notifications, audit/email/API session tables
 * - media files under public/uploads/media/ (unless --keep-uploads)
 * - any leftover legacy PAPeR tables if still present
 *
 * By default re-seeds Welcome page, General category, Hello World post,
 * and Primary Menu (Home + Blog) so the site is usable again.
 *
 * Usage:
 *   php cli/truncate_fresh_install.php
 *   php cli/truncate_fresh_install.php --yes
 *   php cli/truncate_fresh_install.php --yes --no-reseed
 *   php cli/truncate_fresh_install.php --yes --keep-uploads
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    die("This script must be run from CLI.\n");
}

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Database;

$argv = $argv ?? [];
$assumeYes = in_array('--yes', $argv, true);
$noReseed = in_array('--no-reseed', $argv, true);
$keepUploads = in_array('--keep-uploads', $argv, true);

if (!$assumeYes) {
    fwrite(STDOUT, "WARNING: This will remove CMS content and most app data; keeps migrations, roles, and admin.\n");
    fwrite(STDOUT, "Type YES to continue: ");
    $confirm = trim((string) fgets(STDIN));
    if ($confirm !== 'YES') {
        fwrite(STDOUT, "Aborted.\n");
        exit(1);
    }
}

$db = Database::getInstance();
$allTables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$exists = static function (string $table) use ($allTables): bool {
    return in_array($table, $allTables, true);
};

$adminId = 0;
if ($exists('users') && $exists('roles')) {
    $stmt = $db->prepare("
        SELECT u.id
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.username = 'admin'
        ORDER BY u.id ASC
        LIMIT 1
    ");
    $stmt->execute();
    $adminId = (int) ($stmt->fetchColumn() ?: 0);
}

$truncated = [];
$deleted = [];
$reseeded = [];

/**
 * Child → parent order for clarity; FOREIGN_KEY_CHECKS=0 makes order safe.
 *
 * @var list<string>
 */
$tablesToTruncate = [
    // Simple CMS content
    'cms_content_revisions',
    'cms_layout_templates',
    'cms_redirects',
    'cms_menu_items',
    'cms_menus',
    'cms_post_tags',
    'cms_tags',
    'cms_comments',
    'cms_widgets',
    'cms_media_sizes',
    'cms_posts',
    'cms_pages',
    'cms_categories',
    'cms_media',
    // Auth / ops (keep users/roles separately)
    'notifications',
    'audit_log',
    'email_queue',
    'api_tokens',
    'api_2fa_challenges',
    'user_sessions',
    'user_password_history',
    'user_dashboard_config',
    'user_list_columns',
    'backup_archives',
    // Legacy PAPeR (no-op when tables were never migrated)
    'project_phases',
    'projects',
    'municipalities',
    'barangays',
    'project_affected_barangays',
    'municipality_projects',
    'profiles',
    'structures',
    'grievances',
    'grievance_status_log',
    'grievance_attachments',
    'grievance_respondents',
    'socio_import_batch_projects',
    'profile_socio_version_sections',
    'profile_socio_versions',
    'profile_socio_sections',
    'socio_import_batches',
    'ses_rap_column_maps',
    'rap_field_definitions',
    'api_client_events',
    'traffic_events',
    'traffic_ip_blocks',
    'traffic_geo_cache',
    'user_projects',
    'grievance_vulnerabilities',
    'grievance_respondent_types',
    'grievance_grm_channels',
    'grievance_preferred_languages',
    'grievance_types',
    'grievance_categories',
    'grievance_progress_levels',
    'structure_tagging_statuses',
    'structure_actual_usages',
    'holidays',
    'profile_structure_tags',
    'profile_attachments',
    'contacts',
    'api_idempotency_keys',
];

/**
 * Remove files under a directory but keep the directory (and .htaccess if present).
 */
$clearDirFiles = static function (string $dir): int {
    if (!is_dir($dir)) {
        return 0;
    }
    $removed = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        /** @var SplFileInfo $item */
        $name = $item->getFilename();
        if ($name === '.htaccess' || $name === '.gitkeep') {
            continue;
        }
        if ($item->isDir()) {
            @rmdir($item->getPathname());
            continue;
        }
        if (@unlink($item->getPathname())) {
            $removed++;
        }
    }
    return $removed;
};

try {
    $db->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($tablesToTruncate as $table) {
        if (!$exists($table)) {
            continue;
        }
        $db->exec('TRUNCATE TABLE `' . $table . '`');
        $truncated[] = $table;
    }

    if ($exists('app_settings')) {
        $db->exec('TRUNCATE TABLE `app_settings`');
        $truncated[] = 'app_settings';
    }

    if ($exists('users')) {
        if ($adminId > 0) {
            $stmt = $db->prepare('DELETE FROM users WHERE id <> ?');
            $stmt->execute([$adminId]);
            $deleted[] = 'users(non-admin)';
        } else {
            $db->exec('TRUNCATE TABLE `users`');
            $truncated[] = 'users';
        }
    }

    if ($exists('user_profiles')) {
        if ($adminId > 0) {
            $stmt = $db->prepare('DELETE FROM user_profiles WHERE user_id <> ? OR user_id IS NULL');
            $stmt->execute([$adminId]);
            $deleted[] = 'user_profiles(non-admin)';
        } else {
            $db->exec('TRUNCATE TABLE `user_profiles`');
            $truncated[] = 'user_profiles';
        }
    }

    if (!$noReseed) {
        $authorId = $adminId > 0 ? $adminId : null;

        if ($exists('cms_pages')) {
            $stmt = $db->prepare("
                INSERT INTO cms_pages (title, slug, body, status, meta_title, author_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'Welcome',
                'welcome',
                '<p>Welcome to Simple CMS. Edit this page from <a href="/admin/pages">Admin → Pages</a>.</p>',
                'published',
                'Welcome — Simple CMS',
                $authorId,
            ]);
            $reseeded[] = 'cms_pages(welcome)';
        }

        $catId = null;
        if ($exists('cms_categories')) {
            $db->prepare("
                INSERT INTO cms_categories (name, slug, description)
                VALUES (?, ?, ?)
            ")->execute(['General', 'general', 'General news and updates']);
            $catId = (int) $db->query("SELECT id FROM cms_categories WHERE slug = 'general' LIMIT 1")->fetchColumn();
            $reseeded[] = 'cms_categories(general)';
        }

        if ($exists('cms_posts')) {
            $db->prepare("
                INSERT INTO cms_posts (title, slug, excerpt, body, category_id, status, published_at, author_id)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
            ")->execute([
                'Hello World',
                'hello-world',
                'Your first blog post.',
                '<p>This is a sample post. Create more from Content → Posts.</p>',
                $catId ?: null,
                'published',
                $authorId,
            ]);
            $reseeded[] = 'cms_posts(hello-world)';
        }

        if ($exists('cms_menus') && $exists('cms_menu_items')) {
            $db->exec("INSERT INTO cms_menus (id, name, location) VALUES (1, 'Primary Menu', 'primary')");
            $db->exec("
                INSERT INTO cms_menu_items (menu_id, label, item_type, custom_url, sort_order) VALUES
                (1, 'Home', 'home', '/', 10),
                (1, 'Blog', 'blog', '/blog', 20)
            ");
            $reseeded[] = 'cms_menus(primary)';
        }
    }

    $db->exec('SET FOREIGN_KEY_CHECKS=1');
} catch (Throwable $e) {
    try {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $ignored) {
    }
    fwrite(STDERR, "Error during reset: " . $e->getMessage() . "\n");
    exit(1);
}

$uploadsRemoved = 0;
if (!$keepUploads) {
    $mediaDir = dirname(__DIR__) . '/public/uploads/media';
    $uploadsRemoved = $clearDirFiles($mediaDir);
}

fwrite(STDOUT, "Fresh-install-style reset complete.\n");
if (!empty($truncated)) {
    fwrite(STDOUT, "Truncated tables (" . count($truncated) . "): " . implode(', ', $truncated) . "\n");
}
if (!empty($deleted)) {
    fwrite(STDOUT, "Deleted rows: " . implode(', ', $deleted) . "\n");
}
if (!empty($reseeded)) {
    fwrite(STDOUT, "Reseeded: " . implode(', ', $reseeded) . "\n");
} elseif ($noReseed) {
    fwrite(STDOUT, "Skipped CMS baseline reseed (--no-reseed).\n");
}
if (!$keepUploads) {
    fwrite(STDOUT, "Cleared media upload files: {$uploadsRemoved}\n");
} else {
    fwrite(STDOUT, "Kept upload files (--keep-uploads).\n");
}
fwrite(STDOUT, "Kept: migrations, roles, role_capabilities, admin user.\n");
fwrite(STDOUT, "Log in at /admin/login (default admin / admin123 unless changed).\n");
