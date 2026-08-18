<?php
namespace App;

use App\Models\AppSettings;
use Core\Auth;
use Core\Database;

/**
 * Optional visitor password on published pages and posts.
 * Stores PHP password_hash only; never echo hashes in views or API.
 */
class ContentPassword
{
    public const MIN_LENGTH = 6;
    public const MAX_LENGTH = 72;
    public const COOKIE_DAYS = 10;
    public const SETTING_KEY = 'cms_content_pass_key';
    public const FAIL_WINDOW = 900;
    public const FAIL_MAX = 8;

    public static function gateMessage(): string
    {
        return 'This content is password protected.';
    }

    public static function has(object $entity): bool
    {
        return trim((string) ($entity->password_hash ?? '')) !== '';
    }

    public static function openSql(string $alias = 'p'): string
    {
        $col = $alias === '' ? 'password_hash' : $alias . '.password_hash';
        return '(' . $col . ' IS NULL OR ' . $col . " = '')";
    }

    public static function isLocked(string $type, object $entity): bool
    {
        if (!self::has($entity)) {
            return false;
        }
        $cap = $type === 'page' ? 'edit_pages' : 'edit_posts';
        if (Auth::can($cap)) {
            return false;
        }
        return !self::isUnlocked($type, $entity);
    }

    public static function isUnlocked(string $type, object $entity): bool
    {
        if (!in_array($type, ['page', 'post'], true) || !self::has($entity)) {
            return false;
        }
        $id = (int) ($entity->id ?? 0);
        if ($id <= 0) {
            return false;
        }
        $expected = self::token($type, $entity);
        $session = $_SESSION['cms_pw'][$type . ':' . $id] ?? '';
        if (is_string($session) && $session !== '' && hash_equals($expected, $session)) {
            return true;
        }
        $cookie = $_COOKIE[self::cookieName($type, $id)] ?? '';
        if (is_string($cookie) && $cookie !== '' && hash_equals($expected, $cookie)) {
            $_SESSION['cms_pw'][$type . ':' . $id] = $expected;
            return true;
        }
        return false;
    }

    public static function verify(object $entity, string $plain): bool
    {
        $hash = (string) ($entity->password_hash ?? '');
        if ($hash === '' || $plain === '') {
            return false;
        }
        return password_verify($plain, $hash);
    }

    public static function remember(string $type, object $entity): void
    {
        if (!in_array($type, ['page', 'post'], true) || !self::has($entity)) {
            return;
        }
        $id = (int) ($entity->id ?? 0);
        if ($id <= 0) {
            return;
        }
        $token = self::token($type, $entity);
        $_SESSION['cms_pw'][$type . ':' . $id] = $token;
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(self::cookieName($type, $id), $token, [
            'expires' => time() + (self::COOKIE_DAYS * 86400),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $secure,
        ]);
    }

    public static function tooManyAttempts(): bool
    {
        self::pruneFails();
        $fails = $_SESSION['cms_pw_fails'] ?? [];
        return count($fails) >= self::FAIL_MAX;
    }

    public static function recordFail(): void
    {
        self::pruneFails();
        $_SESSION['cms_pw_fails'][] = time();
    }

    public static function requestWriteFields(): array
    {
        return [
            'content_password' => (string) ($_POST['content_password'] ?? ''),
            'remove_content_password' => !empty($_POST['remove_content_password']),
        ];
    }

    public static function writeError(array $data): ?string
    {
        if (!empty($data['remove_content_password'])) {
            return null;
        }
        return self::plainError((string) ($data['content_password'] ?? ''));
    }

    public static function plainError(string $plain): ?string
    {
        $plain = trim($plain);
        if ($plain === '') {
            return null;
        }
        $len = strlen($plain);
        if ($len < self::MIN_LENGTH) {
            return 'Content password must be at least ' . self::MIN_LENGTH . ' characters.';
        }
        if ($len > self::MAX_LENGTH) {
            return 'Content password is too long.';
        }
        return null;
    }

    public static function hashFromWrite(array $data, ?string $existingHash = null): ?string
    {
        $existing = trim((string) $existingHash);
        $existing = $existing === '' ? null : $existing;
        if (!empty($data['remove_content_password'])) {
            return null;
        }
        $plain = trim((string) ($data['content_password'] ?? ''));
        if ($plain === '') {
            return $existing;
        }
        if (self::plainError($plain) !== null) {
            return $existing;
        }
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        return is_string($hash) ? $hash : $existing;
    }

    public static function persistHash(string $table, int $id, ?string $hash): void
    {
        if (!in_array($table, ['cms_pages', 'cms_posts'], true) || $id <= 0) {
            return;
        }
        $stmt = Database::getInstance()->prepare(
            'UPDATE ' . $table . ' SET password_hash = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$hash, $id]);
    }

    public static function storedHash(object $entity): ?string
    {
        $hash = trim((string) ($entity->password_hash ?? ''));
        return $hash === '' ? null : $hash;
    }

    public static function withoutHash(?object $row): ?object
    {
        if ($row === null) {
            return null;
        }
        $out = clone $row;
        unset($out->password_hash);
        $out->password_protected = self::has($row);
        return $out;
    }

    /** @param list<object> $rows */
    public static function withoutHashList(array $rows): array
    {
        return array_map([self::class, 'withoutHash'], $rows);
    }

    /** @return array<string, mixed> */
    public static function publicJsonStub(string $type, object $entity, string $url = ''): array
    {
        return [
            'type' => $type,
            'title' => (string) ($entity->title ?? ''),
            'slug' => (string) ($entity->slug ?? ''),
            'url' => $url,
            'protected' => true,
            'summary' => self::gateMessage(),
        ];
    }

    /**
     * @param array<string, mixed> $seo
     * @return array<string, mixed>
     */
    public static function redactSeo(array $seo, object $entity): array
    {
        $msg = self::gateMessage();
        if (isset($seo['share']) && is_array($seo['share'])) {
            $seo['share']['description'] = $msg;
        }
        $seo['llm_summary'] = '';
        $seo['citation_snippet'] = '';
        $title = (string) ($seo['share']['title'] ?? $entity->title ?? '');
        $url = (string) ($seo['share']['url'] ?? '');
        $seo['json_ld'] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $msg,
            'url' => $url,
        ];
        return $seo;
    }

    private static function token(string $type, object $entity): string
    {
        return hash_hmac(
            'sha256',
            $type . '|' . (int) ($entity->id ?? 0) . '|' . (string) ($entity->password_hash ?? ''),
            self::secret()
        );
    }

    private static function cookieName(string $type, int $id): string
    {
        return 'cms_cp_' . $type . '_' . $id;
    }

    private static function secret(): string
    {
        $key = AppSettings::get(self::SETTING_KEY, '');
        if (!is_string($key) || strlen($key) < 32) {
            $key = bin2hex(random_bytes(32));
            AppSettings::set(self::SETTING_KEY, $key);
        }
        return $key;
    }

    private static function pruneFails(): void
    {
        $cut = time() - self::FAIL_WINDOW;
        $fails = $_SESSION['cms_pw_fails'] ?? [];
        if (!is_array($fails)) {
            $fails = [];
        }
        $_SESSION['cms_pw_fails'] = array_values(array_filter(
            $fails,
            static fn ($t) => is_int($t) && $t >= $cut
        ));
    }
}
