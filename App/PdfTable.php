<?php
namespace App;

/**
 * Build HTML tables for PDF exports.
 */
class PdfTable
{
    public static function cellValue($row, string $key): string
    {
        if ($key === 'capabilities' && is_object($row) && is_array($row->capabilities ?? null)) {
            return implode(', ', $row->capabilities);
        }
        $val = ListHelper::getValue($row, $key);
        return $val === null || $val === '' ? '—' : (string) $val;
    }

    /** @param list<string> $headers @param list<string> $columnKeys */
    public static function html(string $meta, array $headers, array $columnKeys, array $rows): string
    {
        $html = '<p>' . htmlspecialchars($meta) . '</p><table border="1" cellpadding="6" cellspacing="0"><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columnKeys as $key) {
                $html .= '<td>' . htmlspecialchars(self::cellValue($row, $key)) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }
}
