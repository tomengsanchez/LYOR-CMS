<?php
namespace App\Controllers;

use App\Models\Media;
use Core\Controller;

/** Public image URLs for social crawlers and responsive srcset. */
class PublicMediaController extends Controller
{
    public function serve(int $id, string $size = 'full'): void
    {
        $media = Media::find($id);
        if (!$media || !Media::isImageMime((string) ($media->mime_type ?? ''))) {
            http_response_code(404);
            exit;
        }
        $resolved = Media::resolveServePath($media, $size);
        if (!$resolved) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $resolved['mime']);
        header('Content-Length: ' . filesize($resolved['path']));
        header('Cache-Control: public, max-age=86400, immutable');
        header('X-Content-Type-Options: nosniff');
        readfile($resolved['path']);
        exit;
    }
}
