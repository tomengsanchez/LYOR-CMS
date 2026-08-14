<?php
/**
 * Migration 007: Sample CMS content (idempotent seed)
 */
return [
    'name' => 'migration_007_cms_seed',
    'up' => function (\PDO $db): void {
        $authorId = $db->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetchColumn();

        $db->prepare("
            INSERT IGNORE INTO cms_pages (title, slug, body, status, meta_title, author_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            'Welcome',
            'welcome',
            '<p>Welcome to Simple CMS. Edit this page from <a href="/admin/pages">Admin → Pages</a>.</p>',
            'published',
            'Welcome — Simple CMS',
            $authorId ?: null,
        ]);

        $db->prepare("
            INSERT IGNORE INTO cms_categories (name, slug, description)
            VALUES (?, ?, ?)
        ")->execute(['General', 'general', 'General news and updates']);

        $catId = $db->query("SELECT id FROM cms_categories WHERE slug = 'general' LIMIT 1")->fetchColumn();

        $db->prepare("
            INSERT IGNORE INTO cms_posts (title, slug, excerpt, body, category_id, status, published_at, author_id)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ")->execute([
            'Hello World',
            'hello-world',
            'Your first blog post.',
            '<p>This is a sample post. Create more from Content → Posts.</p>',
            $catId ?: null,
            'published',
            $authorId ?: null,
        ]);
    },
    'down' => function (\PDO $db): void {
        $db->exec("DELETE FROM cms_posts WHERE slug = 'hello-world'");
        $db->exec("DELETE FROM cms_categories WHERE slug = 'general'");
        $db->exec("DELETE FROM cms_pages WHERE slug = 'welcome'");
    },
];
