<?php
namespace App\Models;

use App\AuditLog;
use App\MediaImageSizes;
use Core\Auth;
use Core\Database;

class Media
{
    public static function allActive(): array
    {
        return Database::getInstance()->query("
            SELECT m.*, u.username AS uploaded_by_name
            FROM cms_media m
            LEFT JOIN users u ON u.id = m.uploaded_by
            WHERE m.deleted_at IS NULL
            ORDER BY m.created_at DESC
        ")->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_media WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function createFromUpload(array $file, ?string $altText = null): ?int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $original = basename((string) ($file['name'] ?? 'upload'));
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'svg'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }
        $mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? 'application/octet-stream');
        $dir = dirname(__DIR__, 2) . '/public/uploads/media';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }
        $relative = 'media/' . $filename;
        $width = null;
        $height = null;
        if (self::isImageMime($mime)) {
            $size = @getimagesize($dest);
            if (is_array($size)) {
                $width = (int) ($size[0] ?? 0) ?: null;
                $height = (int) ($size[1] ?? 0) ?: null;
            }
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('
            INSERT INTO cms_media (filename, original_name, file_path, mime_type, file_size, width, height, alt_text, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $filename,
            $original,
            $relative,
            $mime,
            (int) ($file['size'] ?? 0),
            $width,
            $height,
            $altText ?: null,
            Auth::id(),
        ]);
        $id = (int) $db->lastInsertId();
        AuditLog::record('media', $id, 'created');
        if (self::isImageMime($mime) && MediaImageSizes::canResizeMime($mime)) {
            MediaImageSizes::generateForMedia($id);
        }
        return $id;
    }

    /**
     * Register a free/external image by URL without downloading (hotlink reference).
     * Stores source_url + caption for attribution.
     */
    public static function createFromExternalUrl(string $url, ?string $altText = null, ?string $caption = null): ?int
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        if (mb_strlen($url) > 500) {
            $url = mb_substr($url, 0, 500);
        }
        $alt = $altText !== null ? trim($altText) : '';
        if (mb_strlen($alt) > 255) {
            $alt = mb_substr($alt, 0, 255);
        }
        $cap = $caption !== null ? trim($caption) : '';
        if ($cap === '') {
            $cap = 'Source: ' . $url;
        }
        if (mb_strlen($cap) > 1000) {
            $cap = mb_substr($cap, 0, 1000);
        }
        $name = basename(parse_url($url, PHP_URL_PATH) ?: 'external-image');
        if ($name === '' || $name === '/') {
            $name = 'external-image';
        }
        if (mb_strlen($name) > 255) {
            $name = mb_substr($name, 0, 255);
        }
        $filename = 'ext_' . bin2hex(random_bytes(8));
        $db = Database::getInstance();
        $stmt = $db->prepare('
            INSERT INTO cms_media (filename, original_name, file_path, mime_type, file_size, width, height, alt_text, source_url, caption, uploaded_by)
            VALUES (?, ?, ?, ?, 0, NULL, NULL, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $filename,
            $name,
            'external',
            'image/jpeg',
            $alt !== '' ? $alt : null,
            $url,
            $cap,
            Auth::id(),
        ]);
        $id = (int) $db->lastInsertId();
        AuditLog::record('media', $id, 'created_external');
        return $id;
    }

    public static function isExternal(object $media): bool
    {
        return trim((string) ($media->file_path ?? '')) === 'external'
            && trim((string) ($media->source_url ?? '')) !== '';
    }

    public static function softDelete(int $id): bool
    {
        $media = self::find($id);
        $stmt = Database::getInstance()->prepare('UPDATE cms_media SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            if ($media) {
                MediaImageSizes::deleteForMedia($id);
            }
            AuditLog::record('media', $id, 'deleted');
            return true;
        }
        return false;
    }

    public static function countActive(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM cms_media WHERE deleted_at IS NULL')->fetchColumn();
    }

    public static function isImageMime(string $mime): bool
    {
        return str_starts_with(strtolower($mime), 'image/') && $mime !== 'image/svg+xml';
    }

    /** Images suitable for featured / social sharing (excludes SVG). */
    public static function listImages(): array
    {
        $rows = Database::getInstance()->query("
            SELECT id, original_name, mime_type, alt_text, width, height, source_url, caption, file_path
            FROM cms_media
            WHERE deleted_at IS NULL
              AND mime_type LIKE 'image/%'
              AND mime_type != 'image/svg+xml'
            ORDER BY created_at DESC
        ")->fetchAll(\PDO::FETCH_OBJ);
        return $rows ?: [];
    }

    public static function publicShareUrl(int $id, string $size = MediaImageSizes::SIZE_FULL): string
    {
        $media = self::find($id);
        if ($media && self::isExternal($media)) {
            return trim((string) $media->source_url);
        }
        return MediaImageSizes::publicUrl($id, $size);
    }

    public static function resolveImageId(?int $id): ?int
    {
        if (!$id || $id < 1) {
            return null;
        }
        $media = self::find($id);
        if (!$media || !self::isImageMime((string) ($media->mime_type ?? ''))) {
            return null;
        }
        return (int) $media->id;
    }

    public static function absolutePath(object $media): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/' . ltrim($media->file_path, '/');
    }

    /**
     * Resolve filesystem path for original or a named size (falls back to original).
     *
     * @return array{path: string, mime: string, width: ?int, height: ?int}|null
     */
    public static function resolveServePath(object $media, string $size = MediaImageSizes::SIZE_FULL): ?array
    {
        if (self::isExternal($media)) {
            return null;
        }
        $size = strtolower(trim($size));
        if ($size !== '' && $size !== MediaImageSizes::SIZE_FULL && MediaImageSizes::isValidSizeName($size)) {
            $row = MediaImageSizes::findSize((int) $media->id, $size);
            if ($row) {
                $path = MediaImageSizes::absolutePathForSize($row);
                if (is_file($path)) {
                    return [
                        'path' => $path,
                        'mime' => (string) ($row->mime_type ?? $media->mime_type ?? 'image/jpeg'),
                        'width' => (int) ($row->width ?? 0) ?: null,
                        'height' => (int) ($row->height ?? 0) ?: null,
                    ];
                }
            }
        }
        $path = self::absolutePath($media);
        if (!is_file($path)) {
            return null;
        }
        return [
            'path' => $path,
            'mime' => (string) ($media->mime_type ?? 'application/octet-stream'),
            'width' => !empty($media->width) ? (int) $media->width : null,
            'height' => !empty($media->height) ? (int) $media->height : null,
        ];
    }

    /**
     * Responsive public <img> for a media id.
     *
     * @param array{class?: string, alt?: string, sizes?: string, preferred_width?: int, loading?: string} $opts
     */
    public static function responsiveImg(int $id, array $opts = []): string
    {
        $media = self::find($id);
        if (!$media || !self::isImageMime((string) ($media->mime_type ?? ''))) {
            return '';
        }
        if (self::isExternal($media)) {
            $src = htmlspecialchars(trim((string) $media->source_url), ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars((string) ($opts['alt'] ?? $media->alt_text ?? ''), ENT_QUOTES, 'UTF-8');
            $class = htmlspecialchars((string) ($opts['class'] ?? 'img-fluid'), ENT_QUOTES, 'UTF-8');
            $loading = htmlspecialchars((string) ($opts['loading'] ?? 'lazy'), ENT_QUOTES, 'UTF-8');
            $img = '<img src="' . $src . '" alt="' . $alt . '" class="' . $class . '" loading="' . $loading . '" decoding="async">';
            $caption = trim((string) ($media->caption ?? ''));
            if ($caption !== '' && !empty($opts['with_caption'])) {
                $img = '<figure class="cms-media-external">' . $img
                    . '<figcaption class="cms-mod-image-caption">' . nl2br(htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')) . '</figcaption></figure>';
            }
            return $img;
        }
        $opts['public'] = true;
        return MediaImageSizes::imgTag($media, $opts);
    }

    /** Payload for admin media pickers / AJAX upload. */
    public static function toPickerItem(object $media): array
    {
        $id = (int) ($media->id ?? 0);
        $external = self::isExternal($media);
        $src = $external ? trim((string) $media->source_url) : '/serve/media/' . $id;
        return [
            'id' => $id,
            'name' => (string) ($media->original_name ?? ''),
            'alt_text' => (string) ($media->alt_text ?? ''),
            'caption' => (string) ($media->caption ?? ''),
            'source_url' => (string) ($media->source_url ?? ''),
            'mime_type' => (string) ($media->mime_type ?? ''),
            'width' => !empty($media->width) ? (int) $media->width : null,
            'height' => !empty($media->height) ? (int) $media->height : null,
            'url' => $src,
            'preview' => $external ? $src : '/serve/media/' . $id . '/medium',
            'thumb' => $external ? $src : '/serve/media/' . $id . '/thumbnail',
            'share_url' => self::publicShareUrl($id),
            'is_external' => $external,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listImagesForPicker(): array
    {
        return array_map(static fn ($m) => self::toPickerItem($m), self::listImages());
    }

    /** Parse media id from /serve/media/{id} or /share/media/{id}[/size] URLs. */
    public static function idFromUrl(string $url): ?int
    {
        if (preg_match('#/(?:serve|share)/media/(\d+)#', $url, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
