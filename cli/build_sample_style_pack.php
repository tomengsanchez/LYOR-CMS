<?php
/**
 * Build default + themed CMS style-pack zips.
 * Prefer: php cli/build_style_pack.php
 */
passthru('php ' . escapeshellarg(__DIR__ . '/build_style_pack.php'), $code);
exit($code);
