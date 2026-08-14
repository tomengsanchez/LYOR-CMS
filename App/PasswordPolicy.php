<?php
namespace App;

use App\Models\AppSettings;
use Core\Database;

class PasswordPolicy
{
    public static function validate(string $password): ?string
    {
        $password = (string) $password;
        $len = strlen($password);

        $config = AppSettings::getSecurityConfig();
        $minLength = (int) ($config->password_min_length ?? 8);
        $minLength = max(1, min(128, $minLength));

        if ($len < $minLength) {
            return 'Password must be at least ' . $minLength . ' characters long.';
        }

        $requireUpper = !empty($config->password_require_upper);
        $requireLower = !empty($config->password_require_lower);
        $requireNumber = !empty($config->password_require_number);
        $requireSymbol = !empty($config->password_require_symbol);

        if ($requireUpper && !preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }
        if ($requireLower && !preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }
        if ($requireNumber && !preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }
        if ($requireSymbol && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'Password must contain at least one symbol (e.g. !, @, #).';
        }

        return null;
    }

    public static function validateForUser(string $password, int $userId): ?string
    {
        $msg = self::validate($password);
        if ($msg !== null) {
            return $msg;
        }

        $config = AppSettings::getSecurityConfig();
        $historyLimit = (int) ($config->password_history_limit ?? 0);
        if ($historyLimit <= 0) {
            return null;
        }

        $db = Database::getInstance();

        // MySQL + PDO: LIMIT bound as a parameter is sent as a quoted literal and can trigger SQL syntax errors (1064).
        $effectiveLimit = max(1, min(100, $historyLimit));
        $stmt = $db->prepare('SELECT password_hash FROM user_password_history WHERE user_id = ? ORDER BY changed_at DESC, id DESC LIMIT ' . $effectiveLimit);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($rows) {
            foreach ($rows as $row) {
                $hash = (string) ($row['password_hash'] ?? '');
                if ($hash !== '' && password_verify($password, $hash)) {
                    return 'Password cannot be the same as any of the last ' . $effectiveLimit . ' passwords.';
                }
            }
        }

        return null;
    }

    public static function recordPasswordChange(int $userId, string $passwordHash): void
    {
        $config = AppSettings::getSecurityConfig();
        $historyLimit = (int) ($config->password_history_limit ?? 0);

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO user_password_history (user_id, password_hash, changed_at) VALUES (?, ?, NOW())');
        $stmt->execute([$userId, $passwordHash]);

        if ($historyLimit <= 0) {
            return;
        }

        $stmt = $db->prepare('SELECT id FROM user_password_history WHERE user_id = ? ORDER BY changed_at DESC, id DESC');
        $stmt->execute([$userId]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!$ids) {
            return;
        }

        if (count($ids) <= $historyLimit) {
            return;
        }

        $idsToDelete = array_slice($ids, $historyLimit);
        if (empty($idsToDelete)) {
            return;
        }

        $in = implode(',', array_fill(0, count($idsToDelete), '?'));
        $params = array_map('intval', $idsToDelete);

        $sql = 'DELETE FROM user_password_history WHERE user_id = ? AND id IN (' . $in . ')';
        array_unshift($params, $userId);

        $del = $db->prepare($sql);
        $del->execute($params);
    }
}

