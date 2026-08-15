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
$colHtml = LayoutBuilder::render($colParsed);
assert(str_contains($colHtml, 'col-md-5'), 'public col-md-5');
assert(str_contains($colHtml, 'min-height:240px'), 'public min-height');
assert(str_contains($colHtml, 'cms-layout-column--valign-center'), 'valign class');
assert(str_contains($colHtml, 'min-height:200px'), 'row min-height');

echo "cms_layout_builder_smoke_test: OK\n";
