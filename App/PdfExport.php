<?php
namespace App;

use App\Models\AppSettings;
use Core\Auth;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

/**
 * Staff PDF reports: branded header (logo + app name), footer with page numbers and generator.
 */
class PdfExport
{
    public static function isAvailable(): bool
    {
        return class_exists(Mpdf::class);
    }

    public static function requireLibrary(): void
    {
        if (!self::isAvailable()) {
            http_response_code(503);
            echo 'PDF export is not available. Install dependencies with: composer install';
            exit;
        }
    }

    /**
     * @return array{app_name: string, company_name: string, logo_data_uri: ?string}
     */
    public static function branding(): array
    {
        $b = AppSettings::getBrandingConfig();
        $logoDataUri = null;
        $logoPath = $b->logo_path ?? '';
        if ($logoPath !== '' && isset($logoPath[0]) && $logoPath[0] === '/') {
            $full = ROOT . '/public' . $logoPath;
            if (is_file($full)) {
                $logoDataUri = self::fileToDataUri($full);
            }
        }
        return [
            'app_name' => (string) ($b->app_name ?? 'PAPeR'),
            'company_name' => (string) ($b->company_name ?? ''),
            'logo_data_uri' => $logoDataUri,
        ];
    }

    private static function fileToDataUri(string $path): ?string
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
        if ($mime === 'application/octet-stream') {
            return null;
        }
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    /**
     * Display name for the account that generated the PDF (web session or API bearer).
     */
    public static function generatorLabel(): string
    {
        $u = Auth::user();
        if ($u === null) {
            return 'Unknown user';
        }
        $display = trim((string) ($u->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }
        $username = trim((string) ($u->username ?? ''));

        return $username !== '' ? $username : ('User #' . (int) ($u->id ?? 0));
    }

    public static function buildHeaderHtml(array $br, string $documentTitle): string
    {
        $logoBlock = '';
        if (!empty($br['logo_data_uri'])) {
            // mPDF: explicit dimensions help consistent header layout with embedded logo
            $logoBlock = '<img src="' . htmlspecialchars($br['logo_data_uri'], ENT_QUOTES, 'UTF-8')
                . '" style="max-height:44px;max-width:140px;height:auto;width:auto;" alt="Logo" />';
        } else {
            $logoBlock = '';
        }

        $company = trim($br['company_name'] ?? '');
        $companyLine = $company !== ''
            ? '<div style="font-size:9pt;color:#64748b;margin-top:2px;">' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '</div>'
            : '';
        return '
<table width="100%" style="border-bottom:2px solid #1e293b;padding-bottom:8px;margin-bottom:4px;">
<tr>
<td width="72" valign="middle">' . $logoBlock . '</td>
<td valign="middle">
<div style="font-size:13pt;font-weight:bold;color:#0f172a;letter-spacing:-0.02em;">' . htmlspecialchars($br['app_name'], ENT_QUOTES, 'UTF-8') . '</div>
' . $companyLine . '
<div style="font-size:10.5pt;color:#334155;margin-top:6px;font-weight:600;">' . htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') . '</div>
</td>
</tr>
</table>';
    }

    public static function documentCss(): string
    {
        return '
body { font-family: "DejaVu Sans", sans-serif; font-size:9pt; color:#1e293b; line-height:1.35; }
.pdf-body { margin:0; padding:0; }
.pdf-meta { font-size:8.5pt; color:#64748b; margin:0 0 8px 0; }
.pdf-section { margin-top:14px; page-break-inside: avoid; }
.pdf-section h3 { font-size:10pt; color:#0f172a; border-bottom:1px solid #e2e8f0; padding-bottom:4px; margin:0 0 8px 0; }
.pdf-kv { width:100%; border-collapse:collapse; margin:0 0 8px 0; font-size:9pt; }
.pdf-kv td { padding:6px 10px; border:1px solid #e2e8f0; vertical-align:top; }
.pdf-kv td:first-child { background:#f1f5f9; font-weight:700; color:#475569; width:34%; }
.pdf-table { width:100%; border-collapse:collapse; font-size:8pt; margin-top:6px; }
.pdf-table th { background:#f1f5f9; color:#0f172a; font-weight:700; text-align:left; padding:6px 8px; border:1px solid #cbd5e1; }
.pdf-table td { padding:5px 8px; border:1px solid #e2e8f0; vertical-align:top; }
.pdf-table tr:nth-child(even) td { background:#f8fafc; }
';
    }

    /**
     * Stream PDF download and exit.
     */
    public static function stream(string $documentTitle, string $filenameBase, string $bodyHtml): void
    {
        self::requireLibrary();
        $br = self::branding();
        $tempDir = ROOT . '/storage/tmp/mpdf';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 36,
                'margin_bottom' => 28,
                'margin_left' => 14,
                'margin_right' => 14,
                'tempDir' => $tempDir,
            ]);
        } catch (MpdfException $e) {
            http_response_code(500);
            echo 'Could not create PDF document.';
            exit;
        }

        $generator = self::generatorLabel();
        $generated = date('Y-m-d H:i');

        $mpdf->SetTitle($documentTitle);
        $mpdf->SetAuthor($br['app_name']);
        $mpdf->SetCreator($generator . ' · ' . $generated);
        $mpdf->SetHTMLHeader(self::buildHeaderHtml($br, $documentTitle));
        $mpdf->SetHTMLFooter(
            '<table width="100%" style="font-size:8pt;color:#64748b;border-top:1px solid #e2e8f0;padding-top:5px;">'
            . '<tr><td style="text-align:left;width:38%;vertical-align:top;">'
            . 'Generated by <strong>' . htmlspecialchars($generator, ENT_QUOTES, 'UTF-8') . '</strong><br />'
            . '<span style="color:#94a3b8;">' . htmlspecialchars($generated, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</td><td style="text-align:center;vertical-align:middle;">'
            . 'Confidential — staff use only'
            . '</td><td style="text-align:right;width:38%;vertical-align:top;">'
            . 'Page {PAGENO} of {nbpg}'
            . '</td></tr></table>'
        );

        $css = '<style>' . self::documentCss() . '</style>';
        $html = $css . '<div class="pdf-body">' . $bodyHtml . '</div>';

        try {
            $mpdf->WriteHTML($html);
        } catch (MpdfException $e) {
            http_response_code(500);
            echo 'Could not render PDF content.';
            exit;
        }

        $safe = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $filenameBase);
        if ($safe === '' || $safe === null) {
            $safe = 'export';
        }
        $mpdf->Output($safe . '.pdf', 'D');
        exit;
    }
}
