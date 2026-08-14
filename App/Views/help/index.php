<?php
require_once __DIR__ . '/helpers.php';
$module = $module ?? 'general';
$from = $from ?? '';
$pageTitle = 'Help - ' . help_module_title($module);
ob_start();
require __DIR__ . '/content.php';
$content = ob_get_clean();
$currentPage = 'help';
require __DIR__ . '/../layout/main.php';
