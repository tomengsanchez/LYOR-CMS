<?php
namespace App;

class CsvExporter
{
    /**
     * @param string[] $headers
     * @param iterable $rows
     * @param string[] $keys
     */
    public static function stream(string $filename, array $headers, iterable $rows, array $keys): void
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $filename);
        if ($safeName === '') {
            $safeName = 'export';
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $safeName . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($keys as $key) {
                $val = ListHelper::getValue($row, $key);
                if (is_bool($val)) {
                    $val = $val ? 'Yes' : 'No';
                } elseif (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $line[] = (string) ($val ?? '');
            }
            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }
}
