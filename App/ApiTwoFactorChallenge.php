<?php
namespace App;

use Core\Database;

/**
 * Short-lived OTP challenges for the API 2FA verify flow.
 *
 * Lifecycle:
 *   POST /api/auth/login → create() (returns challenge_id + raw code emailed separately)
 *   POST /api/auth/2fa/verify → verify($challengeId, $code) → consume() → issue ApiToken
 *   POST /api/auth/2fa/resend → resend($challengeId) → new code, same row, refreshed expires_at
 */
class ApiTwoFactorChallenge
{
    public const VERIFY_OK = 'ok';
    public const VERIFY_NOT_FOUND = 'not_found';
    public const VERIFY_EXPIRED = 'expired';
    public const VERIFY_LOCKED = 'locked';
    public const VERIFY_INVALID_CODE = 'invalid_code';
    public const VERIFY_ALREADY_USED = 'already_used';

    private const DEFAULT_MAX_ATTEMPTS = 5;

    /**
     * Generate a 6-digit code (zero-padded) and persist a challenge row.
     * @return array{challenge_id:string,code:string,expires_at:string}
     */
    public static function create(
        int $userId,
        int $expirationMinutes,
        ?string $ip = null,
        ?string $userAgent = null,
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS
    ): array {
        $expirationMinutes = max(1, min(1440, (int) $expirationMinutes));
        $maxAttempts = max(1, min(20, (int) $maxAttempts));

        $code = self::generateCode();
        $codeHash = self::hashCode($code);
        $challengeId = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', time() + ($expirationMinutes * 60));

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO api_2fa_challenges
             (challenge_id, user_id, code_hash, expires_at, attempts, max_attempts, ip_address, user_agent)
             VALUES (?, ?, ?, ?, 0, ?, ?, ?)'
        );
        $stmt->execute([
            $challengeId,
            $userId,
            $codeHash,
            $expiresAt,
            $maxAttempts,
            $ip !== null ? substr($ip, 0, 45) : null,
            $userAgent !== null ? substr($userAgent, 0, 255) : null,
        ]);

        self::cleanupExpired();

        return [
            'challenge_id' => $challengeId,
            'code' => $code,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Rotate the OTP for an existing challenge (resend flow). Resets attempts
     * and extends expiry. Returns null when challenge is missing/consumed.
     * @return array{challenge_id:string,code:string,expires_at:string}|null
     */
    public static function rotateCode(string $challengeId, int $expirationMinutes): ?array
    {
        $row = self::findRowByChallengeId($challengeId);
        if (!$row || !empty($row->consumed_at)) {
            return null;
        }
        $expirationMinutes = max(1, min(1440, (int) $expirationMinutes));
        $code = self::generateCode();
        $codeHash = self::hashCode($code);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expirationMinutes * 60));
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE api_2fa_challenges
             SET code_hash = ?, expires_at = ?, attempts = 0
             WHERE id = ?'
        );
        $stmt->execute([$codeHash, $expiresAt, (int) $row->id]);
        return [
            'challenge_id' => (string) $row->challenge_id,
            'code' => $code,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Validate a submitted code without consuming. On failure, increments
     * attempts and may transition to LOCKED.
     * @return array{status:string,user_id?:int}
     */
    public static function verify(string $challengeId, string $code): array
    {
        $row = self::findRowByChallengeId($challengeId);
        if (!$row) {
            return ['status' => self::VERIFY_NOT_FOUND];
        }
        if (!empty($row->consumed_at)) {
            return ['status' => self::VERIFY_ALREADY_USED];
        }
        if (strtotime((string) $row->expires_at) < time()) {
            return ['status' => self::VERIFY_EXPIRED];
        }
        if ((int) $row->attempts >= (int) $row->max_attempts) {
            return ['status' => self::VERIFY_LOCKED];
        }

        $codeHash = self::hashCode($code);
        if (!hash_equals((string) $row->code_hash, $codeHash)) {
            self::incrementAttempts((int) $row->id);
            $newAttempts = (int) $row->attempts + 1;
            if ($newAttempts >= (int) $row->max_attempts) {
                return ['status' => self::VERIFY_LOCKED];
            }
            return ['status' => self::VERIFY_INVALID_CODE];
        }

        return [
            'status' => self::VERIFY_OK,
            'user_id' => (int) $row->user_id,
        ];
    }

    /** Mark a challenge as consumed (after successful verify). */
    public static function consume(string $challengeId): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE api_2fa_challenges SET consumed_at = NOW() WHERE challenge_id = ? AND consumed_at IS NULL'
        );
        $stmt->execute([$challengeId]);
    }

    /** Delete expired or consumed challenges older than 24h. */
    public static function cleanupExpired(): void
    {
        $db = Database::getInstance();
        $db->exec(
            'DELETE FROM api_2fa_challenges
             WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
                OR (consumed_at IS NOT NULL AND consumed_at < DATE_SUB(NOW(), INTERVAL 1 DAY))'
        );
    }

    /** Mask email for display in API responses (e.g. te****@example.com). */
    public static function maskEmail(?string $email): ?string
    {
        if (!is_string($email) || $email === '' || strpos($email, '@') === false) {
            return null;
        }
        [$local, $domain] = explode('@', $email, 2);
        if ($local === '') {
            return '***@' . $domain;
        }
        $visible = substr($local, 0, min(2, strlen($local)));
        return $visible . str_repeat('*', max(1, strlen($local) - strlen($visible))) . '@' . $domain;
    }

    private static function findRowByChallengeId(string $challengeId): ?object
    {
        $challengeId = trim($challengeId);
        if ($challengeId === '') {
            return null;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM api_2fa_challenges WHERE challenge_id = ? LIMIT 1');
        $stmt->execute([$challengeId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    private static function incrementAttempts(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE api_2fa_challenges SET attempts = attempts + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    private static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private static function hashCode(string $code): string
    {
        return hash('sha256', trim($code));
    }
}
