<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\AppSettings;

class AssetController extends Controller
{
    /**
     * Serve the configured app logo from public/uploads/app, even when
     * the document root is not the public/ directory.
     */
    public function logo(): void
    {
        $branding = AppSettings::getBrandingConfig();
        $logoPath = $branding->logo_path ?? '';
        if ($logoPath === '' || $logoPath[0] !== '/') {
            http_response_code(404);
            exit;
        }

        $root = dirname(__DIR__, 2);
        $fullPath = $root . '/public' . $logoPath;
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'ico' => 'image/x-icon',
            default => 'application/octet-stream',
        };

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}

