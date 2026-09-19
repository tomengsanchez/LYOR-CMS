<?php
/**
 * Smoke: backup schema snapshot must not require legacy PAPeR `profiles` table.
 */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/cli/backup_schema_helper.php';

use Core\Database;

$db = Database::getInstance();

assert(
    !paper_table_exists($db, 'profiles') || paper_table_exists($db, 'cms_pages'),
    'unexpected empty CMS database'
);

$snap = paper_backup_schema_snapshot($db);
assert(is_array($snap), 'snapshot must be array');
assert(array_key_exists('profiles_table_present', $snap), 'profiles_table_present key');
assert(array_key_exists('profiles_invitation_columns', $snap), 'profiles_invitation_columns key');
assert(array_key_exists('profiles_invitation_ready', $snap), 'profiles_invitation_ready key');

if (!$snap['profiles_table_present']) {
    assert($snap['profiles_invitation_columns'] === [], 'no columns without profiles table');
    assert($snap['profiles_invitation_ready'] === false, 'not ready without profiles table');
    // Must not throw — this is the production CMS path.
    paper_print_schema_restore_report($snap, $db);
}

echo "backup_schema_snapshot_cms_test: OK (profiles_table_present="
    . ($snap['profiles_table_present'] ? '1' : '0') . ")\n";
