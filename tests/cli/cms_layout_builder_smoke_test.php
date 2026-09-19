<?php
/**
 * Smoke test: LayoutBuilder Phase 1 parse/normalize/render + precedence.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\ContentBlocks;
use App\LayoutBuilder;

$starter = LayoutBuilder::starterLayout();
assert(!empty($starter['sections']), 'starter has sections');
assert(isset(LayoutBuilder::moduleTypes()['heading']), 'heading module registered');
assert(($catalog = LayoutBuilder::moduleCatalog()) && isset($catalog['heading']['hint']) && $catalog['heading']['hint'] !== '', 'module catalog hints');
assert(LayoutBuilder::moduleTypes()['heading'] === 'Heading', 'types derived from catalog');
assert(isset($catalog['heading']['fields']) && ($catalog['heading']['fields'][0]['name'] ?? '') === 'text', 'heading Content fields in catalog');
assert(($catalog['heading']['defaults']['level'] ?? null) === 2, 'heading editor defaults');
assert(($catalog['text']['fields'][0]['type'] ?? '') === 'rich', 'text Content field is rich');
assert(in_array('type', $catalog['heading']['design'] ?? [], true), 'heading Design has type');
assert(!in_array('type', $catalog['spacer']['design'] ?? [], true), 'spacer Design has no type');
assert(($catalog['carousel']['custom'] ?? '') === 'carousel', 'carousel stays custom in catalog');
assert(($catalog['accordion']['custom'] ?? '') === 'accordion', 'accordion custom catalog');
assert(($catalog['video']['fields'][0]['name'] ?? '') === 'url', 'video url field in catalog');
assert(($catalog['tabs']['custom'] ?? '') === 'tabs', 'tabs custom catalog');
assert(($catalog['icon_list']['custom'] ?? '') === 'icon_list', 'icon list custom catalog');
assert(($catalog['gallery']['custom'] ?? '') === 'gallery', 'gallery custom catalog');
assert(($catalog['testimonial']['custom'] ?? '') === 'testimonial', 'testimonial custom catalog');
assert(($catalog['inner_row']['custom'] ?? '') === 'inner_row', 'inner row custom catalog');
$catalogJson = json_encode($catalog, JSON_UNESCAPED_UNICODE);
assert(is_string($catalogJson) && json_decode($catalogJson, true)['text']['fields'][0]['name'] === 'text', 'catalog JSON for editor data-modules');

$html = LayoutBuilder::render($starter);
assert(str_contains($html, 'cms-layout'), 'render wraps cms-layout');
assert(str_contains($html, 'Welcome'), 'starter heading text');

$raw = json_encode([
    'version' => 1,
    'sections' => [
        [
            'id' => 'sec1',
            'type' => 'regular',
            'settings' => ['bg_color' => '#ffffff', 'padding' => '1rem'],
            'rows' => [
                [
                    'id' => 'row1',
                    'settings' => [],
                    'columns' => [
                        [
                            'id' => 'col1',
                            'width' => 6,
                            'settings' => [],
                            'modules' => [
                                [
                                    'id' => 'm1',
                                    'type' => 'heading',
                                    'data' => ['text' => 'Hello <b>x</b>', 'level' => 2],
                                    'design' => ['text_align' => 'center'],
                                    'advanced' => ['css_class' => 'my-mod', 'hide_mobile' => true],
                                ],
                                [
                                    'id' => 'm2',
                                    'type' => 'button',
                                    'data' => ['label' => 'Go', 'url' => 'https://example.com', 'style' => 'primary'],
                                    'design' => [],
                                    'advanced' => [],
                                ],
                                [
                                    'id' => 'm3',
                                    'type' => 'html',
                                    'data' => ['html' => '<p>ok</p><script>alert(1)</script>'],
                                    'design' => [],
                                    'advanced' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE);

$norm = LayoutBuilder::normalizeJson($raw);
assert(is_string($norm) && $norm !== '', 'normalizeJson returns string');
$parsed = LayoutBuilder::parse($norm);
assert($parsed['sections'][0]['rows'][0]['columns'][0]['width'] === 6, 'width preserved');

$rendered = LayoutBuilder::render($parsed);
assert(str_contains($rendered, 'Hello &lt;b&gt;x&lt;/b&gt;'), 'heading escaped on render');
assert(str_contains($rendered, 'cms-mod-button'), 'button module');
assert(!str_contains($rendered, '<script>'), 'script stripped from html module');
assert(str_contains($rendered, 'my-mod'), 'css class applied');
assert(str_contains($rendered, 'd-none') && str_contains($rendered, 'd-md-block'), 'hide mobile classes');

$plain = LayoutBuilder::plainText($parsed);
assert(str_contains($plain, 'Hello'), 'plain text has heading');
assert(str_contains($plain, 'Go'), 'plain text has button label');

$entityLayout = (object) [
    'layout_json' => $norm,
    'blocks_json' => null,
    'body' => '<p>Body only</p>',
];
$out = ContentBlocks::renderEntity($entityLayout, (string) $entityLayout->body);
assert(str_contains($out, 'cms-layout'), 'layout preferred over body');
assert(str_contains($out, 'cms-layout-css'), 'public stylesheet tag');
assert(str_contains($out, 'cms-el-'), 'element CSS class');
assert(str_contains($out, 'background-color:#ffffff'), 'section bg in stylesheet');
assert(str_contains($out, 'text-align:center'), 'heading align in stylesheet');
assert(!str_contains($out, 'Body only'), 'body not used when layout present');

$entityBody = (object) [
    'layout_json' => null,
    'blocks_json' => null,
    'body' => '<p>Body only</p>',
];
$outBody = ContentBlocks::renderEntity($entityBody, (string) $entityBody->body);
assert(str_contains($outBody, 'Body only'), 'body fallback');

$blurbRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'sec-bl',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'row-bl',
            'settings' => [],
            'columns' => [[
                'id' => 'col-bl',
                'width' => 12,
                'settings' => [],
                'modules' => [[
                    'id' => 'm-bl',
                    'type' => 'blurb',
                    'data' => ['title' => 'Feature', 'text' => 'Left please.', 'icon' => '★'],
                    'design' => ['text_align' => 'left'],
                    'advanced' => [],
                ]],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$blurbHtml = LayoutBuilder::render(LayoutBuilder::parse(LayoutBuilder::normalizeJson($blurbRaw)));
assert(str_contains($blurbHtml, 'cms-mod-blurb'), 'blurb markup');
assert(str_contains($blurbHtml, '.cms-el-m-bl{'), 'blurb element rule');
assert(str_contains($blurbHtml, 'text-align:left'), 'blurb Design left compiles');
assert(str_contains($blurbHtml, '.cms-el-m-bl>*{text-align:left}'), 'blurb inner wrapper follows Design align');

$hasLayout = LayoutBuilder::hasLayout((object) ['layout_json' => null]);
assert($hasLayout === false, 'no layout');

$empty = LayoutBuilder::normalizeJson('{"version":1,"sections":[]}');
assert($empty === null, 'empty sections normalize to null');

assert(isset(LayoutBuilder::moduleTypes()['carousel']), 'carousel module registered');

$carouselRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'sec-c',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'row-c',
            'settings' => [],
            'columns' => [[
                'id' => 'col-c',
                'width' => 12,
                'settings' => [],
                'modules' => [[
                    'id' => 'mod-carousel',
                    'type' => 'carousel',
                    'data' => [
                        'autoplay' => true,
                        'interval_ms' => 4000,
                        'show_arrows' => true,
                        'show_dots' => true,
                        'slides' => [
                            ['media_id' => null, 'url' => 'https://example.com/a.jpg', 'alt' => 'Alpha', 'caption' => 'First slide', 'link' => ''],
                            ['media_id' => null, 'url' => 'https://example.com/b.jpg', 'alt' => 'Beta', 'caption' => '', 'link' => 'https://example.com'],
                        ],
                    ],
                    'design' => [],
                    'advanced' => [],
                ]],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$carouselNorm = LayoutBuilder::normalizeJson($carouselRaw);
$carouselParsed = LayoutBuilder::parse($carouselNorm);
$carouselHtml = LayoutBuilder::render($carouselParsed);
assert(str_contains($carouselHtml, 'cms-carousel'), 'carousel wrapper');
assert(str_contains($carouselHtml, 'data-cms-carousel'), 'carousel data attr');
assert(str_contains($carouselHtml, 'First slide'), 'carousel caption');
assert(str_contains($carouselHtml, 'example.com/a.jpg'), 'carousel image url');
$carouselPlain = LayoutBuilder::plainText($carouselParsed);
assert(str_contains($carouselPlain, 'First slide'), 'carousel plain caption');
assert(str_contains($carouselPlain, 'Beta'), 'carousel plain alt fallback');

$oneMod = static function (array $mod): string {
    return json_encode([
        'version' => 1,
        'sections' => [[
            'id' => 'sec-m',
            'type' => 'regular',
            'settings' => [],
            'rows' => [[
                'id' => 'row-m',
                'settings' => [],
                'columns' => [[
                    'id' => 'col-m',
                    'width' => 12,
                    'settings' => [],
                    'modules' => [$mod],
                ]],
            ]],
        ]],
    ], JSON_UNESCAPED_UNICODE);
};

$accParsed = LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-acc',
    'type' => 'accordion',
    'data' => [
        'first_open' => true,
        'items' => [
            ['title' => '<script>alert(1)</script>', 'body' => "<img src=x onerror=alert(1)>\nSafe line"],
            ['title' => 'Second', 'body' => 'Two'],
        ],
    ],
    'design' => [],
    'advanced' => [],
])));
$accHtml = LayoutBuilder::render($accParsed);
assert(str_contains($accHtml, 'cms-mod-accordion'), 'accordion wrapper');
assert(str_contains($accHtml, '<details'), 'accordion details');
assert(str_contains($accHtml, '<summary'), 'accordion summary');
assert(str_contains($accHtml, '&lt;script&gt;'), 'accordion title escaped');
assert(!str_contains($accHtml, '<script>alert'), 'accordion script not executed');
assert(!str_contains($accHtml, '<img'), 'accordion img tag stripped from body');
assert(!str_contains($accHtml, 'onerror'), 'accordion onerror stripped from body');
assert(str_contains($accHtml, 'Safe line'), 'accordion body text kept');
$accPlain = LayoutBuilder::plainText($accParsed);
assert(str_contains($accPlain, '<script>alert(1)</script>'), 'plain text keeps original title');
assert(str_contains($accPlain, 'Second'), 'accordion second title in plain text');
assert(str_contains($accPlain, 'Two'), 'accordion body in plain text');

$ytParsed = LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-yt',
    'type' => 'video',
    'data' => [
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1',
        'caption' => 'Demo clip',
    ],
    'design' => [],
    'advanced' => [],
])));
$ytHtml = LayoutBuilder::render($ytParsed);
assert(str_contains($ytHtml, 'cms-mod-video'), 'video wrapper');
assert(str_contains($ytHtml, 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'), 'youtube nocookie embed');
assert(!str_contains($ytHtml, 'javascript:'), 'no javascript url');
assert(!str_contains($ytHtml, 'youtube.com/watch'), 'watch url not used as iframe src');
assert(str_contains($ytHtml, 'Demo clip'), 'video caption');

$evilVid = LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-evil',
    'type' => 'video',
    'data' => ['url' => 'javascript:alert(1)', 'caption' => 'x'],
    'design' => [],
    'advanced' => [],
])));
$evilHtml = LayoutBuilder::render($evilVid);
assert(($evilVid['sections'][0]['rows'][0]['columns'][0]['modules'][0]['data']['url'] ?? 'unset') === '', 'javascript video url dropped');
assert(!str_contains($evilHtml, 'javascript:'), 'javascript not in video html');
assert(!str_contains($evilHtml, '<iframe'), 'no iframe for invalid video');

$hostVid = LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-host',
    'type' => 'video',
    'data' => ['url' => 'https://evil.example/embed/abcd', 'caption' => ''],
])));
assert(($hostVid['sections'][0]['rows'][0]['columns'][0]['modules'][0]['data']['url'] ?? 'unset') === '', 'unknown host dropped');

$vimeoHtml = LayoutBuilder::render(LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-vm',
    'type' => 'video',
    'data' => ['url' => 'https://vimeo.com/123456789'],
]))));
assert(str_contains($vimeoHtml, 'https://player.vimeo.com/video/123456789'), 'vimeo player embed');

$fileHtml = LayoutBuilder::render(LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-file',
    'type' => 'video',
    'data' => ['url' => 'https://cdn.example.com/clip.mp4'],
]))));
assert(str_contains($fileHtml, '<video'), 'file video element');
assert(str_contains($fileHtml, 'https://cdn.example.com/clip.mp4'), 'file src kept');

$tabsParsed = LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-tabs',
    'type' => 'tabs',
    'data' => [
        'items' => [
            ['title' => '<b>One</b>', 'body' => '<script>x</script>Safe'],
            ['title' => 'Two', 'body' => 'Second'],
        ],
    ],
])));
$tabsHtml = LayoutBuilder::render($tabsParsed);
assert(str_contains($tabsHtml, 'cms-mod-tabs'), 'tabs wrapper');
assert(str_contains($tabsHtml, 'type="radio"'), 'tabs radios');
assert(str_contains($tabsHtml, '&lt;b&gt;One'), 'tabs title escaped');
assert(!str_contains($tabsHtml, '<script>x'), 'tabs script stripped from body');
assert(str_contains($tabsHtml, 'Safe'), 'tabs body text kept');
assert(str_contains(LayoutBuilder::plainText($tabsParsed), 'Second'), 'tabs plain body');

$listHtml = LayoutBuilder::render(LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-ilist',
    'type' => 'icon_list',
    'data' => [
        'items' => [
            ['icon' => '<img>', 'text' => 'Alpha'],
            ['icon' => '✓', 'text' => 'Beta'],
        ],
    ],
]))));
assert(str_contains($listHtml, 'cms-mod-icon-list'), 'icon list wrapper');
assert(str_contains($listHtml, '&lt;img&gt;'), 'icon escaped');
assert(str_contains($listHtml, 'Alpha'), 'icon list text');
assert(!str_contains($listHtml, '<img>'), 'raw img dropped');

$galParsed = LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-gal',
    'type' => 'gallery',
    'data' => [
        'columns' => 5,
        'items' => [
            ['url' => 'https://example.com/g.jpg', 'alt' => 'Grid', 'caption' => '<b>Cap</b>', 'link' => 'javascript:alert(1)'],
        ],
    ],
])));
assert(($galParsed['sections'][0]['rows'][0]['columns'][0]['modules'][0]['data']['columns'] ?? 0) === 3, 'gallery columns clamped');
$galHtml = LayoutBuilder::render($galParsed);
assert(str_contains($galHtml, 'cms-mod-gallery--cols-3'), 'gallery grid class');
assert(str_contains($galHtml, 'example.com/g.jpg'), 'gallery image url');
assert(str_contains($galHtml, '&lt;b&gt;Cap'), 'gallery caption escaped');
assert(!str_contains($galHtml, 'javascript:'), 'gallery javascript link dropped');
assert(str_contains(LayoutBuilder::plainText($galParsed), 'Cap'), 'gallery plain caption');

$tmlParsed = LayoutBuilder::parse(LayoutBuilder::normalizeJson($oneMod([
    'id' => 'mod-tml',
    'type' => 'testimonial',
    'data' => [
        'items' => [
            ['quote' => '<script>x</script>Nice', 'name' => '<b>Pat</b>', 'role' => 'Editor'],
        ],
    ],
])));
$tmlHtml = LayoutBuilder::render($tmlParsed);
assert(str_contains($tmlHtml, 'cms-mod-testimonial'), 'testimonial wrapper');
assert(str_contains($tmlHtml, '&lt;script&gt;'), 'testimonial quote escaped');
assert(str_contains($tmlHtml, '&lt;b&gt;Pat'), 'testimonial name escaped');
assert(!str_contains($tmlHtml, '<script>x'), 'testimonial script not raw');
assert(str_contains(LayoutBuilder::plainText($tmlParsed), 'Editor'), 'testimonial role in plain text');

$ctaRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'sec-cta',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'row-cta',
            'settings' => [],
            'columns' => [[
                'id' => 'col-cta',
                'width' => 12,
                'settings' => [],
                'modules' => [
                    [
                        'id' => 'm-cta',
                        'type' => 'cta',
                        'data' => [
                            'title' => 'Join us',
                            'text' => 'Details',
                            'label' => 'Sign up',
                            'url' => 'https://example.com',
                            'style' => 'outline',
                            'new_tab' => true,
                        ],
                        'design' => [],
                        'advanced' => [],
                    ],
                    [
                        'id' => 'm-btn',
                        'type' => 'button',
                        'data' => [
                            'label' => 'Docs',
                            'url' => 'https://example.com/docs',
                            'style' => 'secondary',
                            'new_tab' => true,
                        ],
                        'design' => [],
                        'advanced' => [],
                    ],
                ],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$ctaHtml = LayoutBuilder::render(LayoutBuilder::parse(LayoutBuilder::normalizeJson($ctaRaw)));
assert(str_contains($ctaHtml, 'btn-outline-primary'), 'cta outline style');
assert(str_contains($ctaHtml, 'cms-mod-cta-btn'), 'cta button class');
assert(str_contains($ctaHtml, 'target="_blank"'), 'new tab on cta/button');
assert(str_contains($ctaHtml, 'rel="noopener noreferrer"'), 'noopener on new tab');
assert(str_contains($ctaHtml, 'btn-secondary'), 'button secondary style');

$colSizeRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'sec-w',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'row-w',
            'settings' => ['min_height' => '200px'],
            'columns' => [
                [
                    'id' => 'col-5',
                    'width' => 5,
                    'settings' => ['min_height' => '240px', 'valign' => 'center'],
                    'settings_tablet' => ['min_height' => '160px'],
                    'modules' => [[
                        'id' => 'm-h',
                        'type' => 'heading',
                        'data' => ['text' => 'Five', 'level' => 2],
                        'design' => [],
                        'advanced' => [],
                    ]],
                ],
                [
                    'id' => 'col-7',
                    'width' => 7,
                    'settings' => [],
                    'modules' => [],
                ],
            ],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$colParsed = LayoutBuilder::parse(LayoutBuilder::normalizeJson($colSizeRaw));
assert((int) $colParsed['sections'][0]['rows'][0]['columns'][0]['width'] === 5, 'odd width 5 kept');
assert($colParsed['sections'][0]['rows'][0]['columns'][0]['settings']['min_height'] === '240px', 'column min_height kept');
assert(($colParsed['sections'][0]['rows'][0]['columns'][0]['settings_tablet']['min_height'] ?? '') === '160px', 'tablet min_height kept');
$colHtml = LayoutBuilder::render($colParsed);
assert(str_contains($colHtml, 'col-md-5'), 'public col-md-5');
assert(str_contains($colHtml, 'cms-layout-css'), 'column size stylesheet');
assert(str_contains($colHtml, 'min-height:240px'), 'public min-height');
assert(str_contains($colHtml, 'min-height:160px'), 'tablet min-height');
assert(str_contains($colHtml, '@media (max-width:' . LayoutBuilder::CSS_TABLET_MAX . ')'), 'tablet media query');
assert(str_contains($colHtml, 'cms-layout-column--valign-center'), 'valign class');
assert(str_contains($colHtml, 'min-height:200px'), 'row min-height');
assert(!preg_match('/cms-layout-column[^>]*\sstyle=/', $colHtml), 'min-height not inline on column');

$heroRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'sec-h',
        'type' => 'fullwidth',
        'settings' => [
            'bg_color' => 'accent',
            'bg_image' => 'https://example.com/hero.jpg',
            'bg_overlay' => '#000000',
            'bg_overlay_opacity' => 40,
            'shape_top' => 'wave',
            'shape_top_color' => 'bg',
            'shape_top_height' => 'sm',
            'bg_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ],
        'rows' => [[
            'id' => 'row-h',
            'settings' => ['col_reverse_mobile' => true],
            'columns' => [[
                'id' => 'col-h',
                'width' => 12,
                'settings' => [],
                'modules' => [[
                    'id' => 'm-hx',
                    'type' => 'heading',
                    'data' => ['text' => 'Hero', 'level' => 2],
                    'design' => [
                        'text_color' => 'surface',
                        'padding' => '1rem 0px 0px 2rem',
                        'border_radius' => '8px',
                        'box_shadow' => 'md',
                        'font_weight' => '700',
                        'border_color' => 'accent',
                        'font_family' => 'serif',
                        'letter_spacing' => 'wide',
                        'text_transform' => 'uppercase',
                        'position' => 'sticky',
                        'z_index' => '10',
                        'sticky_offset' => 'sm',
                    ],
                    'design_hover' => ['text_color' => 'accent'],
                    'advanced' => [],
                ]],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$hero = LayoutBuilder::parse(LayoutBuilder::normalizeJson($heroRaw));
assert(($hero['sections'][0]['settings']['bg_color'] ?? '') === 'accent', 'accent token stored');
assert(($hero['sections'][0]['settings']['shape_top'] ?? '') === 'wave', 'shape key stored');
assert(($hero['sections'][0]['settings']['bg_video_url'] ?? '') !== '', 'video url stored');
assert(!empty($hero['sections'][0]['rows'][0]['settings']['col_reverse_mobile']), 'reverse stored');
assert(($hero['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design']['text_color'] ?? '') === 'surface', 'surface token stored');
assert(($hero['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design_hover']['text_color'] ?? '') === 'accent', 'hover token stored');
$heroHtml = LayoutBuilder::render($hero);
assert(str_contains($heroHtml, 'var(--pub-accent)'), 'token compiles to CSS variable');
assert(str_contains($heroHtml, 'var(--pub-surface)'), 'heading uses surface token');
assert(str_contains($heroHtml, ':hover'), 'hover rule compiled');
assert(str_contains($heroHtml, '.public-site .cms-el-m-hx:hover .btn'), 'hover beats public button styles');
assert(str_contains($heroHtml, 'padding:1rem 0px 0px 2rem'), 'four-sided padding compiles');
assert(str_contains($heroHtml, 'border-radius:8px'), 'radius compiles');
assert(str_contains($heroHtml, 'box-shadow:0 4px 12px'), 'shadow preset compiles');
assert(str_contains($heroHtml, 'font-weight:700'), 'font weight compiles');
assert(str_contains($heroHtml, 'Georgia,"Times New Roman",Times,serif'), 'serif stack compiles');
assert(str_contains($heroHtml, 'letter-spacing:0.05em'), 'letter spacing preset compiles');
assert(str_contains($heroHtml, 'text-transform:uppercase'), 'text transform compiles');
assert(str_contains($heroHtml, 'position:sticky'), 'sticky position compiles');
assert(str_contains($heroHtml, 'top:1rem'), 'sticky offset compiles');
assert(str_contains($heroHtml, 'z-index:10'), 'z-index compiles');
assert(($hero['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design']['font_family'] ?? '') === 'serif', 'font family key stored');
assert(str_contains($heroHtml, 'border-color:var(--pub-accent)'), 'border token compiles');
assert(str_contains($heroHtml, 'url("https://example.com/hero.jpg")'), 'background image url');
assert(str_contains($heroHtml, 'linear-gradient(rgba(0,0,0,0.4)'), 'overlay gradient');
assert(str_contains($heroHtml, 'cms-shape--wave'), 'shape divider class');
assert(str_contains($heroHtml, 'cms-shape--top'), 'shape top side');
assert(str_contains($heroHtml, 'fill="currentColor"'), 'shape uses currentColor');
assert(str_contains($heroHtml, '.cms-el-sec-h>.cms-shape--top{color:var(--pub-bg)}'), 'shape color compiles to child rule');
assert(str_contains($heroHtml, 'youtube-nocookie.com/embed/dQw4w9WgXcQ'), 'youtube watch becomes nocookie embed');
assert(str_contains($heroHtml, 'autoplay=1'), 'background video autoplay constructed');
assert(str_contains($heroHtml, 'playlist=dQw4w9WgXcQ'), 'youtube loop playlist constructed');
assert(!str_contains($heroHtml, 'youtube.com/watch'), 'raw watch URL not used as iframe src');
assert(str_contains($heroHtml, 'cms-layout-row--reverse-mobile'), 'row reverse class');
assert(!str_contains($heroHtml, 'javascript:'), 'no javascript in compiled CSS');

$badHero = LayoutBuilder::parse(LayoutBuilder::normalizeJson(json_encode([
    'sections' => [[
        'id' => 'sec-x',
        'type' => 'regular',
        'settings' => ['bg_image' => 'https://example.com/x.jpg")foo', 'bg_color' => 'not-a-color', 'box_shadow' => 'foo);color:red', 'shape_top' => '<script>', 'bg_video_url' => 'javascript:alert(1)'],
        'rows' => [[
            'id' => 'row-x',
            'settings' => [],
            'columns' => [['id' => 'col-x', 'width' => 12, 'settings' => [], 'modules' => []]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE)));
$badHtml = LayoutBuilder::render($badHero);
assert(!str_contains($badHtml, 'foo'), 'quoted URL rejected');
assert(!str_contains($badHtml, 'not-a-color'), 'invalid color dropped');
assert(!str_contains($badHtml, 'color:red'), 'raw shadow CSS rejected');
assert(($badHero['sections'][0]['settings']['shape_top'] ?? '') === '', 'evil shape key dropped');
assert(($badHero['sections'][0]['settings']['bg_video_url'] ?? '') === '', 'javascript video URL dropped');
assert(!str_contains($badHtml, 'cms-shape--'), 'no shape markup for rejected key');
assert(!str_contains($badHtml, '<iframe'), 'no iframe for rejected video');

$evilVideo = LayoutBuilder::parse(LayoutBuilder::normalizeJson(json_encode([
    'sections' => [[
        'id' => 'sec-vv',
        'type' => 'regular',
        'settings' => ['bg_video_url' => 'https://evil.example.com/watch?v=dQw4w9WgXcQ'],
        'rows' => [[
            'id' => 'row-vv',
            'settings' => [],
            'columns' => [['id' => 'col-vv', 'width' => 12, 'settings' => [], 'modules' => []]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE)));
assert(($evilVideo['sections'][0]['settings']['bg_video_url'] ?? '') === '', 'unknown video host dropped');
$evilHtml = LayoutBuilder::render($evilVideo);
assert(!str_contains($evilHtml, 'evil.example'), 'unknown host not in HTML');

$badTypeRaw = json_encode([
    'sections' => [[
        'id' => 'sec-ty',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'row-ty',
            'settings' => [],
            'columns' => [[
                'id' => 'col-ty',
                'width' => 12,
                'settings' => [],
                'modules' => [[
                    'id' => 'm-ty',
                    'type' => 'heading',
                    'data' => ['text' => 'Ty', 'level' => 2],
                    'design' => [
                        'font_family' => 'serif);color:red',
                        'letter_spacing' => 'wide;color:red',
                        'text_transform' => 'uppercase;color:red',
                        'position' => 'fixed',
                        'z_index' => '9999',
                        'sticky_offset' => '0);color:red',
                    ],
                    'advanced' => [],
                ]],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$badType = LayoutBuilder::parse(LayoutBuilder::normalizeJson($badTypeRaw));
$badTypeHtml = LayoutBuilder::render($badType);
assert(($badType['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design']['font_family'] ?? 'x') === '', 'evil font family dropped');
assert(($badType['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design']['letter_spacing'] ?? 'x') === '', 'evil letter spacing dropped');
assert(($badType['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design']['text_transform'] ?? 'x') === '', 'evil text transform dropped');
assert(!str_contains($badTypeHtml, 'color:red'), 'typography injection rejected');
assert(!str_contains($badTypeHtml, 'serif)'), 'font family not interpolated');
assert(($badType['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design']['position'] ?? 'x') === '', 'fixed position dropped');
assert(($badType['sections'][0]['rows'][0]['columns'][0]['modules'][0]['design']['z_index'] ?? 'x') === '', 'unlisted z-index dropped');
assert(!str_contains($badTypeHtml, 'position:fixed'), 'fixed not compiled');
assert(!str_contains($badTypeHtml, 'z-index:9999'), 'large z-index not compiled');

$innerRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'sec-ir',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'row-ir',
            'settings' => [],
            'columns' => [[
                'id' => 'col-ir',
                'width' => 12,
                'settings' => [],
                'modules' => [[
                    'id' => 'ir1',
                    'type' => 'inner_row',
                    'data' => [],
                    'design' => [],
                    'advanced' => [],
                    'columns' => [
                        [
                            'id' => 'icol-a',
                            'width' => 6,
                            'settings' => [],
                            'modules' => [[
                                'id' => 'im-h',
                                'type' => 'heading',
                                'data' => ['text' => 'Nested heading', 'level' => 3],
                                'design' => ['text_color' => '#111111'],
                                'advanced' => [],
                            ]],
                        ],
                        [
                            'id' => 'icol-b',
                            'width' => 6,
                            'settings' => [],
                            'modules' => [[
                                'id' => 'ir-nested',
                                'type' => 'inner_row',
                                'data' => [],
                                'design' => [],
                                'advanced' => [],
                                'columns' => [[
                                    'id' => 'too-deep',
                                    'width' => 12,
                                    'settings' => [],
                                    'modules' => [[
                                        'id' => 'dropped-h',
                                        'type' => 'heading',
                                        'data' => ['text' => 'Should drop', 'level' => 2],
                                        'design' => [],
                                        'advanced' => [],
                                    ]],
                                ]],
                            ]],
                        ],
                    ],
                ]],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$innerParsed = LayoutBuilder::parse($innerRaw);
$innerMod = $innerParsed['sections'][0]['rows'][0]['columns'][0]['modules'][0] ?? [];
assert(($innerMod['type'] ?? '') === 'inner_row', 'inner_row kept');
assert(count($innerMod['columns'] ?? []) === 2, 'inner_row has two columns');
assert(($innerMod['columns'][0]['width'] ?? 0) === 6, 'inner column width 6');
assert(($innerMod['columns'][0]['modules'][0]['data']['text'] ?? '') === 'Nested heading', 'nested heading kept');
$deepMods = $innerMod['columns'][1]['modules'] ?? [];
foreach ($deepMods as $dm) {
    assert(($dm['type'] ?? '') !== 'inner_row', 'nested inner_row dropped');
}
$innerHtml = LayoutBuilder::render($innerParsed);
assert(str_contains($innerHtml, 'cms-mod-inner-row'), 'public inner row class');
assert(str_contains($innerHtml, 'col-md-6'), 'inner columns use Bootstrap width');
assert(str_contains($innerHtml, 'Nested heading'), 'nested heading renders');
assert(!str_contains($innerHtml, 'Should drop'), 'deep nested inner_row content dropped');
assert(str_contains($innerHtml, '#111111'), 'nested module design compiles');
$innerPlain = LayoutBuilder::plainText($innerParsed);
assert(str_contains($innerPlain, 'Nested heading'), 'plainText walks inner_row');
assert(!str_contains($innerPlain, 'Should drop'), 'plainText skips dropped nest');

$richRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'secr',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'rowr',
            'settings' => [],
            'columns' => [[
                'id' => 'colr',
                'width' => 12,
                'settings' => [],
                'modules' => [
                    [
                        'id' => 'rtxt',
                        'type' => 'text',
                        'data' => ['text' => '<strong>Hi</strong><script>alert(1)</script>'],
                        'design' => [],
                        'advanced' => [],
                    ],
                    [
                        'id' => 'rlnk',
                        'type' => 'text',
                        'data' => ['text' => '<a href="javascript:alert(1)">click</a>'],
                        'design' => [],
                        'advanced' => [],
                    ],
                    [
                        'id' => 'rpln',
                        'type' => 'text',
                        'data' => ['text' => "a\nb"],
                        'design' => [],
                        'advanced' => [],
                    ],
                ],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$richParsed = LayoutBuilder::parse($richRaw);
$richHtml = LayoutBuilder::render($richParsed);
assert(str_contains($richHtml, '<strong>Hi</strong>'), 'rich keeps strong');
assert(!str_contains($richHtml, '<script'), 'rich drops script tags');
assert(!str_contains($richHtml, 'alert(1)'), 'rich drops script contents and javascript hrefs');
assert(str_contains($richHtml, 'click'), 'javascript link text kept');
assert(!str_contains(strtolower($richHtml), 'javascript:'), 'javascript protocol dropped');
assert(str_contains($richHtml, '<p>a<br>b</p>') || str_contains($richHtml, '<p>a<br />b</p>'), 'plain newlines become p/br');
$richPlain = LayoutBuilder::plainText($richParsed);
assert(str_contains($richPlain, 'Hi'), 'plainText strips rich tags');

assert(isset(LayoutBuilder::moduleTypes()['widget_recent_posts']), 'widget_recent_posts module registered');
assert(LayoutBuilder::moduleTypes()['widget_recent_posts'] === 'Recent posts', 'widget module label from Widget::types');
assert(LayoutBuilder::widgetTypeFromModule('widget_newsletter') === 'newsletter', 'widget type mapped from module');
assert(LayoutBuilder::widgetTypeFromModule('cta') === null, 'layout cta is not a widget module');
assert(($catalog['widget_search']['fields'][0]['name'] ?? '') === 'title', 'search widget has title field');

$widgetRaw = json_encode([
    'version' => 1,
    'sections' => [[
        'id' => 'wsec',
        'type' => 'regular',
        'settings' => [],
        'rows' => [[
            'id' => 'wrow',
            'settings' => [],
            'columns' => [[
                'id' => 'wcol',
                'width' => 12,
                'settings' => [],
                'modules' => [
                    [
                        'id' => 'wm1',
                        'type' => 'widget_recent_posts',
                        'data' => ['title' => 'Latest', 'count' => 3],
                        'design' => [],
                        'advanced' => [],
                    ],
                    [
                        'id' => 'wm2',
                        'type' => 'widget_search',
                        'data' => ['title' => 'Find'],
                        'design' => [],
                        'advanced' => [],
                    ],
                    [
                        'id' => 'wm3',
                        'type' => 'widget_cta',
                        'data' => [
                            'title' => '',
                            'headline' => 'Join',
                            'text' => 'Now',
                            'label' => 'Go',
                            'url' => '/blog',
                        ],
                        'design' => [],
                        'advanced' => [],
                    ],
                    [
                        'id' => 'wm-bad',
                        'type' => 'recent_posts',
                        'data' => ['count' => 5],
                        'design' => [],
                        'advanced' => [],
                    ],
                ],
            ]],
        ]],
    ]],
], JSON_UNESCAPED_UNICODE);
$widgetParsed = LayoutBuilder::parse($widgetRaw);
$widgetMods = $widgetParsed['sections'][0]['rows'][0]['columns'][0]['modules'];
assert(count($widgetMods) === 3, 'unknown recent_posts type dropped; three widget modules kept');
assert(($widgetMods[0]['data']['count'] ?? null) === 3, 'recent posts count sanitized');
assert(($widgetMods[0]['data']['title'] ?? '') === 'Latest', 'widget title kept');
$widgetHtml = LayoutBuilder::render($widgetParsed);
assert(str_contains($widgetHtml, 'public-widget--recent_posts'), 'recent posts widget renders');
assert(str_contains($widgetHtml, 'public-widget--search'), 'search widget renders');
assert(str_contains($widgetHtml, 'public-widget--cta'), 'cta widget module renders');
assert(str_contains($widgetHtml, 'widget-cta-headline'), 'cta headline present');

echo "cms_layout_builder_smoke_test: OK\n";
