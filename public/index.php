<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$router = new \Core\Router();

// Legacy redirects (old admin URLs → /admin/*)
$router->get('/login', 'LegacyRedirectController@login');
$router->get('/login/2fa', 'LegacyRedirectController@login2fa');
$router->get('/help', 'LegacyRedirectController@help');
$router->get('/help/fragment', 'LegacyRedirectController@helpFragment');
$router->get('/pages', 'LegacyRedirectController@pages');
$router->get('/posts', 'LegacyRedirectController@posts');
$router->get('/categories', 'LegacyRedirectController@categories');
$router->get('/media', 'LegacyRedirectController@media');
$router->get('/settings', 'LegacyRedirectController@settings');
$router->get('/users', 'LegacyRedirectController@users');
$router->get('/account', 'LegacyRedirectController@account');
$router->get('/notifications', 'LegacyRedirectController@notifications');
$router->get('/admin-guide', 'LegacyRedirectController@adminGuide');
$router->get('/system/general', 'LegacyRedirectController@systemGeneral');
$router->get('/system/backup-restore', 'LegacyRedirectController@systemBackupRestore');
$router->get('/system/audit-trail', 'LegacyRedirectController@systemAuditTrail');

// Public site
$router->get('/', 'PublicController@home');
$router->get('/index.json', 'PublicController@homeJson');
$router->get('/site.json', 'PublicController@siteJson');
$router->get('/p/{slug}.json', 'PublicController@pageJson');
$router->get('/p/{slug}', 'PublicController@page');
$router->get('/search', 'PublicController@search');
$router->get('/blog.json', 'PublicController@blogJson');
$router->get('/blog/category/{slug}', 'PublicController@category');
$router->get('/blog/tag/{slug}', 'PublicController@tag');
$router->get('/blog/archive/{year}/{month}', 'PublicController@archiveMonth');
$router->get('/blog/archive/{year}', 'PublicController@archiveYear');
$router->get('/blog/author/{username}', 'PublicController@author');
$router->get('/blog', 'PublicController@blog');
$router->get('/blog/{slug}.json', 'PublicController@postJson');
$router->get('/blog/{slug}', 'PublicController@post');
$router->get('/share/media/{id}/{size}', 'PublicMediaController@serve');
$router->get('/share/media/{id}', 'PublicMediaController@serve');
$router->get('/sitemap.xml', 'PublicController@sitemap');
$router->get('/feed.xml', 'PublicController@feed');
$router->get('/feed', 'PublicController@feed');
$router->get('/robots.txt', 'PublicController@robots');
$router->get('/llms.txt', 'PublicController@llmsTxt');
$router->get('/llms-full.txt', 'PublicController@llmsFull');
$router->post('/comment/post/{id}', 'PublicController@commentStore');
$router->post('/unlock/page/{id}', 'PublicController@unlockPage');
$router->post('/unlock/post/{id}', 'PublicController@unlockPost');
$router->get('/subscribe', 'NewsletterController@form');
$router->post('/subscribe', 'NewsletterController@store');
$router->get('/subscribe/confirm/{token}', 'NewsletterController@confirm');
$router->get('/unsubscribe/{token}', 'NewsletterController@unsubscribeForm');
$router->post('/unsubscribe/{token}', 'NewsletterController@unsubscribe');

// Admin auth
$router->get('/admin/login', 'AuthController@loginForm');
$router->post('/admin/login', 'AuthController@login');
$router->get('/admin/login/2fa', 'AuthController@twoFactorForm');
$router->post('/admin/login/2fa/verify', 'AuthController@twoFactorVerify');
$router->post('/admin/logout', 'AuthController@logout');

// Admin dashboard
$router->get('/admin', 'DashboardController@index');

// Admin — Pages
$router->get('/admin/pages', 'PageController@index');
$router->get('/admin/pages/view/{id}', 'PageController@show');
$router->get('/admin/pages/create', 'PageController@create');
$router->post('/admin/pages/store', 'PageController@store');
$router->get('/admin/pages/edit/{id}', 'PageController@edit');
$router->post('/admin/pages/update/{id}', 'PageController@update');
$router->post('/admin/pages/delete/{id}', 'PageController@delete');
$router->post('/admin/pages/duplicate/{id}', 'PageController@duplicate');
$router->post('/admin/pages/bulk', 'PageController@bulk');
$router->post('/admin/pages/restore/{id}/{revisionId}', 'RevisionController@restorePage');

// Visual layout builder (Divi-style)
$router->get('/admin/builder/page/{id}', 'BuilderController@editPage');
$router->post('/admin/builder/page/{id}/save', 'BuilderController@savePage');
$router->get('/admin/builder/post/{id}', 'BuilderController@editPost');
$router->post('/admin/builder/post/{id}/save', 'BuilderController@savePost');
$router->get('/admin/builder/templates', 'BuilderController@listTemplates');
$router->get('/admin/builder/templates/{id}', 'BuilderController@getTemplate');
$router->post('/admin/builder/templates', 'BuilderController@saveTemplate');
$router->post('/admin/builder/templates/{id}/delete', 'BuilderController@deleteTemplate');

// Admin — Posts
$router->get('/admin/posts', 'PostController@index');
$router->get('/admin/posts/view/{id}', 'PostController@show');
$router->get('/admin/posts/create', 'PostController@create');
$router->post('/admin/posts/store', 'PostController@store');
$router->get('/admin/posts/edit/{id}', 'PostController@edit');
$router->post('/admin/posts/update/{id}', 'PostController@update');
$router->post('/admin/posts/delete/{id}', 'PostController@delete');
$router->post('/admin/posts/duplicate/{id}', 'PostController@duplicate');
$router->post('/admin/posts/bulk', 'PostController@bulk');
$router->post('/admin/posts/restore/{id}/{revisionId}', 'RevisionController@restorePost');

// Admin — Content library search
$router->get('/admin/search', 'ContentSearchController@index');

// Admin — Redirects (SEO)
$router->get('/admin/redirects', 'RedirectController@index');
$router->post('/admin/redirects/store', 'RedirectController@store');
$router->post('/admin/redirects/update/{id}', 'RedirectController@update');
$router->post('/admin/redirects/delete/{id}', 'RedirectController@delete');

// Admin — Categories
$router->get('/admin/categories', 'CategoryController@index');
$router->get('/admin/categories/create', 'CategoryController@create');
$router->post('/admin/categories/store', 'CategoryController@store');
$router->post('/admin/categories/quick-store', 'CategoryController@quickStore');
$router->get('/admin/categories/edit/{id}', 'CategoryController@edit');
$router->post('/admin/categories/update/{id}', 'CategoryController@update');
$router->post('/admin/categories/delete/{id}', 'CategoryController@delete');

// Admin — Tags
$router->get('/admin/tags', 'TagController@index');
$router->get('/admin/tags/create', 'TagController@create');
$router->post('/admin/tags/store', 'TagController@store');
$router->get('/admin/tags/edit/{id}', 'TagController@edit');
$router->post('/admin/tags/update/{id}', 'TagController@update');
$router->post('/admin/tags/delete/{id}', 'TagController@delete');

// Admin — Menus
$router->get('/admin/menus', 'MenuController@index');
$router->post('/admin/menus/save', 'MenuController@save');

// Admin — Comments
$router->get('/admin/comments', 'CommentController@index');
$router->post('/admin/comments/approve/{id}', 'CommentController@approve');
$router->post('/admin/comments/spam/{id}', 'CommentController@spam');
$router->post('/admin/comments/trash/{id}', 'CommentController@trash');

// Admin — Newsletter subscribers
$router->get('/admin/subscribers', 'SubscriberController@index');
$router->get('/admin/subscribers/export', 'SubscriberController@export');
$router->post('/admin/subscribers/confirm/{id}', 'SubscriberController@confirm');
$router->post('/admin/subscribers/unsubscribe/{id}', 'SubscriberController@unsubscribe');
$router->post('/admin/subscribers/delete/{id}', 'SubscriberController@delete');

// Admin — Widgets
$router->get('/admin/widgets', 'WidgetController@index');
$router->post('/admin/widgets/save', 'WidgetController@save');

// Admin — Media
$router->get('/admin/media', 'MediaController@index');
$router->post('/admin/media/upload', 'MediaController@upload');
$router->post('/admin/media/register-url', 'MediaController@registerUrl');
$router->post('/admin/media/upload-json', 'MediaController@uploadJson');
$router->post('/admin/media/delete/{id}', 'MediaController@delete');

// Admin — Account
$router->get('/admin/account', 'AccountController@index');
$router->get('/admin/account/sessions', 'SessionsController@index');
$router->post('/admin/account/sessions/logout-others', 'SessionsController@logoutOthers');
$router->post('/admin/account/sessions/logout/{id}', 'SessionsController@logoutSession');
$router->post('/admin/account/tokens/logout/{id}', 'SessionsController@logoutToken');

// Admin — Notifications & Help
$router->get('/admin/notifications', 'NotificationController@index');
$router->get('/admin/notifications/click/{id}', 'NotificationController@click');
$router->get('/admin/help/fragment', 'HelpController@fragment');
$router->get('/admin/help', 'HelpController@index');

// Admin — Settings
$router->get('/admin/settings', 'SettingsController@index');
$router->post('/admin/settings/ui', 'SettingsController@updateUi');
$router->post('/admin/settings/notifications', 'SettingsController@updateNotifications');
$router->get('/admin/settings/email', 'EmailSettingsController@index');
$router->post('/admin/settings/email/update', 'EmailSettingsController@update');
$router->post('/admin/settings/email/test', 'EmailSettingsController@testMail');
$router->get('/admin/settings/security', 'SecuritySettingsController@index');
$router->post('/admin/settings/security/update', 'SecuritySettingsController@update');

// Admin — System
$router->get('/admin/system/general', 'GeneralController@index');
$router->post('/admin/system/general/save', 'GeneralController@save');
$router->post('/admin/system/general/theme-preview', 'GeneralController@themePreview');
$router->post('/admin/system/general/theme-preview-clear', 'GeneralController@themePreviewClear');
$router->post('/admin/system/general/help-chat', 'GeneralController@saveHelpChat');
$router->get('/admin/customize', 'CustomizeController@index');
$router->post('/admin/customize/preview', 'CustomizeController@preview');
$router->post('/admin/customize/publish', 'CustomizeController@publish');
$router->post('/admin/customize/close', 'CustomizeController@close');
$router->post('/admin/customize/import-style-pack', 'CustomizeController@importStylePack');
$router->post('/admin/customize/clear-style-pack', 'CustomizeController@clearStylePack');
$router->post('/admin/customize/activate-style-pack', 'CustomizeController@activateStylePack');
$router->post('/admin/customize/delete-style-pack', 'CustomizeController@deleteStylePack');
$router->post('/admin/customize/install-bundled-style-pack', 'CustomizeController@installBundledStylePack');
$router->get('/admin/customize/export-style-pack', 'CustomizeController@exportStylePack');
$router->get('/admin/customize/sample-style-pack', 'CustomizeController@downloadSampleStylePack');
$router->get('/admin/system/backup-restore', 'BackupRestoreController@index');
$router->post('/admin/system/backup-restore/create', 'BackupRestoreController@createBackup');
$router->post('/admin/system/backup-restore/download', 'BackupRestoreController@downloadBackup');
$router->get('/admin/system/audit-trail', 'AuditTrailController@index');
$router->get('/admin/admin-guide', 'AdminGuideController@index');

// Admin — Users & Roles
$router->get('/admin/users', 'UserController@index');
$router->get('/admin/users/export', 'UserController@export');
$router->get('/admin/users/view/{id}', 'UserController@show');
$router->get('/admin/users/create', 'UserController@create');
$router->post('/admin/users/store', 'UserController@store');
$router->get('/admin/users/edit/{id}', 'UserController@edit');
$router->post('/admin/users/update/{id}', 'UserController@update');
$router->post('/admin/users/delete/{id}', 'UserController@delete');
$router->get('/admin/users/roles', 'RoleController@index');
$router->get('/admin/users/roles/view/{id}', 'RoleController@show');
$router->get('/admin/users/roles/create', 'RoleController@create');
$router->post('/admin/users/roles/store', 'RoleController@store');
$router->get('/admin/users/roles/edit/{id}', 'RoleController@edit');
$router->post('/admin/users/roles/update/{id}', 'RoleController@update');

// Assets (admin-authenticated media; logo public)
$router->get('/serve/app-logo', 'AssetController@logo');
$router->get('/serve/media/{id}/{size}', 'MediaController@serve');
$router->get('/serve/media/{id}', 'MediaController@serve');

// REST API (unchanged)
$router->post('/api/auth/login', 'Api\AuthController@login');
$router->post('/api/auth/2fa/verify', 'Api\AuthController@verify2fa');
$router->post('/api/auth/2fa/resend', 'Api\AuthController@resend2fa');
$router->get('/api/auth/me', 'Api\AuthController@me');
$router->post('/api/auth/logout', 'Api\AuthController@logout');
$router->get('/api/meta/error-codes', 'Api\MetaController@errorCodes');
$router->get('/api/pages', 'Api\PageController@listApi');
$router->post('/api/pages', 'Api\PageController@createApi');
$router->get('/api/pages/{id}', 'Api\PageController@getApi');
$router->patch('/api/pages/{id}', 'Api\PageController@updateApi');
$router->delete('/api/pages/{id}', 'Api\PageController@deleteApi');
$router->get('/api/posts', 'Api\PostController@listApi');
$router->post('/api/posts', 'Api\PostController@createApi');
$router->get('/api/posts/{id}', 'Api\PostController@getApi');
$router->patch('/api/posts/{id}', 'Api\PostController@updateApi');
$router->delete('/api/posts/{id}', 'Api\PostController@deleteApi');
$router->get('/api/media', 'Api\MediaController@listApi');
$router->get('/api/notifications', 'Api\NotificationController@listApi');
$router->get('/api/settings/ui', 'Api\SettingsController@ui');
$router->get('/api/system/general', 'Api\SettingsController@general');

// Custom permalink patterns (must be last — catch-all)
$router->get('/{s1}/{s2}/{s3}', 'PublicController@permalinkResolve');
$router->get('/{s1}/{s2}', 'PublicController@permalinkResolve');
$router->get('/{s1}', 'PublicController@permalinkResolve');

$router->dispatch();
