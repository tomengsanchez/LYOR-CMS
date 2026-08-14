#!/usr/bin/env php
<?php
/**
 * E2E helper for the API 2FA flow.
 *
 * Usage:
 *   php cli/e2e_api_2fa.php --enable                      Toggle enable_email_2fa = 1, set email_provider = 'log'
 *   php cli/e2e_api_2fa.php --disable                     Toggle enable_email_2fa = 0, restore prior provider
 *   php cli/e2e_api_2fa.php --set-code --challenge-id=X --code=Y
 *                                                         Hash Y into api_2fa_challenges.code_hash for challenge X
 *   php cli/e2e_api_2fa.php --latest-for=username         Print JSON for the latest active challenge for a user
 *
 * Designed for Playwright tests; not safe to expose in production.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Database;
use App\Models\AppSettings;

$argv = $argv ?? [];

$flag = static function (string $name) use ($argv): bool {
    return in_array($name, $argv, true);
};
$opt = static function (string $name) use ($argv): ?string {
    $prefix = $name . '=';
    foreach ($argv as $a) {
        if (strpos($a, $prefix) === 0) {
            return substr($a, strlen($prefix));
        }
    }
    return null;
};

$enable = $flag('--enable');
$disable = $flag('--disable');
$setCode = $flag('--set-code');
$latestFor = $opt('--latest-for');

if ($enable) {
    $previous = AppSettings::get('email_provider', 'smtp');
    AppSettings::set('paper_e2e_prev_email_provider', $previous);
    AppSettings::set('email_provider', 'log');
    AppSettings::set('enable_email_2fa', '1');
    if (!AppSettings::get('2fa_expiration_minutes', null)) {
        AppSettings::set('2fa_expiration_minutes', '15');
    }
    $forUser = $opt('--for') ?? 'admin';
    $db = Database::getInstance();
    $stmt = $db->prepare('SELECT id, email FROM users WHERE username = ?');
    $stmt->execute([$forUser]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    $patchedEmail = null;
    if ($row && (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL))) {
        $upd = $db->prepare('UPDATE users SET email = ? WHERE id = ?');
        $upd->execute(['e2e+' . $forUser . '@example.test', (int) $row['id']]);
        $patchedEmail = 'e2e+' . $forUser . '@example.test';
    }
    echo json_encode([
        'enabled' => true,
        'previous_email_provider' => $previous,
        'user' => $forUser,
        'patched_email' => $patchedEmail,
    ]) . "\n";
    exit(0);
}

if ($disable) {
    AppSettings::set('enable_email_2fa', '0');
    $restore = AppSettings::get('paper_e2e_prev_email_provider', null);
    if ($restore !== null && $restore !== '') {
        AppSettings::set('email_provider', (string) $restore);
    }
    echo json_encode(['enabled' => false, 'restored_email_provider' => $restore]) . "\n";
    exit(0);
}

if ($setCode) {
    $challengeId = $opt('--challenge-id');
    $code = $opt('--code');
    if ($challengeId === null || $code === null) {
        fwrite(STDERR, "--set-code requires --challenge-id and --code.\n");
        exit(1);
    }
    $hash = hash('sha256', trim($code));
    $db = Database::getInstance();
    $stmt = $db->prepare('UPDATE api_2fa_challenges SET code_hash = ?, attempts = 0 WHERE challenge_id = ?');
    $stmt->execute([$hash, $challengeId]);
    if ($stmt->rowCount() === 0) {
        fwrite(STDERR, "No challenge with id=$challengeId.\n");
        exit(2);
    }
    echo json_encode(['challenge_id' => $challengeId, 'code_set' => true]) . "\n";
    exit(0);
}

if ($latestFor !== null && $latestFor !== '') {
    $db = Database::getInstance();
    $stmt = $db->prepare(
        'SELECT c.challenge_id, c.user_id, c.expires_at, c.attempts, c.max_attempts, u.username
         FROM api_2fa_challenges c
         INNER JOIN users u ON u.id = c.user_id
         WHERE u.username = ? AND c.consumed_at IS NULL AND c.expires_at > NOW()
         ORDER BY c.id DESC LIMIT 1'
    );
    $stmt->execute([$latestFor]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) {
        fwrite(STDERR, "No active challenge for user $latestFor.\n");
        exit(2);
    }
    echo json_encode($row) . "\n";
    exit(0);
}

fwrite(STDERR, "Usage: --enable | --disable | --set-code --challenge-id=X --code=Y | --latest-for=username\n");
exit(1);
