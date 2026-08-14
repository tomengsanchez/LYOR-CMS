<?php

/**
 * IMPORTANT:
 * Production database runs on MariaDB on a Linux server.
 * When writing SQL (queries, migrations, JSON functions, etc.),
 * ensure it is compatible with MariaDB (avoid MySQL-only features
 * such as the native JSON column type or syntax that MariaDB
 * does not support).
 */

return [
    'host'     => 'localhost',
    'dbname'   => 'paper_db2',
    'username' => 'root',
    'password' => 'root',
    'charset'  => 'utf8mb4',
];

