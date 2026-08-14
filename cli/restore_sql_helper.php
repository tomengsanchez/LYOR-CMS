<?php
/**
 * SQL import helpers for cli/restore.php (mysqldump / MariaDB dumps via PDO).
 */

declare(strict_types=1);

/**
 * Split a SQL dump into executable statements.
 * Skips -- / # line comments and non-executable block comments so that
 * mysqldump headers like "-- Table structure..." do not swallow the following DROP TABLE.
 * Keeps MySQL versioned comments (!... form) and MariaDB versioned blocks that contain real SQL.
 * Sandbox banner comments from MariaDB dumps are discarded.
 *
 * @return list<string>
 */
function paper_split_sql_statements(string $sql): array
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';
    $escaped = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        if ($inString) {
            $buffer .= $ch;
            if ($escaped) {
                $escaped = false;
            } elseif ($ch === '\\') {
                $escaped = true;
            } elseif ($ch === $stringChar) {
                $inString = false;
                $stringChar = '';
            }
            continue;
        }

        // Line comments: -- ... and # ...
        if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            $i++;
            while ($i + 1 < $len && $sql[$i + 1] !== "\n" && $sql[$i + 1] !== "\r") {
                $i++;
            }
            continue;
        }
        if ($ch === '#') {
            while ($i + 1 < $len && $sql[$i + 1] !== "\n" && $sql[$i + 1] !== "\r") {
                $i++;
            }
            continue;
        }

        // Block comments
        if ($ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
            $next2 = $i + 2 < $len ? $sql[$i + 2] : '';
            $next3 = $i + 3 < $len ? $sql[$i + 3] : '';
            // MySQL versioned: /*!...*/
            $isMysqlVersioned = ($next2 === '!');
            // MariaDB versioned executable: /*M!100101 ... */ — keep.
            // Sandbox banner /*M!999999\- enable the sandbox mode */ — skip (no SQL).
            $isMariaVersioned = (strtoupper($next2) === 'M' && $next3 === '!');
            $keepBlock = $isMysqlVersioned;
            if ($isMariaVersioned) {
                $endProbe = strpos($sql, '*/', $i + 4);
                $blockInner = $endProbe === false
                    ? substr($sql, $i + 4)
                    : substr($sql, $i + 4, $endProbe - ($i + 4));
                // Keep only if it looks like real SQL (SET/DROP/CREATE/ALTER/INSERT/LOCK/UNLOCK)
                $keepBlock = (bool) preg_match(
                    '/\b(SET|DROP|CREATE|ALTER|INSERT|LOCK|UNLOCK|UPDATE|DELETE|REPLACE)\b/i',
                    $blockInner
                );
            }

            if ($keepBlock) {
                $buffer .= $ch;
                $i++;
                $buffer .= $sql[$i]; // *
                while ($i + 1 < $len) {
                    $i++;
                    $buffer .= $sql[$i];
                    if ($sql[$i] === '*' && $i + 1 < $len && $sql[$i + 1] === '/') {
                        $i++;
                        $buffer .= $sql[$i];
                        break;
                    }
                }
                continue;
            }

            // Skip non-executable / sandbox block comment
            $i++; // *
            while ($i + 1 < $len) {
                $i++;
                if ($sql[$i] === '*' && $i + 1 < $len && $sql[$i + 1] === '/') {
                    $i++; // /
                    break;
                }
            }
            continue;
        }

        if (($ch === '"' || $ch === "'" || $ch === '`') && !$escaped) {
            $inString = true;
            $stringChar = $ch;
            $buffer .= $ch;
            continue;
        }

        if ($ch === ';') {
            $stmt = trim($buffer);
            $buffer = '';
            if ($stmt !== '' && stripos($stmt, 'DELIMITER ') !== 0) {
                $statements[] = $stmt;
            }
            continue;
        }

        $buffer .= $ch;
    }

    $stmt = trim($buffer);
    if ($stmt !== '' && stripos($stmt, 'DELIMITER ') !== 0) {
        $statements[] = $stmt;
    }

    return $statements;
}

function paper_import_sql_file_with_pdo(\PDO $pdo, string $sqlFile, bool $largeMode = false): void
{
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new RuntimeException('Cannot read SQL file');
    }

    $pdo->exec('SET NAMES utf8mb4');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $statements = paper_split_sql_statements($sql);
    $executed = 0;
    foreach ($statements as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (\PDOException $e) {
            $preview = mb_substr(preg_replace('/\s+/', ' ', $stmt) ?? $stmt, 0, 180);
            throw new RuntimeException(
                'SQL import failed: ' . $e->getMessage() . ' | statement: ' . $preview,
                (int) $e->getCode(),
                $e
            );
        }
        $executed++;
        if ($largeMode && ($executed % 250) === 0) {
            fwrite(STDOUT, "[large-mode] executed {$executed} SQL statements via PDO...\n");
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    if ($largeMode) {
        fwrite(STDOUT, "[large-mode] executed total SQL statements via PDO: {$executed}\n");
    }
}
