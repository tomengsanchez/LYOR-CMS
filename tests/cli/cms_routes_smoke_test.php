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

assert(str_contains($index, "'/admin/posts/bulk'"), 'post bulk route');
assert(str_contains($index, "'/admin/posts/import'"), 'post import route');
assert(str_contains($index, "'/admin/pages/bulk'"), 'page bulk route');
assert(str_contains($index, "'/admin/pages/duplicate/{id}'"), 'page duplicate route');
assert(str_contains($index, "'/search'"), 'public site search route');
assert(str_contains($index, 'PublicController@archiveMonth'), 'month archive route');
assert(str_contains($index, 'PublicController@author'), 'author archive route');
assert(str_contains($index, "'/unlock/page/{id}'"), 'unlock page route');
assert(str_contains($index, "'/unlock/post/{id}'"), 'unlock post route');
assert(str_contains($index, "'/subscribe'"), 'newsletter subscribe route');
assert(str_contains($index, "'/admin/subscribers'"), 'admin subscribers route');

echo "cms_routes_smoke_test: OK\n";
