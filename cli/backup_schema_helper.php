<?php
/**
 * Shared schema snapshot helpers for cli/backup.php and cli/restore.php.
 */

declare(strict_types=1);

/** Profile invitation columns introduced in migration_064 (legacy PAPeR; optional on Simple CMS). */
const PAPER_PROFILE_INVITATION_COLUMNS = [
    'invitation_rsvp',
    'invitation_reason_not_accepting',
    'invitation_reason_not_attending',
    'invitation_specific_needs_specify',
    'invitation_date_received_visit',
    'invitation_first_visit_name',
    'invitation_first_visit_position_org',
    'invitation_first_visit_name_2',
    'invitation_first_visit_position_org_2',
    'invitation_first_visit_date_of_invitation',
    'invitation_second_visit_name',
    'invitation_second_visit_position_org',
    'invitation_second_visit_name_2',
    'invitation_second_visit_position_org_2',
    'invitation_second_visit_date_of_invitation',
    'invitation_distribution_status',
    'invitation_distribution_status_other',
    'invitation_first_visit_status',
    'invitation_second_visit_status',
];

/**
 * Whether a base table exists in the current database (MySQL/MariaDB).
 */
function paper_table_exists(\PDO $db, string $table): bool
{
    $table = trim($table);
    if ($table === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    try {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table]);
        if ($stmt->fetchColumn()) {
            return true;
        }
    } catch (\Throwable $e) {
        // Fall through to SHOW TABLES.
    }

    try {
        $stmt = $db->query('SHOW TABLES LIKE ' . $db->quote($table));
        return (bool) ($stmt && $stmt->fetchColumn());
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * @return list<string> Column names present on profiles at backup/restore time (empty if no profiles table).
 */
function paper_profiles_invitation_columns_present(\PDO $db): array
{
    if (!paper_table_exists($db, 'profiles')) {
        return [];
    }

    $present = [];
    foreach (PAPER_PROFILE_INVITATION_COLUMNS as $col) {
        try {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $present[] = $col;
            }
        } catch (\Throwable $e) {
            return $present;
        }
    }

    return $present;
}

/**
 * @return array{
 *   last_migration: ?string,
 *   profiles_table_present: bool,
 *   profiles_invitation_columns: list<string>,
 *   profiles_invitation_ready: bool
 * }
 */
function paper_backup_schema_snapshot(\PDO $db): array
{
    $lastMigration = null;
    try {
        $stmt = $db->query('SELECT name FROM migrations ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['name'])) {
            $lastMigration = (string) $row['name'];
        }
    } catch (\Throwable $e) {
        $lastMigration = null;
    }

    $profilesPresent = paper_table_exists($db, 'profiles');
    $invitationCols = $profilesPresent ? paper_profiles_invitation_columns_present($db) : [];

    return [
        'last_migration' => $lastMigration,
        'profiles_table_present' => $profilesPresent,
        'profiles_invitation_columns' => $invitationCols,
        'profiles_invitation_ready' => $profilesPresent
            && count($invitationCols) === count(PAPER_PROFILE_INVITATION_COLUMNS),
    ];
}

/**
 * Run pending migrations after restore (aligns schema with current app code).
 *
 * @return array{ran: int, errors: list<string>, warnings: list<string>}
 */
function paper_run_pending_migrations(string $root): array
{
    if (!defined('ROOT')) {
        require_once $root . '/bootstrap.php';
    }

    $runner = new \Core\MigrationRunner(\Core\Database::getInstance());
    $result = $runner->runPending();

    return [
        'ran' => (int) ($result['ran'] ?? 0),
        'errors' => array_values(array_map('strval', $result['errors'] ?? [])),
        'warnings' => array_values(array_map('strval', $result['warnings'] ?? [])),
    ];
}

/**
 * Compare manifest schema block with live DB; print human-readable lines to STDOUT.
 *
 * @param array<string, mixed> $manifestSchema
 */
function paper_print_schema_restore_report(array $manifestSchema, \PDO $db): void
{
    $live = paper_backup_schema_snapshot($db);
    $backupHadProfiles = array_key_exists('profiles_table_present', $manifestSchema)
        ? !empty($manifestSchema['profiles_table_present'])
        : (!empty($manifestSchema['profiles_invitation_ready'])
            || !empty($manifestSchema['profiles_invitation_columns']));
    $liveHasProfiles = (bool) $live['profiles_table_present'];

    $backupMigration = isset($manifestSchema['last_migration']) ? (string) $manifestSchema['last_migration'] : '';
    $liveMigration = $live['last_migration'] ?? '';
    if ($backupMigration !== '' || $liveMigration !== '') {
        fwrite(STDOUT, "Migrations: backup had `{$backupMigration}`; database now has `{$liveMigration}`.\n");
    }

    if (!$liveHasProfiles && !$backupHadProfiles) {
        fwrite(STDOUT, "Schema after restore: CMS schema (no legacy profiles table).\n");
        return;
    }

    $backupReady = !empty($manifestSchema['profiles_invitation_ready']);
    $liveReady = (bool) $live['profiles_invitation_ready'];

    fwrite(STDOUT, "Schema after restore: invitation columns "
        . count($live['profiles_invitation_columns']) . '/' . count(PAPER_PROFILE_INVITATION_COLUMNS)
        . ($liveReady ? " (ready)\n" : " (incomplete — run php cli/migrate.php)\n"));

    if ($backupReady && !$liveReady) {
        fwrite(STDERR, "WARNING: Backup included full invitation schema but restored DB is missing columns.\n");
    }
    if (!$backupReady && $liveReady) {
        fwrite(STDOUT, "Note: Backup predates invitation columns; post-restore migrate added them (existing invitation values may be NULL).\n");
    }
}
