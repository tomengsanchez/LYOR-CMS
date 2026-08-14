<?php
namespace App;

use App\Models\Media;
use Core\Database;

/**
 * WordPress-style intermediate image sizes + GD resize.
 */
class MediaImageSizes
{
    public const SIZE_THUMBNAIL = 'thumbnail';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_MEDIUM_LARGE = 'medium_large';
    public const SIZE_LARGE = 'large';
    public const SIZE_1536 = '1536x1536';
    public const SIZE_2048 = '2048x2048';
    public const SIZE_FULL = 'full';

    /**
     * Registered sizes: max width/height (0 = unconstrained on that axis).
     * Thumbnail is cropped square; others scale proportionally (no upscale).
     *
     * @return array<string, array{width: int, height: int, crop: bool}>
     */
    public static function registered(): array
    {
        return [
            self::SIZE_THUMBNAIL => ['width' => 150, 'height' => 150, 'crop' => true],
            self::SIZE_MEDIUM => ['width' => 300, 'height' => 300, 'crop' => false],
            self::SIZE_MEDIUM_LARGE => ['width' => 768, 'height' => 0, 'crop' => false],
            self::SIZE_LARGE => ['width' => 1024, 'height' => 1024, 'crop' => false],
            self::SIZE_1536 => ['width' => 1536, 'height' => 1536, 'crop' => false],
            self::SIZE_2048 => ['width' => 2048, 'height' => 2048, 'crop' => false],
        ];
    }

    public static function isValidSizeName(string $name): bool
    {
        $name = strtolower(trim($name));
        if ($name === '' || $name === self::SIZE_FULL) {
            return true;
        }
        return array_key_exists($name, self::registered());
    }

    public static function canResizeMime(string $mime): bool
    {
        $mime = strtolower($mime);
        return in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    /** Generate (or regenerate) all intermediate sizes for a media row. */
    public static function generateForMedia(int $mediaId): int
    {
        $media = Media::find($mediaId);
        if (!$media || !Media::isImageMime((string) ($media->mime_type ?? ''))) {
            return 0;
        }
        if (!self::canResizeMime((string) $media->mime_type)) {
            return 0;
        }
        $srcPath = Media::absolutePath($media);
        if (!is_file($srcPath)) {
            return 0;
        }

        self::deleteFilesForMedia($mediaId);
        self::deleteRowsForMedia($mediaId);

        $info = @getimagesize($srcPath);
        if (!is_array($info) || empty($info[0]) || empty($info[1])) {
            return 0;
        }
        $srcW = (int) $info[0];
        $srcH = (int) $info[1];
        $src = self::createImageResource($srcPath, (string) $media->mime_type);
        if ($src === null) {
            return 0;
        }

        $created = 0;
        $baseName = pathinfo((string) $media->filename, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo((string) $media->filename, PATHINFO_EXTENSION)) ?: 'jpg';
        $dir = dirname($srcPath);

        foreach (self::registered() as $name => $spec) {
            $dims = self::computeDimensions($srcW, $srcH, $spec['width'], $spec['height'], $spec['crop']);
            if ($dims === null) {
                continue;
            }
            [$dstW, $dstH, $srcX, $srcY, $cropW, $cropH] = $dims;
            if ($dstW >= $srcW && $dstH >= $srcH && !$spec['crop']) {
                continue; // never upscale
            }

            $dst = imagecreatetruecolor($dstW, $dstH);
            if ($dst === false) {
                continue;
            }
            self::preserveTransparency($dst, (string) $media->mime_type);
            if (!imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $cropW, $cropH)) {
                imagedestroy($dst);
                continue;
            }

            $filename = $baseName . '-' . $name . '.' . $ext;
            $destPath = $dir . '/' . $filename;
            if (!self::saveImageResource($dst, $destPath, (string) $media->mime_type)) {
                imagedestroy($dst);
                continue;
            }
            imagedestroy($dst);

            $relative = 'media/' . $filename;
            $fileSize = (int) (@filesize($destPath) ?: 0);
            self::upsertRow($mediaId, $name, $relative, (string) $media->mime_type, $dstW, $dstH, $fileSize);
            $created++;
        }

        imagedestroy($src);
        return $created;
    }

    /** @return array<int, object> */
    public static function listForMedia(int $mediaId): array
    {
        $stmt = Database::getInstance()->prepare('
            SELECT * FROM cms_media_sizes WHERE media_id = ? ORDER BY width ASC
        ');
        $stmt->execute([$mediaId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
    }

    public static function findSize(int $mediaId, string $sizeName): ?object
    {
        $sizeName = strtolower(trim($sizeName));
        if ($sizeName === '' || $sizeName === self::SIZE_FULL) {
            return null;
        }
        $stmt = Database::getInstance()->prepare('
            SELECT * FROM cms_media_sizes WHERE media_id = ? AND size_name = ?
        ');
        $stmt->execute([$mediaId, $sizeName]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function absolutePathForSize(object $sizeRow): string
    {
        return dirname(__DIR__) . '/public/uploads/' . ltrim((string) $sizeRow->file_path, '/');
    }

    public static function deleteForMedia(int $mediaId): void
    {
        self::deleteFilesForMedia($mediaId);
        self::deleteRowsForMedia($mediaId);
    }

    public static function publicUrl(int $mediaId, string $sizeName = self::SIZE_FULL): string
    {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $sizeName = strtolower(trim($sizeName));
        if ($sizeName === '' || $sizeName === self::SIZE_FULL) {
            return $base . '/share/media/' . max(0, $mediaId);
        }
        return $base . '/share/media/' . max(0, $mediaId) . '/' . rawurlencode($sizeName);
    }

    public static function adminUrl(int $mediaId, string $sizeName = self::SIZE_FULL): string
    {
        $sizeName = strtolower(trim($sizeName));
        if ($sizeName === '' || $sizeName === self::SIZE_FULL) {
            return '/serve/media/' . max(0, $mediaId);
        }
        return '/serve/media/' . max(0, $mediaId) . '/' . rawurlencode($sizeName);
    }

    /**
     * Build srcset candidates: width => url (public share).
     *
     * @return array<int, string>
     */
    public static function srcsetMap(object $media, bool $public = true): array
    {
        $id = (int) ($media->id ?? 0);
        if ($id < 1) {
            return [];
        }
        $map = [];
        $fullW = (int) ($media->width ?? 0);
        if ($fullW > 0) {
            $map[$fullW] = $public ? self::publicUrl($id) : self::adminUrl($id);
        }
        foreach (self::listForMedia($id) as $row) {
            $w = (int) ($row->width ?? 0);
            if ($w < 1) {
                continue;
            }
            $map[$w] = $public
                ? self::publicUrl($id, (string) $row->size_name)
                : self::adminUrl($id, (string) $row->size_name);
        }
        ksort($map, SORT_NUMERIC);
        return $map;
    }

    public static function srcsetAttribute(object $media, bool $public = true): string
    {
        $parts = [];
        foreach (self::srcsetMap($media, $public) as $w => $url) {
            $parts[] = $url . ' ' . $w . 'w';
        }
        return implode(', ', $parts);
    }

    /**
     * Pick a default src URL near $preferredWidth (or largest <= preferred, else full).
     */
    public static function pickSrc(object $media, int $preferredWidth = 1024, bool $public = true): string
    {
        $map = self::srcsetMap($media, $public);
        if ($map === []) {
            $id = (int) ($media->id ?? 0);
            return $public ? self::publicUrl($id) : self::adminUrl($id);
        }
        $bestUrl = null;
        $bestW = 0;
        foreach ($map as $w => $url) {
            if ($w <= $preferredWidth && $w >= $bestW) {
                $bestW = $w;
                $bestUrl = $url;
            }
        }
        if ($bestUrl !== null) {
            return $bestUrl;
        }
        // Prefer smallest available if all are larger than preferred
        foreach ($map as $url) {
            return $url;
        }
        $id = (int) ($media->id ?? 0);
        return $public ? self::publicUrl($id) : self::adminUrl($id);
    }

    /**
     * Responsive <img> HTML.
     *
     * @param array{class?: string, alt?: string, sizes?: string, preferred_width?: int, loading?: string, public?: bool, decoding?: string} $opts
     */
    public static function imgTag(object $media, array $opts = []): string
    {
        $public = ($opts['public'] ?? true) !== false;
        $preferred = max(1, (int) ($opts['preferred_width'] ?? 1024));
        $src = self::pickSrc($media, $preferred, $public);
        $srcset = self::srcsetAttribute($media, $public);
        $alt = htmlspecialchars((string) ($opts['alt'] ?? $media->alt_text ?? ''), ENT_QUOTES, 'UTF-8');
        $class = htmlspecialchars((string) ($opts['class'] ?? 'img-fluid'), ENT_QUOTES, 'UTF-8');
        $sizes = htmlspecialchars((string) ($opts['sizes'] ?? '(max-width: 768px) 100vw, min(1024px, 100vw)'), ENT_QUOTES, 'UTF-8');
        $loading = htmlspecialchars((string) ($opts['loading'] ?? 'lazy'), ENT_QUOTES, 'UTF-8');
        $decoding = htmlspecialchars((string) ($opts['decoding'] ?? 'async'), ENT_QUOTES, 'UTF-8');
        $w = (int) ($media->width ?? 0);
        $h = (int) ($media->height ?? 0);

        $html = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"';
        if ($srcset !== '') {
            $html .= ' srcset="' . htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') . '"';
            $html .= ' sizes="' . $sizes . '"';
        }
        $html .= ' alt="' . $alt . '"';
        if ($class !== '') {
            $html .= ' class="' . $class . '"';
        }
        if ($loading !== '') {
            $html .= ' loading="' . $loading . '"';
        }
        if ($decoding !== '') {
            $html .= ' decoding="' . $decoding . '"';
        }
        if ($w > 0 && $h > 0) {
            $html .= ' width="' . $w . '" height="' . $h . '"';
        }
        $html .= '>';
        return $html;
    }

    /**
     * @param array{width: int, height: int, crop: bool} $spec unused — dims passed separately
     * @return array{0:int,1:int,2:int,3:int,4:int,5:int}|null dstW,dstH,srcX,srcY,cropW,cropH
     */
    private static function computeDimensions(int $srcW, int $srcH, int $maxW, int $maxH, bool $crop): ?array
    {
        if ($srcW < 1 || $srcH < 1) {
            return null;
        }
        if ($crop) {
            $targetW = $maxW > 0 ? $maxW : $srcW;
            $targetH = $maxH > 0 ? $maxH : $srcH;
            $srcRatio = $srcW / $srcH;
            $dstRatio = $targetW / $targetH;
            if ($srcRatio > $dstRatio) {
                $cropH = $srcH;
                $cropW = (int) round($srcH * $dstRatio);
                $srcX = (int) max(0, ($srcW - $cropW) / 2);
                $srcY = 0;
            } else {
                $cropW = $srcW;
                $cropH = (int) round($srcW / $dstRatio);
                $srcX = 0;
                $srcY = (int) max(0, ($srcH - $cropH) / 2);
            }
            return [$targetW, $targetH, $srcX, $srcY, $cropW, $cropH];
        }

        $maxW = $maxW > 0 ? $maxW : $srcW;
        $maxH = $maxH > 0 ? $maxH : $srcH;
        if ($srcW <= $maxW && $srcH <= $maxH) {
            return null; // no resize needed
        }
        $ratio = min($maxW / $srcW, $maxH / $srcH);
        $dstW = max(1, (int) round($srcW * $ratio));
        $dstH = max(1, (int) round($srcH * $ratio));
        return [$dstW, $dstH, 0, 0, $srcW, $srcH];
    }

    /** @return \GdImage|resource|null */
    private static function createImageResource(string $path, string $mime)
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return null;
        }
        $mime = strtolower($mime);
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        return $img ?: null;
    }

    /** @param \GdImage|resource $img */
    private static function preserveTransparency($img, string $mime): void
    {
        $mime = strtolower($mime);
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $transparent);
            }
        }
    }

    /** @param \GdImage|resource $img */
    private static function saveImageResource($img, string $path, string $mime): bool
    {
        $mime = strtolower($mime);
        return match ($mime) {
            'image/jpeg' => imagejpeg($img, $path, 82),
            'image/png' => imagepng($img, $path, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($img, $path, 82) : false,
            default => false,
        };
    }

    private static function upsertRow(
        int $mediaId,
        string $sizeName,
        string $filePath,
        string $mime,
        int $width,
        int $height,
        int $fileSize
    ): void {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            INSERT INTO cms_media_sizes (media_id, size_name, file_path, mime_type, width, height, file_size)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                mime_type = VALUES(mime_type),
                width = VALUES(width),
                height = VALUES(height),
                file_size = VALUES(file_size)
        ');
        $stmt->execute([$mediaId, $sizeName, $filePath, $mime, $width, $height, $fileSize]);
    }

    private static function deleteRowsForMedia(int $mediaId): void
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM cms_media_sizes WHERE media_id = ?');
        $stmt->execute([$mediaId]);
    }

    private static function deleteFilesForMedia(int $mediaId): void
    {
        foreach (self::listForMedia($mediaId) as $row) {
            $path = self::absolutePathForSize($row);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
