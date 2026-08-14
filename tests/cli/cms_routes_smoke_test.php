<?php
/**
 * Smoke test: public/admin route registration and URL helpers.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\AdminPath;

assert(AdminPath::PREFIX === '/admin', 'admin prefix');
assert(AdminPath::url() === '/admin', 'admin home');
assert(AdminPath::url('pages') === '/admin/pages', 'admin pages');
assert(AdminPath::url('login') === '/admin/login', 'admin login');
assert(function_exists('admin_url'), 'admin_url helper');
assert(admin_url('posts') === '/admin/posts', 'admin_url posts');

$index = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
assert(str_contains($index, "PublicController@home"), 'public home route');
assert(str_contains($index, "'/admin/login'"), 'admin login route');
assert(str_contains($index, 'LegacyRedirectController@login'), 'legacy login redirect');

echo "cms_routes_smoke_test: OK\n";
