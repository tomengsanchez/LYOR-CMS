<?php
namespace App\Models;

use App\AuditLog;
use App\CommentRateLimit;
use App\NewsletterMail;
use App\NewsletterSettings;
use Core\Csrf;
use Core\Database;

class NewsletterSubscriber
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    private const CONFIRM_TTL_SECONDS = 604800;

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_UNSUBSCRIBED => 'Unsubscribed',
        ];
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_newsletter_subscribers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?object
    {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return null;
        }
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_newsletter_subscribers WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findByConfirmToken(string $token): ?object
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM cms_newsletter_subscribers WHERE confirm_token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findByUnsubToken(string $token): ?object
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM cms_newsletter_subscribers WHERE unsub_token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /** @return array<int, object> */
    public static function allForAdmin(?string $status = null, string $q = ''): array
    {
        $sql = 'SELECT * FROM cms_newsletter_subscribers WHERE 1=1';
        $params = [];
        if ($status !== null && $status !== '' && isset(self::statuses()[$status])) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $q = trim($q);
        if ($q !== '') {
            $sql .= ' AND email LIKE ?';
            $params[] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], mb_substr($q, 0, 100)) . '%';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 500';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function pendingCount(): int
    {
        return (int) Database::getInstance()
            ->query("SELECT COUNT(*) FROM cms_newsletter_subscribers WHERE status = 'pending'")
            ->fetchColumn();
    }

    public static function confirmedCount(): int
    {
        return (int) Database::getInstance()
            ->query("SELECT COUNT(*) FROM cms_newsletter_subscribers WHERE status = 'confirmed'")
            ->fetchColumn();
    }

    /**
     * Public signup. Always generic from the visitor's perspective except validation errors.
     *
     * @param array<string, mixed> $post
     * @return array{ok: bool, error?: string}
     */
    public static function subscribePublic(array $post): array
    {
        $settings = NewsletterSettings::get();
        if (!$settings->enabled) {
            return ['ok' => false, 'error' => 'Signups are paused.'];
        }
        $honeypot = trim((string) ($post['website'] ?? ''));
        if ($honeypot !== '') {
            return ['ok' => true];
        }
        $ip = CommentRateLimit::clientIp();
        if (self::tooManyAttempts($ip, $settings->rate_limit_per_hour)) {
            return ['ok' => false, 'error' => 'Too many attempts. Please try again later.'];
        }
        self::recordAttempt();

        $email = self::normalizeEmail((string) ($post['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Please enter a valid email address.'];
        }
        if (empty($post['consent'])) {
            return ['ok' => false, 'error' => 'Please confirm you want to receive updates.'];
        }

        $existing = self::findByEmail($email);
        $now = date('Y-m-d H:i:s');
        $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        if ($existing && ($existing->status ?? '') === self::STATUS_CONFIRMED) {
            return ['ok' => true];
        }

        if (!$settings->double_opt_in) {
            self::upsertConfirmed($email, $ip, $ua, $now, $existing);
            return ['ok' => true];
        }

        $token = self::newToken();
        if ($existing) {
            $stmt = Database::getInstance()->prepare('
                UPDATE cms_newsletter_subscribers
                SET status = ?, confirm_token = ?, ip_address = ?, user_agent = ?, consent_at = ?,
                    confirm_sent_at = ?, unsubscribed_at = NULL, updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([
                self::STATUS_PENDING,
                $token,
                $ip !== '' ? $ip : null,
                $ua !== '' ? $ua : null,
                $now,
                $now,
                $now,
                (int) $existing->id,
            ]);
        } else {
            $stmt = Database::getInstance()->prepare('
                INSERT INTO cms_newsletter_subscribers
                    (email, status, confirm_token, ip_address, user_agent, consent_at, confirm_sent_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $email,
                self::STATUS_PENDING,
                $token,
                $ip !== '' ? $ip : null,
                $ua !== '' ? $ua : null,
                $now,
                $now,
                $now,
                $now,
            ]);
        }
        NewsletterMail::sendConfirm($email, $token);
        return ['ok' => true];
    }

    public static function confirmByToken(string $token): string
    {
        $row = self::findByConfirmToken($token);
        if (!$row) {
            return 'invalid';
        }
        $sent = strtotime((string) ($row->confirm_sent_at ?? $row->created_at ?? '')) ?: 0;
        if ($sent > 0 && (time() - $sent) > self::CONFIRM_TTL_SECONDS) {
            return 'expired';
        }
        if (($row->status ?? '') === self::STATUS_CONFIRMED) {
            return 'already';
        }
        self::markConfirmed((int) $row->id);
        return 'ok';
    }

    public static function unsubscribeByToken(string $token): bool
    {
        $row = self::findByUnsubToken($token);
        if (!$row) {
            return false;
        }
        return self::markUnsubscribed((int) $row->id);
    }

    public static function adminConfirm(int $id): bool
    {
        $row = self::find($id);
        if (!$row) {
            return false;
        }
        $ok = self::markConfirmed($id);
        if ($ok) {
            AuditLog::record('newsletter', $id, 'confirmed');
        }
        return $ok;
    }

    public static function adminUnsubscribe(int $id): bool
    {
        $ok = self::markUnsubscribed($id);
        if ($ok) {
            AuditLog::record('newsletter', $id, 'unsubscribed');
        }
        return $ok;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM cms_newsletter_subscribers WHERE id = ?');
        $ok = $stmt->execute([$id]);
        if ($ok && $stmt->rowCount() > 0) {
            AuditLog::record('newsletter', $id, 'deleted');
            return true;
        }
        return false;
    }

    /**
     * @return array<int, object>
     */
    public static function exportRows(?string $status = null): array
    {
        $sql = 'SELECT email, status, consent_at, confirmed_at, unsubscribed_at, created_at
                FROM cms_newsletter_subscribers WHERE 1=1';
        $params = [];
        if ($status !== null && $status !== '' && isset(self::statuses()[$status])) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (mb_strlen($email) > 191) {
            return '';
        }
        return $email;
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function safeReturnPath(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || !str_starts_with($raw, '/') || str_starts_with($raw, '//') || str_contains($raw, '\\')) {
            return '/subscribe';
        }
        if (str_contains($raw, "\n") || str_contains($raw, "\r") || str_contains($raw, '@')) {
            return '/subscribe';
        }
        $parts = parse_url($raw);
        $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/subscribe';
        }
        $query = is_array($parts) && !empty($parts['query']) ? '?' . $parts['query'] : '';
        $out = $path . $query;
        return mb_strlen($out) > 300 ? '/subscribe' : $out;
    }

    public static function currentPath(): string
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = is_string($uri) && $uri !== '' ? $uri : '/';
        return self::safeReturnPath($path);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function renderForm(array $config = [], ?int $widgetId = null): string
    {
        $settings = NewsletterSettings::get();
        if (!$settings->enabled) {
            return '<p class="text-muted small mb-0">Signups are paused.</p>';
        }
        $intro = trim((string) ($config['intro'] ?? ''));
        $placeholder = trim((string) ($config['placeholder'] ?? '')) ?: 'Email address';
        $button = trim((string) ($config['button'] ?? '')) ?: 'Subscribe';
        $consent = trim((string) ($config['consent_label'] ?? ''))
            ?: 'I agree to receive occasional email updates.';
        $suffix = $widgetId !== null ? '-' . (int) $widgetId : '';
        $emailId = 'newsletterEmail' . $suffix;
        $consentId = 'newsletterConsent' . $suffix;
        $hpId = 'newsletterWebsite' . $suffix;
        $html = '';
        if ($intro !== '') {
            $html .= '<p class="widget-newsletter-intro">' . htmlspecialchars($intro) . '</p>';
        }
        $html .= '<form action="/subscribe" method="post" class="widget-newsletter-form" novalidate>';
        $html .= Csrf::field();
        $html .= '<input type="hidden" name="return" value="' . htmlspecialchars(self::currentPath()) . '">';
        $html .= '<div class="visually-hidden" aria-hidden="true">';
        $html .= '<label for="' . htmlspecialchars($hpId) . '">Website</label>';
        $html .= '<input type="text" name="website" id="' . htmlspecialchars($hpId) . '" tabindex="-1" autocomplete="off">';
        $html .= '</div>';
        $html .= '<label class="visually-hidden" for="' . htmlspecialchars($emailId) . '">Email</label>';
        $html .= '<div class="input-group mb-2">';
        $html .= '<input type="email" name="email" id="' . htmlspecialchars($emailId)
            . '" class="form-control" required maxlength="191" placeholder="' . htmlspecialchars($placeholder)
            . '" autocomplete="email">';
        $html .= '<button type="submit" class="btn btn-primary">' . htmlspecialchars($button) . '</button>';
        $html .= '</div>';
        $html .= '<div class="form-check">';
        $html .= '<input class="form-check-input" type="checkbox" name="consent" value="1" id="'
            . htmlspecialchars($consentId) . '" required>';
        $html .= '<label class="form-check-label small" for="' . htmlspecialchars($consentId) . '">'
            . htmlspecialchars($consent) . '</label>';
        $html .= '</div>';
        $html .= '</form>';
        return $html;
    }

    public static function genericThanks(bool $doubleOptIn): string
    {
        return $doubleOptIn
            ? 'If that address can be subscribed, you will receive a confirmation email shortly.'
            : 'If that address can be subscribed, you are on the list.';
    }

    public static function tooManyAttempts(string $ip, int $maxPerHour): bool
    {
        $maxPerHour = max(0, $maxPerHour);
        if ($maxPerHour === 0) {
            return false;
        }
        $now = time();
        $hits = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $hits = $_SESSION['cms_nl_hits'] ?? [];
            if (!is_array($hits)) {
                $hits = [];
            }
            $hits = array_values(array_filter($hits, static function ($t) use ($now) {
                return is_int($t) && $t > $now - 3600;
            }));
            $_SESSION['cms_nl_hits'] = $hits;
            if (count($hits) >= $maxPerHour) {
                return true;
            }
        }
        if ($ip === '') {
            return false;
        }
        $stmt = Database::getInstance()->prepare('
            SELECT COUNT(*) FROM cms_newsletter_subscribers
            WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');
        $stmt->execute([$ip]);
        return (int) $stmt->fetchColumn() >= $maxPerHour;
    }

    public static function recordAttempt(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $hits = $_SESSION['cms_nl_hits'] ?? [];
        if (!is_array($hits)) {
            $hits = [];
        }
        $hits[] = time();
        $_SESSION['cms_nl_hits'] = $hits;
    }

    private static function upsertConfirmed(string $email, string $ip, string $ua, string $now, ?object $existing): void
    {
        $unsub = self::newToken();
        if ($existing) {
            $stmt = Database::getInstance()->prepare('
                UPDATE cms_newsletter_subscribers
                SET status = ?, confirm_token = NULL, unsub_token = COALESCE(unsub_token, ?),
                    ip_address = ?, user_agent = ?, consent_at = COALESCE(consent_at, ?),
                    confirmed_at = COALESCE(confirmed_at, ?), unsubscribed_at = NULL, updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([
                self::STATUS_CONFIRMED,
                $unsub,
                $ip !== '' ? $ip : null,
                $ua !== '' ? $ua : null,
                $now,
                $now,
                $now,
                (int) $existing->id,
            ]);
            return;
        }
        $stmt = Database::getInstance()->prepare('
            INSERT INTO cms_newsletter_subscribers
                (email, status, unsub_token, ip_address, user_agent, consent_at, confirmed_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $email,
            self::STATUS_CONFIRMED,
            $unsub,
            $ip !== '' ? $ip : null,
            $ua !== '' ? $ua : null,
            $now,
            $now,
            $now,
            $now,
        ]);
    }

    private static function markConfirmed(int $id): bool
    {
        $row = self::find($id);
        if (!$row) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $unsub = (string) ($row->unsub_token ?? '');
        if ($unsub === '') {
            $unsub = self::newToken();
        }
        $stmt = Database::getInstance()->prepare('
            UPDATE cms_newsletter_subscribers
            SET status = ?, confirm_token = NULL, unsub_token = ?, confirmed_at = COALESCE(confirmed_at, ?),
                unsubscribed_at = NULL, updated_at = ?
            WHERE id = ?
        ');
        return $stmt->execute([self::STATUS_CONFIRMED, $unsub, $now, $now, $id]);
    }

    private static function markUnsubscribed(int $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = Database::getInstance()->prepare('
            UPDATE cms_newsletter_subscribers
            SET status = ?, confirm_token = NULL, unsubscribed_at = ?, updated_at = ?
            WHERE id = ?
        ');
        return $stmt->execute([self::STATUS_UNSUBSCRIBED, $now, $now, $id]);
    }
}
