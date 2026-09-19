<?php
/**
 * SQL dump helpers: generated-column aware backup/restore.
 */

declare(strict_types=1);

function paper_column_extra_is_generated(?string $extra): bool
{
    return stripos((string) $extra, 'GENERATED') !== false;
}

/**
 * Quote a value for a MySQL/MariaDB option file ([client] section).
 * Always double-quotes so #, ;, spaces, and quotes in passwords are not truncated
 * or misparsed (PDO can succeed while an unquoted .cnf password fails with 1045).
 */
function paper_mysql_option_file_quote(string $value): string
{
    $escaped = str_replace(
        ["\\", '"', "\n", "\r", "\t"],
        ['\\\\', '\\"', '\\n', '\\r', '\\t'],
        $value
    );

    return '"' . $escaped . '"';
}

/**
 * Write a temporary mysql/mysqldump defaults-extra-file with quoted credentials.
 */
function paper_write_mysql_client_cnf(
    string $path,
    string $host,
    string $user,
    string $pass,
    ?string $charset = null
): void {
    $content = "[client]\n"
        . 'host=' . paper_mysql_option_file_quote($host) . "\n"
        . 'user=' . paper_mysql_option_file_quote($user) . "\n"
        . 'password=' . paper_mysql_option_file_quote($pass) . "\n";
    if ($charset !== null && $charset !== '') {
        $content .= 'default-character-set=' . paper_mysql_option_file_quote($charset) . "\n";
    }
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Cannot write MySQL client option file: ' . $path);
    }
    if (function_exists('chmod')) {
        @chmod($path, 0600);
    }
}

/**
 * @return list<string>
 */
function paper_mysqldump_extra_args(string $mysqldump): array
{
    static $cache = [];
    if (!array_key_exists($mysqldump, $cache)) {
        $cache[$mysqldump] = paper_mysqldump_supports_skip_generated($mysqldump);
    }

    return $cache[$mysqldump] ? ['--skip-generated-columns'] : [];
}

function paper_mysqldump_supports_skip_generated(string $mysqldump): bool
{
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open([$mysqldump, '--help'], $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        return false;
    }
    fclose($pipes[0]);
    $help = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    return stripos($help, 'skip-generated-columns') !== false;
}

/**
 * @return array<string, list<string>> table => generated column names
 */
function paper_parse_generated_columns_from_sql_dump(string $sql): array
{
    $result = [];
    if (!preg_match_all('/CREATE TABLE `([^`]+)`\s*\((.*?)\)\s*ENGINE/si', $sql, $matches, PREG_SET_ORDER)) {
        return $result;
    }

    foreach ($matches as $match) {
        $table = (string) $match[1];
        $body = (string) $match[2];
        foreach (preg_split('/\r\n|\n|\r/', $body) as $line) {
            $line = trim($line);
            if ($line === '' || stripos($line, 'PRIMARY KEY') === 0 || stripos($line, 'UNIQUE KEY') === 0
                || stripos($line, 'KEY ') === 0 || stripos($line, 'CONSTRAINT ') === 0) {
                continue;
            }
            if (!preg_match('/^`([^`]+)`/s', $line, $colMatch)) {
                continue;
            }
            if (stripos($line, 'GENERATED') !== false) {
                $result[$table][] = $colMatch[1];
            }
        }
    }

    return $result;
}

/**
 * @return list<string>
 */
function paper_parse_table_column_order_from_sql_dump(string $sql, string $table): array
{
    if (!preg_match('/CREATE TABLE `' . preg_quote($table, '/') . '`\s*\((.*?)\)\s*ENGINE/si', $sql, $match)) {
        return [];
    }

    $cols = [];
    foreach (preg_split('/\r\n|\n|\r/', (string) $match[1]) as $line) {
        $line = trim($line);
        if ($line === '' || stripos($line, 'PRIMARY KEY') === 0 || stripos($line, 'UNIQUE KEY') === 0
            || stripos($line, 'KEY ') === 0 || stripos($line, 'CONSTRAINT ') === 0) {
            continue;
        }
        if (preg_match('/^`([^`]+)`/s', $line, $colMatch)) {
            $cols[] = $colMatch[1];
        }
    }

    return $cols;
}

/**
 * Remove MariaDB mysqldump sandbox banners that older mysql clients misread as
 * client meta-commands (Unknown command backslash-dash).
 * Targets the enable-the-sandbox-mode versioned block comment from MariaDB 10.11+ dumps.
 */
function paper_strip_mariadb_sandbox_banner(string $sql): string
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;

    // Build pattern without a literal star-slash sequence in source (breaks some IDE parsers).
    $blockEnd = '*' . '/';
    $pattern = '#/\*M?!999999\\\\- enable the sandbox mode ' . preg_quote($blockEnd, '#') . '\s*#i';
    $stripped = preg_replace($pattern, '', $sql, 1);

    return is_string($stripped) ? $stripped : $sql;
}

/**
 * @return array{path: string, changed: bool, stripped_sandbox: bool, stripped_generated: bool}
 */
function paper_sanitize_sql_dump_file(string $sqlPath): array
{
    $sql = file_get_contents($sqlPath);
    if ($sql === false) {
        throw new RuntimeException('Cannot read SQL dump: ' . $sqlPath);
    }

    $withoutBanner = paper_strip_mariadb_sandbox_banner($sql);
    $strippedSandbox = ($withoutBanner !== $sql);
    $sanitized = paper_sanitize_generated_column_inserts($withoutBanner);
    $strippedGenerated = ($sanitized !== $withoutBanner);

    if (!$strippedSandbox && !$strippedGenerated) {
        return [
            'path' => $sqlPath,
            'changed' => false,
            'stripped_sandbox' => false,
            'stripped_generated' => false,
        ];
    }

    $tmp = $sqlPath . '.sanitized.tmp';
    if (file_put_contents($tmp, $sanitized) === false) {
        throw new RuntimeException('Cannot write sanitized SQL dump.');
    }
    if (!@rename($tmp, $sqlPath)) {
        @unlink($tmp);
        throw new RuntimeException('Cannot replace SQL dump with sanitized version.');
    }

    return [
        'path' => $sqlPath,
        'changed' => true,
        'stripped_sandbox' => $strippedSandbox,
        'stripped_generated' => $strippedGenerated,
    ];
}

/**
 * Shared pre-import normalize: strip MariaDB sandbox banner, then generated-column INSERTs.
 * Used by both mysql-client and PDO restore paths.
 */
function paper_sanitize_sql_dump_contents(string $sql): string
{
    return paper_sanitize_generated_column_inserts(paper_strip_mariadb_sandbox_banner($sql));
}

function paper_sanitize_generated_column_inserts(string $sql): string
{
    $generatedByTable = paper_parse_generated_columns_from_sql_dump($sql);
    if ($generatedByTable === []) {
        return $sql;
    }

    $out = '';
    $offset = 0;
    $len = strlen($sql);
    while ($offset < $len) {
        $pos = stripos($sql, 'INSERT INTO `', $offset);
        if ($pos === false) {
            $out .= substr($sql, $offset);
            break;
        }
        $out .= substr($sql, $offset, $pos - $offset);
        $semi = paper_find_sql_statement_end($sql, $pos);
        if ($semi === false) {
            $out .= substr($sql, $pos);
            break;
        }
        $stmt = substr($sql, $pos, $semi - $pos + 1);
        $out .= paper_sanitize_insert_statement($stmt, $sql, $generatedByTable);
        $offset = $semi + 1;
    }

    return $out;
}

/**
 * @return int|false position of terminating semicolon
 */
function paper_find_sql_statement_end(string $sql, int $start)
{
    $inString = false;
    $stringChar = '';
    $escaped = false;
    $len = strlen($sql);
    for ($i = $start; $i < $len; $i++) {
        $ch = $sql[$i];
        if ($inString) {
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($ch === '\\') {
                $escaped = true;
                continue;
            }
            if ($ch === $stringChar) {
                $inString = false;
                $stringChar = '';
            }
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $inString = true;
            $stringChar = $ch;
            continue;
        }
        if ($ch === ';') {
            return $i;
        }
    }

    return false;
}

/**
 * @param array<string, list<string>> $generatedByTable
 */
function paper_sanitize_insert_statement(string $stmt, string $fullSql, array $generatedByTable): string
{
    $trimmed = rtrim($stmt);
    if (!preg_match('/^INSERT INTO `([^`]+)`/i', $trimmed, $tableMatch)) {
        return $stmt;
    }

    $table = $tableMatch[1];
    $generated = $generatedByTable[$table] ?? [];
    if ($generated === []) {
        return $stmt;
    }

    $body = rtrim($trimmed, " \t\n\r;");
    $hadSemicolon = str_ends_with($trimmed, ';');

    if (preg_match('/^INSERT INTO `[^`]+`\s*\(([^)]+)\)\s*VALUES\s*(.+)$/is', $body, $parts)) {
        $cols = paper_sql_parse_backtick_column_list($parts[1]);
        $removeIndices = paper_indices_to_remove($cols, $generated);
        if ($removeIndices === []) {
            return $stmt;
        }

        $tuples = paper_sql_parse_value_tuples(trim($parts[2]));
        $newCols = paper_remove_indices($cols, $removeIndices);
        $newTuples = [];
        foreach ($tuples as $tupleInner) {
            $vals = paper_sql_split_top_level_commas($tupleInner);
            $vals = paper_remove_indices($vals, $removeIndices);
            $newTuples[] = '(' . implode(',', $vals) . ')';
        }

        $suffix = $hadSemicolon ? ';' : '';
        $colSql = implode(',', array_map(static fn (string $c) => '`' . str_replace('`', '``', $c) . '`', $newCols));

        return 'INSERT INTO `' . str_replace('`', '``', $table) . '` (' . $colSql . ') VALUES ' . implode(',', $newTuples) . $suffix;
    }

    if (preg_match('/^INSERT INTO `[^`]+`\s*VALUES\s*(.+)$/is', $body, $parts)) {
        $allCols = paper_parse_table_column_order_from_sql_dump($fullSql, $table);
        $removeIndices = paper_indices_to_remove($allCols, $generated);
        if ($removeIndices === [] || $allCols === []) {
            return $stmt;
        }

        $insertCols = paper_remove_indices($allCols, $removeIndices);
        $tuples = paper_sql_parse_value_tuples(trim($parts[1]));
        $newTuples = [];
        foreach ($tuples as $tupleInner) {
            $vals = paper_sql_split_top_level_commas($tupleInner);
            $vals = paper_remove_indices($vals, $removeIndices);
            $newTuples[] = '(' . implode(',', $vals) . ')';
        }

        $suffix = $hadSemicolon ? ';' : '';
        $colSql = implode(',', array_map(static fn (string $c) => '`' . str_replace('`', '``', $c) . '`', $insertCols));

        return 'INSERT INTO `' . str_replace('`', '``', $table) . '` (' . $colSql . ') VALUES ' . implode(',', $newTuples) . $suffix;
    }

    return $stmt;
}

/**
 * @return list<string>
 */
function paper_sql_parse_backtick_column_list(string $list): array
{
    $cols = [];
    if (preg_match_all('/`([^`]+)`/', $list, $matches)) {
        $cols = $matches[1];
    }

    return $cols;
}

/**
 * @param list<string> $cols
 * @param list<string> $generated
 * @return list<int>
 */
function paper_indices_to_remove(array $cols, array $generated): array
{
    $indices = [];
    foreach ($generated as $name) {
        $idx = array_search($name, $cols, true);
        if ($idx !== false) {
            $indices[] = (int) $idx;
        }
    }
    rsort($indices);

    return $indices;
}

/**
 * @template T
 * @param list<T> $items
 * @param list<int> $indicesDesc
 * @return list<T>
 */
function paper_remove_indices(array $items, array $indicesDesc): array
{
    foreach ($indicesDesc as $idx) {
        if (array_key_exists($idx, $items)) {
            unset($items[$idx]);
        }
    }

    return array_values($items);
}

/**
 * @return list<string> inner tuple contents without surrounding parens
 *
 * Parentheses inside quoted string values must not change tuple depth
 * (grievance/profile text fields often contain "(...)" and would otherwise
 * truncate multi-row INSERT sanitization).
 */
function paper_sql_parse_value_tuples(string $valuesSql): array
{
    $valuesSql = trim($valuesSql);
    $tuples = [];
    $buf = '';
    $depth = 0;
    $inString = false;
    $stringChar = '';
    $escaped = false;
    $len = strlen($valuesSql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $valuesSql[$i];
        if ($inString) {
            if ($depth > 0) {
                $buf .= $ch;
            }
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($ch === '\\') {
                $escaped = true;
                continue;
            }
            if ($ch === $stringChar) {
                // SQL standard doubled-quote escape: 'it''s'
                if ($i + 1 < $len && $valuesSql[$i + 1] === $stringChar) {
                    $i++;
                    if ($depth > 0) {
                        $buf .= $stringChar;
                    }
                    continue;
                }
                $inString = false;
                $stringChar = '';
            }
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $inString = true;
            $stringChar = $ch;
            if ($depth > 0) {
                $buf .= $ch;
            }
            continue;
        }
        if ($ch === '(') {
            if ($depth === 0) {
                $buf = '';
            } else {
                $buf .= $ch;
            }
            $depth++;
            continue;
        }
        if ($ch === ')') {
            $depth--;
            if ($depth === 0) {
                $tuples[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $ch;
            continue;
        }
        if ($depth > 0) {
            $buf .= $ch;
        }
    }

    return $tuples;
}

/**
 * @return list<string>
 */
function paper_sql_split_top_level_commas(string $inner): array
{
    $parts = [];
    $buf = '';
    $depth = 0;
    $inString = false;
    $stringChar = '';
    $escaped = false;
    $len = strlen($inner);

    for ($i = 0; $i < $len; $i++) {
        $ch = $inner[$i];
        if ($inString) {
            $buf .= $ch;
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($ch === '\\') {
                $escaped = true;
                continue;
            }
            if ($ch === $stringChar) {
                $inString = false;
                $stringChar = '';
            }
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $inString = true;
            $stringChar = $ch;
            $buf .= $ch;
            continue;
        }
        if ($ch === '(') {
            $depth++;
            $buf .= $ch;
            continue;
        }
        if ($ch === ')') {
            $depth--;
            $buf .= $ch;
            continue;
        }
        if ($ch === ',' && $depth === 0) {
            $parts[] = trim($buf);
            $buf = '';
            continue;
        }
        $buf .= $ch;
    }
    if (trim($buf) !== '') {
        $parts[] = trim($buf);
    }

    return $parts;
}
