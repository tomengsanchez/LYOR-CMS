<?php
/**
 * Design-only seed for "The Filipino Men" journal.
 * Applies branding, style pack, empty desks (categories), pages, menu, widgets.
 * Does NOT import essays from xyz/feed.atom (use php cli/import_atom_feed.php).
 *
 * Usage (project root): php cli/seed_filipino_men_site.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\DiscussionSettings;
use App\LayoutBuilder;
use App\Models\AppSettings;
use App\Models\Category;
use App\Models\NavMenu;
use App\Models\Page;
use App\Models\Widget;
use App\PublicTheme;
use App\ReadingSettings;
use App\ThemeStylePack;
use Core\Auth;
use Core\Database;

$db = Database::getInstance();
$admin = $db->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_OBJ);
if (!$admin) {
    fwrite(STDERR, "No users found. Run php cli/migrate.php first.\n");
    exit(1);
}
Auth::login((int) $admin->id);

function tfm_uid(): string
{
    return 'el_' . bin2hex(random_bytes(6));
}

function tfm_mod(string $type, array $data = [], array $design = []): array
{
    return [
        'id' => tfm_uid(),
        'type' => $type,
        'data' => $data,
        'design' => $design,
        'advanced' => [],
    ];
}

function tfm_col(int $width, array $modules): array
{
    return ['id' => tfm_uid(), 'width' => $width, 'settings' => [], 'modules' => $modules];
}

function tfm_row(array $widths, array $modulesPerCol): array
{
    $columns = [];
    foreach ($widths as $i => $w) {
        $columns[] = tfm_col((int) $w, $modulesPerCol[$i] ?? []);
    }
    return ['id' => tfm_uid(), 'settings' => [], 'columns' => $columns];
}

function tfm_section(array $widths, array $modulesPerCol, string $bg = '', string $padding = '2.5rem 0'): array
{
    return [
        'id' => tfm_uid(),
        'type' => 'regular',
        'settings' => ['bg_color' => $bg, 'padding' => $padding, 'css_class' => ''],
        'rows' => [tfm_row($widths, $modulesPerCol)],
    ];
}

function tfm_layout(array $sections): string
{
    return (string) LayoutBuilder::normalizeJson(json_encode([
        'version' => LayoutBuilder::VERSION,
        'sections' => $sections,
    ], JSON_UNESCAPED_UNICODE));
}

function tfm_upsert_page(array $def): int
{
    $existing = Page::findBySlug((string) $def['slug']);
    $payload = [
        'title' => $def['title'],
        'slug' => $def['slug'],
        'body' => $def['body'] ?? '',
        'status' => 'published',
        'content_layout' => $def['content_layout'] ?? '',
        'meta_title' => $def['meta_title'] ?? $def['title'],
        'meta_description' => $def['meta_description'] ?? '',
        'llm_summary' => $def['llm_summary'] ?? '',
        'citation_snippet' => $def['citation'] ?? '',
        'faq_json' => $def['faq_json'] ?? null,
        'robots_noindex' => false,
    ];
    if ($existing) {
        Page::update((int) $existing->id, $payload);
        $id = (int) $existing->id;
        echo "Updated page {$def['slug']} (#{$id})\n";
    } else {
        $id = Page::create($payload);
        if ($id <= 0) {
            fwrite(STDERR, "Failed to create page {$def['slug']}\n");
            exit(1);
        }
        $db = Database::getInstance();
        $db->prepare('UPDATE cms_pages SET slug = ? WHERE id = ?')->execute([$def['slug'], $id]);
        echo "Created page {$def['slug']} (#{$id})\n";
    }
    if (!empty($def['layout'])) {
        Page::saveLayoutJson($id, $def['layout']);
    }
    return $id;
}

function tfm_ensure_category(string $name, string $description): object
{
    $slug = \App\CmsSlug::from($name, 'category');
    $cat = Category::findBySlug($slug);
    if ($cat) {
        Category::update((int) $cat->id, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
        ]);
        echo "Updated category {$slug}\n";
        return Category::find((int) $cat->id);
    }
    $id = Category::create(['name' => $name, 'description' => $description]);
    echo "Created category {$slug} (#{$id})\n";
    return Category::find($id);
}

$desks = [
    ['name' => 'Manhood & Mindset', 'desc' => 'Who you are when no one is looking. Purpose, discipline, and self-respect in a noisy country.'],
    ['name' => 'Pera & Pressure', 'desc' => 'Sahod, padala, loud budgeting, and the fear of running out — without calling yourself kuripot.'],
    ['name' => 'Love in This Economy', 'desc' => 'Dating math, quiet husbands, heartbreak, and love that should not feel like another bill.'],
    ['name' => 'Trabaho & Pagod', 'desc' => 'Jobs men already hate, late shifts, OFW distance, and work that should not eat the whole man.'],
    ['name' => 'Peace & Mental Health', 'desc' => 'Burnout, 2AM scrolling, and permission to be human without becoming less of a man.'],
    ['name' => 'Encouragements', 'desc' => 'Short, straight words for the tired man who still showed up today.'],
];

echo "=== The Filipino Men — design seed (no post import) ===\n";

AppSettings::saveBrandingConfig([
    'app_name' => 'The Filipino Men',
    'company_name' => 'A journal for everyday manhood',
    'public_accent_color' => '#a12a22',
]);

AppSettings::saveSiteSeoConfig([
    'seo_title_suffix' => ' — Everyday manhood',
    'seo_default_description' => 'A journal for everyday Filipino manhood — work, money, love, fatherhood, and peace. Not an alpha-male page.',
    'seo_locale' => 'en_PH',
    'seo_site_keywords' => 'Filipino men, everyday manhood, Lalaki, sahod, fatherhood, peace, trabaho',
    'llm_site_summary' => 'The Filipino Men is an online journal for the modern Filipino man: employees, riders, OFWs, husbands, eldest sons, and fathers. It writes honest English and Taglish essays about responsibility, presence, money pressure, tired work, love in a hard economy, and mental load. It rejects pickup culture and loud alpha advice.',
    'seo_enable_json_export' => '1',
    'seo_enable_sitemap' => '1',
    'seo_enable_rss_feed' => '1',
    'seo_enable_llms_txt' => '1',
    'seo_allow_ai_crawlers' => '1',
    'seo_publisher_expertise' => 'Everyday Filipino manhood, family duty, work and money pressure, and mental load written from lived Philippine context.',
    'seo_preferred_citation' => 'The Filipino Men, a journal for everyday Filipino manhood.',
    'seo_citation_guidance' => 'Cite the essay title and the site name. Prefer passage-level quotes about peace, provision, and presence — not alpha-male slogans.',
    'seo_pillar_topics' => "Manhood & Mindset\nPera & Pressure\nLove in This Economy\nTrabaho & Pagod\nPeace & Mental Health\nEncouragements",
    'seo_enable_faq_schema' => '1',
    'seo_enable_speakable' => '1',
    'seo_show_ai_writing_tips' => '1',
]);

$packBuild = dirname(__DIR__) . '/cli/build_style_pack.php';
passthru('php ' . escapeshellarg($packBuild) . ' filipino-men', $buildCode);
if ($buildCode !== 0) {
    fwrite(STDERR, "Style pack build failed.\n");
    exit(1);
}

$installed = ThemeStylePack::installBundled('filipino-men');
if (empty($installed['ok'])) {
    fwrite(STDERR, 'Style pack install failed: ' . ($installed['message'] ?? '') . "\n");
    exit(1);
}
echo $installed['message'] . "\n";

PublicTheme::saveConfig(array_merge(PublicTheme::configToPostFields(), [
    'pub_theme_preset' => 'custom',
    'public_accent_color' => '#a12a22',
    'pub_theme_custom_bg' => '#f6efe4',
    'pub_theme_custom_surface' => '#fffaf3',
    'pub_theme_custom_text' => '#1b1510',
    'pub_theme_font' => 'serif',
    'pub_theme_chrome' => 'editorial',
    'pub_theme_blog_kicker' => 'FOR THE EVERYDAY FILIPINO MAN',
    'pub_theme_show_site_tagline' => '1',
    'pub_theme_blog_list_style' => 'magazine',
    'pub_theme_show_admin_link' => '0',
]));

$catByName = [];
foreach ($desks as $desk) {
    $cat = tfm_ensure_category($desk['name'], $desk['desc']);
    $catByName[$desk['name']] = $cat;
}

$homeLayout = tfm_layout([
    tfm_section([12], [[
        tfm_mod('heading', ['text' => 'The Filipino Men', 'level' => 1], ['text_align' => 'center']),
        tfm_mod('text', ['text' => '<p><em>A journal for everyday manhood.</em></p><p>Not louder. Not richer. Not alpha.</p><p>Just the man who keeps his word, comes home, and chooses peace in a country that profits from his pressure.</p>'], ['text_align' => 'center']),
        tfm_mod('button', ['label' => 'Meet the journal', 'url' => '/p/about', 'style' => 'primary'], ['text_align' => 'center']),
    ]], '#fffaf3', '3.5rem 0'),
    tfm_section([12], [[
        tfm_mod('heading', ['text' => 'What we believe', 'level' => 2], ['text_align' => 'center']),
        tfm_mod('text', ['text' => '<p>Manhood is not an event. It is a habit on ordinary Tuesdays.</p>'], ['text_align' => 'center']),
    ]], '', '1.5rem 0 0'),
    tfm_section([3, 3, 3, 3], [
        [tfm_mod('blurb', ['title' => 'Responsibility', 'text' => '<p>He does what he said. He owns the mistake without adding <em>kasi</em>.</p>', 'icon' => '1'])],
        [tfm_mod('blurb', ['title' => 'Strength with control', 'text' => '<p>Strong enough to protect the peace — not to win the comment section.</p>', 'icon' => '2'])],
        [tfm_mod('blurb', ['title' => 'Character', 'text' => '<p>How he talks to the waiter is who he is. Especially when it is inconvenient.</p>', 'icon' => '3'])],
        [tfm_mod('blurb', ['title' => 'Presence', 'text' => '<p>He is actually there. Not just charging his body on the sofa.</p>', 'icon' => '4'])],
    ], '#f6efe4', '1.5rem 0 2.5rem'),
    tfm_section([12], [[
        tfm_mod('heading', ['text' => 'The desks', 'level' => 2], ['text_align' => 'center']),
        tfm_mod('text', ['text' => '<p>Essays are not imported yet. The house is ready. The archive comes next.</p>'], ['text_align' => 'center']),
    ]], '', '1rem 0 0'),
    tfm_section([4, 4, 4], [
        [tfm_mod('blurb', ['title' => 'Manhood & Mindset', 'text' => '<p>Who you are when no one is looking.</p>', 'icon' => '◆', 'url' => '/blog/category/manhood-mindset'])],
        [tfm_mod('blurb', ['title' => 'Pera & Pressure', 'text' => '<p>Protecting what little you have is not being kuripot.</p>', 'icon' => '₱', 'url' => '/blog/category/pera-pressure'])],
        [tfm_mod('blurb', ['title' => 'Love in This Economy', 'text' => '<p>Not ayaw magmahal. Ayaw manakit ng walang maayos na buhay.</p>', 'icon' => '♡', 'url' => '/blog/category/love-in-this-economy'])],
    ], '', '0 0 1rem'),
    tfm_section([4, 4, 4], [
        [tfm_mod('blurb', ['title' => 'Trabaho & Pagod', 'text' => '<p>The grind from the site to the office to abroad.</p>', 'icon' => '⚒', 'url' => '/blog/category/trabaho-pagod'])],
        [tfm_mod('blurb', ['title' => 'Peace & Mental Health', 'text' => '<p>The new alpha is the man who can stay calm.</p>', 'icon' => '◎', 'url' => '/blog/category/peace-mental-health'])],
        [tfm_mod('blurb', ['title' => 'Encouragements', 'text' => '<p>Short words for the tired man who still showed up.</p>', 'icon' => '✦', 'url' => '/blog/category/encouragements'])],
    ], '', '0 0 2rem'),
    tfm_section([12], [[
        tfm_mod('cta', [
            'title' => 'Essays come later.',
            'text' => '<p>This design pass builds the house only. The LalakiPH archive stays offline until you say import.</p>',
            'label' => 'See the desks',
            'url' => '/p/topics',
            'style' => 'primary',
        ]),
    ]], '#fffaf3', '2rem 0 3rem'),
]);

$aboutLayout = tfm_layout([
    tfm_section([12], [[
        tfm_mod('heading', ['text' => 'About The Filipino Men', 'level' => 1], ['text_align' => 'left']),
        tfm_mod('text', ['text' => '<p><strong>Redefining what it means to be a man in the Philippines today.</strong></p><p>For a long time, manhood here was defined by other people. Be tough. Don\'t cry. Be the provider. Don\'t show weakness.</p><p>No one taught us how to <em>live</em> it — how to be a good son, a faithful husband, a present father, a man of principle in a world that keeps changing.</p><p>That is why this journal exists.</p>']),
    ]], '#fffaf3', '2.5rem 0'),
    tfm_section([6, 6], [
        [
            tfm_mod('heading', ['text' => 'Who we are', 'level' => 2], ['text_align' => 'left']),
            tfm_mod('text', ['text' => '<p>We are not an alpha-male page. We are not a pickup blog. We are not a quote mill.</p><p>We are a journal for everyday manhood — the kind you live on a Monday, not the kind you see in movies.</p><p>We write for the working man, the student, the single guy figuring out his path, the husband learning to lead, the father trying to be better than his father was.</p>']),
        ],
        [
            tfm_mod('heading', ['text' => 'How we write', 'level' => 2], ['text_align' => 'left']),
            tfm_mod('text', ['text' => '<p>Clear English and Taglish. Straight to the point.</p><p>We will not tell you that you are perfect. We will not tell you that manhood is easy.</p><p>We will tell you the truth, and remind you that good men still exist — and you can be one of them.</p>']),
        ],
    ], '', '1rem 0 2.5rem'),
    tfm_section([12], [[
        tfm_mod('icon_list', ['items' => [
            ['icon' => '1', 'text' => 'Responsibility — he does what he said he would do.'],
            ['icon' => '2', 'text' => 'Strength with control — used to protect, provide, and stay calm.'],
            ['icon' => '3', 'text' => 'Character — respect for people who can do nothing for you.'],
            ['icon' => '4', 'text' => 'Presence — actually there, not just in the room.'],
        ]]),
        tfm_mod('cta', [
            'title' => 'For the everyday Filipino man.',
            'text' => '<p>This is not a page for perfect men. This is a page for men who are trying.</p>',
            'label' => 'Read the FAQ',
            'url' => '/p/faq',
            'style' => 'primary',
        ]),
    ]], '#f6efe4', '2rem 0 3rem'),
]);

$topicCols = [];
foreach ($desks as $i => $desk) {
    $slug = \App\CmsSlug::from($desk['name'], 'category');
    $topicCols[] = [tfm_mod('blurb', [
        'title' => $desk['name'],
        'text' => '<p>' . htmlspecialchars($desk['desc'], ENT_QUOTES, 'UTF-8') . '</p>',
        'icon' => (string) ($i + 1),
        'url' => '/blog/category/' . $slug,
    ])];
}

$topicsLayout = tfm_layout([
    tfm_section([12], [[
        tfm_mod('heading', ['text' => 'The desks', 'level' => 1], ['text_align' => 'center']),
        tfm_mod('text', ['text' => '<p>Same labels as the essay archive. Empty on purpose — posts are not imported yet.</p>'], ['text_align' => 'center']),
    ]], '#fffaf3', '2.5rem 0 1rem'),
    tfm_section([4, 4, 4], array_slice($topicCols, 0, 3), '', '0 0 1rem'),
    tfm_section([4, 4, 4], array_slice($topicCols, 3, 3), '', '0 0 2.5rem'),
]);

$faqItems = [
    ['title' => 'What is The Filipino Men?', 'body' => '<p>A Filipino men\'s lifestyle journal. It writes about what it means to be a man in the Philippines today: work, money, marriage, fatherhood, barkada, heartbreak, and mental load. It is not an alpha-male page and not a pickup blog.</p>'],
    ['title' => 'Who should read this?', 'body' => '<p>The everyday Filipino man who carries responsibility. The employee, rider, OFW, husband, eldest son, and father who is tired but still showing up. Written for men who are trying, not for men who want to look rich.</p>'],
    ['title' => 'What does everyday manhood mean here?', 'body' => '<p>Not a big event. It is how you keep your word, how you come home, how you handle sahod, and how you treat people who cannot do anything for you. It happens on ordinary Tuesdays.</p>'],
    ['title' => 'Is this an alpha or high-value-man page?', 'body' => '<p>No. Peace, presence, and responsibility matter more than dominance, flexing, or collecting women.</p>'],
    ['title' => 'What topics will the essays cover?', 'body' => '<p>Fatherhood and late shifts. Jobs men already hate but cannot leave yet. Money and the fear of running out. Marriage when the husband goes quiet. Barkada that starts costing the future. Sleep, burnout, and family duty.</p>'],
    ['title' => 'Where are the essays?', 'body' => '<p>Not imported yet. The desks, About, and FAQ are designed first so the house is ready before the archive moves in.</p>'],
];

$faqLayout = tfm_layout([
    tfm_section([12], [[
        tfm_mod('heading', ['text' => 'FAQ', 'level' => 1], ['text_align' => 'left']),
        tfm_mod('text', ['text' => '<p><strong>The Filipino Men is a journal for everyday Filipino manhood.</strong> These are the questions readers ask first.</p>']),
        tfm_mod('accordion', ['first_open' => true, 'items' => $faqItems]),
    ]], '#fffaf3', '2.5rem 0 3rem'),
]);

$homeId = tfm_upsert_page([
    'title' => 'The Filipino Men',
    'slug' => 'the-filipino-men',
    'content_layout' => '',
    'meta_title' => 'The Filipino Men',
    'meta_description' => 'A journal for everyday Filipino manhood — peace over pressure, presence over performance.',
    'llm_summary' => 'Homepage of The Filipino Men, a journal for everyday Filipino manhood. Introduces four habits — responsibility, strength with control, character, presence — and six essay desks. Essays are not imported yet.',
    'citation' => 'The Filipino Men is a journal for everyday Filipino manhood: responsibility, presence, and peace over pressure.',
    'layout' => $homeLayout,
]);

$aboutId = tfm_upsert_page([
    'title' => 'About',
    'slug' => 'about',
    'content_layout' => 'normal',
    'meta_title' => 'About The Filipino Men',
    'meta_description' => 'The Filipino Men is a journal for everyday Filipino manhood — for working men, husbands, fathers, and sons, not for alpha-male or pickup culture.',
    'llm_summary' => 'About The Filipino Men: an online publication for the modern Filipino man. It rejects alpha and pickup culture and measures manhood by responsibility, controlled strength, character, and presence.',
    'citation' => 'The Filipino Men writes for men who are trying — not for men who want to look rich.',
    'layout' => $aboutLayout,
]);

$topicsId = tfm_upsert_page([
    'title' => 'Topics',
    'slug' => 'topics',
    'content_layout' => '',
    'meta_title' => 'Topics — The Filipino Men',
    'meta_description' => 'Six desks: Manhood & Mindset, Pera & Pressure, Love in This Economy, Trabaho & Pagod, Peace & Mental Health, Encouragements.',
    'llm_summary' => 'Topic desks for The Filipino Men journal. Essay archive not imported yet.',
    'citation' => 'The Filipino Men organizes essays into six desks of everyday manhood.',
    'layout' => $topicsLayout,
]);

$faqId = tfm_upsert_page([
    'title' => 'FAQ',
    'slug' => 'faq',
    'content_layout' => 'narrow',
    'meta_title' => 'FAQ — The Filipino Men',
    'meta_description' => 'Who The Filipino Men is for, what everyday manhood means, and why this is not an alpha-male page.',
    'llm_summary' => 'FAQ for The Filipino Men: a journal for everyday Filipino manhood, not an alpha or pickup page. Essays are not imported yet.',
    'citation' => 'The Filipino Men is for the everyday Filipino man who carries responsibility.',
    'faq_json' => json_encode(array_map(static function (array $item): array {
        return ['q' => $item['title'], 'a' => strip_tags($item['body'])];
    }, $faqItems), JSON_UNESCAPED_UNICODE),
    'layout' => $faqLayout,
]);

ReadingSettings::save([
    'reading_show_on_front' => ReadingSettings::FRONT_PAGE,
    'reading_page_on_front' => $homeId,
    'reading_posts_per_page' => 8,
]);

DiscussionSettings::save([
    'discussion_comments_enabled' => '1',
    'discussion_moderation' => '1',
    'discussion_require_name_email' => '1',
    'discussion_show_sidebar' => '1',
]);

NavMenu::savePrimaryItems([
    ['label' => 'Home', 'item_type' => NavMenu::TYPE_HOME, 'custom_url' => '/'],
    ['label' => 'Essays', 'item_type' => NavMenu::TYPE_BLOG, 'custom_url' => '/blog'],
    ['label' => 'Topics', 'item_type' => NavMenu::TYPE_PAGE, 'object_id' => $topicsId],
    ['label' => 'About', 'item_type' => NavMenu::TYPE_PAGE, 'object_id' => $aboutId],
    ['label' => 'FAQ', 'item_type' => NavMenu::TYPE_PAGE, 'object_id' => $faqId],
]);

Widget::saveAreaWidgets(Widget::AREA_AFTER_HEADER, [
    [
        'widget_type' => 'cta',
        'title' => '',
        'is_enabled' => 1,
        'config' => [
            'headline' => 'A journal, not a flex.',
            'text' => 'Essays on work, money, love, fatherhood, and peace — written for men who are trying, not performing.',
            'label' => 'Who we are',
            'url' => '/p/about',
        ],
    ],
]);
Widget::saveAreaWidgets(Widget::AREA_HOME, []);
Widget::saveAreaWidgets(Widget::AREA_AFTER_CONTENT, [
    [
        'widget_type' => 'cta',
        'title' => '',
        'is_enabled' => 1,
        'config' => [
            'headline' => 'Keep going.',
            'text' => 'This is not a page for perfect men. This is a page for men who show up again tomorrow.',
            'label' => 'Read the FAQ',
            'url' => '/p/faq',
        ],
    ],
]);
Widget::saveAreaWidgets(Widget::AREA_FOOTER, [
    [
        'widget_type' => 'custom_html',
        'title' => 'The Filipino Men',
        'is_enabled' => 1,
        'config' => [
            'html' => '<p>A journal for everyday Filipino manhood. Responsibility. Presence. Peace over pressure.</p>',
        ],
    ],
    [
        'widget_type' => 'categories',
        'title' => 'Desks',
        'is_enabled' => 1,
        'config' => [],
    ],
    [
        'widget_type' => 'pages',
        'title' => 'In this house',
        'is_enabled' => 1,
        'config' => [],
    ],
]);

echo "Homepage page_id={$homeId}\n";
echo "About={$aboutId} Topics={$topicsId} FAQ={$faqId}\n";
echo "Done. Open http://cms.local/ — no essays imported.\n";
