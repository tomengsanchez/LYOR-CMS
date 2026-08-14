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
 * - transactional/module data
 * - lookup/seeded grievance option data
 * - app_settings
 * - non-admin users and related links
 *
 * Usage:
 *   php cli/truncate_fresh_install.php
 *   php cli/truncate_fresh_install.php --yes
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    die("This script must be run from CLI.\n");
}

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Database;

$argv = $argv ?? [];
$assumeYes = in_array('--yes', $argv, true);

if (!$assumeYes) {
    fwrite(STDOUT, "WARNING: This will remove most app data and keep only baseline auth/migrations.\n");
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

$tablesToTruncate = [
    // Core app/module data
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
    'notifications',
    'audit_log',
    'socio_import_batch_projects',
    'profile_socio_version_sections',
    'profile_socio_versions',
    'profile_socio_sections',
    'socio_import_batches',
    'ses_rap_column_maps',
    'email_queue',
    'api_tokens',
    'api_client_events',
    'user_sessions',
    'traffic_events',
    'traffic_ip_blocks',
    'traffic_geo_cache',
    'user_projects',
    'user_list_columns',
    'user_dashboard_config',
    // Lookup/seeded option tables (seed_grievance_options / seed_structure_options will re-seed)
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
];

try {
    $db->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($tablesToTruncate as $table) {
        if (!$exists($table)) {
            continue;
        }
        $db->exec('TRUNCATE TABLE `' . $table . '`');
        $truncated[] = $table;
    }

    // Reset app-wide settings to fresh baseline.
    if ($exists('app_settings')) {
        $db->exec('TRUNCATE TABLE `app_settings`');
        $truncated[] = 'app_settings';
    }

    // Keep admin; delete all other users.
    if ($exists('users')) {
        if ($adminId > 0) {
            $stmt = $db->prepare('DELETE FROM users WHERE id <> ?');
            $stmt->execute([$adminId]);
            $deleted[] = 'users(non-admin)';
        } else {
            // If admin account is missing, keep table but clear rows.
            $db->exec('TRUNCATE TABLE `users`');
            $truncated[] = 'users';
        }
    }

    // Legacy table cleanup: keep only row tied to admin (if exists).
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

    $db->exec('SET FOREIGN_KEY_CHECKS=1');
} catch (Throwable $e) {
    try {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $ignored) {
    }
    fwrite(STDERR, "Error during reset: " . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Fresh-install-style reset complete.\n");
if (!empty($truncated)) {
    fwrite(STDOUT, "Truncated tables (" . count($truncated) . "): " . implode(', ', $truncated) . "\n");
}
if (!empty($deleted)) {
    fwrite(STDOUT, "Deleted rows: " . implode(', ', $deleted) . "\n");
}
fwrite(STDOUT, "Next step: php database/seeders/seed_grievance_options.php && php database/seeders/seed_structure_options.php\n");

