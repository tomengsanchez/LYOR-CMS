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
assert(($catalog['carousel']['custom'] ?? '') === 'carousel', 'carousel stays custom in catalog');
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
        ],
        'rows' => [[
            'id' => 'row-h',
            'settings' => [],
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
assert(str_contains($heroHtml, 'border-color:var(--pub-accent)'), 'border token compiles');
assert(str_contains($heroHtml, 'url("https://example.com/hero.jpg")'), 'background image url');
assert(str_contains($heroHtml, 'linear-gradient(rgba(0,0,0,0.4)'), 'overlay gradient');
assert(!str_contains($heroHtml, 'javascript:'), 'no javascript in compiled CSS');

$badHero = LayoutBuilder::parse(LayoutBuilder::normalizeJson(json_encode([
    'sections' => [[
        'id' => 'sec-x',
        'type' => 'regular',
        'settings' => ['bg_image' => 'https://example.com/x.jpg")foo', 'bg_color' => 'not-a-color', 'box_shadow' => 'foo);color:red'],
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

echo "cms_layout_builder_smoke_test: OK\n";
