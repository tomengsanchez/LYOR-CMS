<?php
namespace App;

/**
 * Reusable list operations: search, sort, paginate.
 * Search applies across all configured (selected) columns.
 */
class ListHelper
{
    public static function getValue(object $row, string $key)
    {
        if ($key === 'capabilities' && isset($row->capabilities)) {
            return is_array($row->capabilities) ? implode(' ', $row->capabilities) : (string) $row->capabilities;
        }
        return $row->{$key} ?? '';
    }

    public static function search(array $rows, string $term, array $columnKeys, string $module): array
    {
        $term = trim(mb_strtolower($term));
        if ($term === '') return $rows;

        return array_filter($rows, function ($row) use ($term, $columnKeys, $module) {
            foreach ($columnKeys as $key) {
                $val = self::getValue($row, $key);
                if (is_scalar($val) && mb_stripos((string) $val, $term) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    public static function sort(array $rows, string $sortBy, string $order, array $columnKeys, string $module): array
    {
        if (!in_array($sortBy, $columnKeys, true)) return $rows;
        $col = ListConfig::getColumnByKey($module, $sortBy);
        if (!$col || empty($col['sortable'])) return $rows;

        $desc = strtolower($order) === 'desc';
        usort($rows, function ($a, $b) use ($sortBy, $desc, $module) {
            $va = ListHelper::getValue($a, $sortBy);
            $vb = ListHelper::getValue($b, $sortBy);
            if (is_numeric($va) && is_numeric($vb)) {
                $cmp = (float) $va <=> (float) $vb;
            } else {
                $cmp = strcasecmp((string) $va, (string) $vb);
            }
            return $desc ? -$cmp : $cmp;
        });
        return array_values($rows);
    }

    public static function paginate(array $rows, int $page, int $perPage): array
    {
        $perPage = max(1, min(100, $perPage));
        $total = count($rows);
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($totalPages ?: 1, $page));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($rows, $offset, $perPage);
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public static function buildQuery(array $params, string $baseUrl): string
    {
        $parts = [];
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null) {
                $parts[] = urlencode($k) . '=' . urlencode((string) $v);
            }
        }
        return $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . implode('&', $parts);
    }
}
