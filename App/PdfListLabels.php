<?php
namespace App;

class PdfListLabels
{
    /**
     * @param string[] $keys
     * @return string[]
     */
    public static function headersForKeys(string $module, array $keys): array
    {
        $meta = ListConfig::getColumns($module);
        $map = array_column($meta, 'label', 'key');
        $out = [];
        foreach ($keys as $k) {
            $out[] = $map[$k] ?? $k;
        }
        return $out;
    }
}
