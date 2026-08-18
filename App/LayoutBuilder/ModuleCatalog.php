<?php
namespace App\LayoutBuilder;

/**
 * LayoutBuilder implementation: ModuleCatalog.
 */
trait ModuleCatalog
{
    /**
     * Module labels, picker hints, Content-field schemas, Design groups, and editor defaults.
     * Public HTML render and sanitize still live in normalizeModuleData / renderModuleInner.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function moduleCatalog(): array
    {
        $btnStyle = [
            ['v' => 'primary', 'l' => 'Primary'],
            ['v' => 'secondary', 'l' => 'Secondary'],
            ['v' => 'outline', 'l' => 'Outline'],
        ];
        $levels = [];
        for ($n = 1; $n <= 6; $n++) {
            $levels[] = ['v' => $n, 'l' => 'H' . $n];
        }
        $textPack = ['align', 'type', 'color', 'space', 'chrome'];
        $mediaPack = ['align', 'color', 'space', 'chrome'];
        $boxPack = ['color', 'space', 'chrome'];
        $spacePack = ['space'];
        $linePack = ['color', 'space'];

        return [
            'heading' => [
                'label' => 'Heading',
                'hint' => 'Page or section title (H1–H6)',
                'design' => $textPack,
                'defaults' => ['text' => 'Heading', 'level' => 2],
                'fields' => [
                    ['name' => 'text', 'type' => 'text', 'label' => 'Text'],
                    [
                        'name' => 'level',
                        'type' => 'select',
                        'label' => 'Level',
                        'choices' => $levels,
                        'hint' => 'H1 is for the page’s main heading. The admin title is not repeated as an H1 on the public page.',
                    ],
                ],
            ],
            'text' => [
                'label' => 'Text',
                'hint' => 'Paragraphs with bold, italic, lists, and links',
                'design' => $textPack,
                'defaults' => ['text' => 'Add your text here.'],
                'fields' => [
                    [
                        'name' => 'text',
                        'type' => 'rich',
                        'label' => 'Text',
                        'hint' => 'Bold, italic, lists, and links. Custom HTML is for embeds or extra tags.',
                    ],
                ],
            ],
            'image' => [
                'label' => 'Image',
                'hint' => 'Library, upload, or URL — with caption and optional link',
                'design' => $mediaPack,
                'defaults' => ['media_id' => null, 'url' => '', 'alt' => '', 'caption' => '', 'link' => ''],
                'fields' => [
                    ['name' => 'media', 'type' => 'media', 'label' => 'Image'],
                    ['name' => 'alt', 'type' => 'text', 'label' => 'Alt text', 'hint' => 'Describe the image for accessibility and SEO.'],
                    ['name' => 'caption', 'type' => 'textarea', 'label' => 'Caption', 'rows' => 2, 'hint' => 'Shown under the image. Use Image URL below for the photo, not this field.'],
                    ['name' => 'link', 'type' => 'text', 'label' => 'Link URL', 'placeholder' => '/about or https://', 'hint' => 'Optional. Makes the image clickable.'],
                ],
            ],
            'button' => [
                'label' => 'Button',
                'hint' => 'Linked call-to-action button',
                'design' => $textPack,
                'defaults' => ['label' => 'Learn more', 'url' => '#', 'style' => 'primary', 'new_tab' => false],
                'fields' => [
                    ['name' => 'label', 'type' => 'text', 'label' => 'Label'],
                    ['name' => 'url', 'type' => 'text', 'label' => 'URL', 'placeholder' => '/about or https://'],
                    ['name' => 'style', 'type' => 'select', 'label' => 'Style', 'choices' => $btnStyle],
                    ['name' => 'new_tab', 'type' => 'checkbox', 'label' => 'Open in new tab'],
                ],
            ],
            'cta' => [
                'label' => 'Call to Action',
                'hint' => 'Banner with title, text, and button',
                'design' => $textPack,
                'defaults' => ['title' => 'Call to action', 'text' => 'Supporting text.', 'label' => 'Get started', 'url' => '#', 'style' => 'primary', 'new_tab' => false],
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title'],
                    ['name' => 'text', 'type' => 'rich', 'label' => 'Text', 'hint' => 'Bold, italic, lists, and links.'],
                    ['name' => 'label', 'type' => 'text', 'label' => 'Button label'],
                    ['name' => 'url', 'type' => 'text', 'label' => 'Button URL', 'placeholder' => '/contact or https://'],
                    ['name' => 'style', 'type' => 'select', 'label' => 'Button style', 'choices' => $btnStyle],
                    ['name' => 'new_tab', 'type' => 'checkbox', 'label' => 'Open in new tab'],
                ],
            ],
            'spacer' => [
                'label' => 'Spacer',
                'hint' => 'Vertical space between modules',
                'design' => $spacePack,
                'defaults' => ['size' => 'md'],
                'fields' => [
                    [
                        'name' => 'size',
                        'type' => 'select',
                        'label' => 'Size',
                        'choices' => [
                            ['v' => 'sm', 'l' => 'Small'],
                            ['v' => 'md', 'l' => 'Medium'],
                            ['v' => 'lg', 'l' => 'Large'],
                            ['v' => 'xl', 'l' => 'Extra large'],
                        ],
                    ],
                ],
            ],
            'divider' => [
                'label' => 'Divider',
                'hint' => 'Horizontal rule. Color comes from Design → Text color.',
                'design' => $linePack,
                'defaults' => ['style' => 'solid'],
                'fields' => [
                    [
                        'name' => 'style',
                        'type' => 'select',
                        'label' => 'Style',
                        'choices' => [
                            ['v' => 'solid', 'l' => 'Solid'],
                            ['v' => 'dashed', 'l' => 'Dashed'],
                            ['v' => 'dotted', 'l' => 'Dotted'],
                        ],
                    ],
                    ['type' => 'note', 'text' => 'Set Design → Text color to tint the line.'],
                ],
            ],
            'html' => [
                'label' => 'Custom HTML',
                'hint' => 'Raw HTML (scripts and forms are stripped publicly)',
                'design' => $textPack,
                'defaults' => ['html' => '<p>Custom HTML</p>'],
                'fields' => [
                    [
                        'name' => 'html',
                        'type' => 'textarea',
                        'label' => 'HTML',
                        'rows' => 10,
                        'hint' => 'script, iframe, form, and input tags are stripped on the public site.',
                    ],
                    ['type' => 'html_preview', 'source' => 'html'],
                ],
            ],
            'blurb' => [
                'label' => 'Blurb',
                'hint' => 'Feature card: icon or image plus title',
                'design' => $textPack,
                'defaults' => ['title' => 'Feature', 'text' => 'Short description.', 'icon' => '★', 'media_id' => null, 'url' => ''],
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title'],
                    ['name' => 'text', 'type' => 'rich', 'label' => 'Text', 'hint' => 'Bold, italic, lists, and links.'],
                    ['name' => 'icon', 'type' => 'text', 'label' => 'Icon / emoji', 'hint' => 'Used only when no image is selected. Image wins if both are set.'],
                    ['name' => 'media', 'type' => 'media', 'label' => 'Image'],
                    ['name' => 'url', 'type' => 'text', 'label' => 'Link URL', 'placeholder' => '/page or https://', 'hint' => 'Optional. Wraps the title.'],
                ],
            ],
            'carousel' => [
                'label' => 'Carousel',
                'hint' => 'Up to 12 image slides',
                'custom' => 'carousel',
                'design' => $boxPack,
                'defaults' => [
                    'autoplay' => true,
                    'interval_ms' => 5000,
                    'show_arrows' => true,
                    'show_dots' => true,
                    'slides' => [
                        ['media_id' => null, 'url' => '', 'alt' => '', 'caption' => '', 'link' => ''],
                        ['media_id' => null, 'url' => '', 'alt' => '', 'caption' => '', 'link' => ''],
                    ],
                ],
            ],
            'accordion' => [
                'label' => 'Accordion',
                'hint' => 'Expandable FAQ-style items (native details/summary)',
                'custom' => 'accordion',
                'design' => $textPack,
                'defaults' => [
                    'first_open' => true,
                    'items' => [
                        ['title' => 'Question', 'body' => 'Answer.'],
                        ['title' => 'Another question', 'body' => ''],
                    ],
                ],
            ],
            'video' => [
                'label' => 'Video',
                'hint' => 'YouTube, Vimeo, or HTTPS MP4/WebM',
                'design' => $mediaPack,
                'defaults' => ['url' => '', 'caption' => ''],
                'fields' => [
                    [
                        'name' => 'url',
                        'type' => 'text',
                        'label' => 'Video URL',
                        'placeholder' => 'https://www.youtube.com/watch?v=…',
                        'hint' => 'YouTube, Vimeo, or a direct HTTPS .mp4 / .webm / .ogg file. Other hosts are dropped on save.',
                    ],
                    ['name' => 'caption', 'type' => 'textarea', 'label' => 'Caption', 'rows' => 2],
                ],
            ],
            'tabs' => [
                'label' => 'Tabs',
                'hint' => 'Side-by-side panels (CSS radios, no extra JS)',
                'custom' => 'tabs',
                'design' => $textPack,
                'defaults' => [
                    'items' => [
                        ['title' => 'Tab 1', 'body' => 'First panel.'],
                        ['title' => 'Tab 2', 'body' => ''],
                    ],
                ],
            ],
            'icon_list' => [
                'label' => 'Icon list',
                'hint' => 'Short points with emoji or a symbol',
                'custom' => 'icon_list',
                'design' => $textPack,
                'defaults' => [
                    'items' => [
                        ['icon' => '✓', 'text' => 'First point'],
                        ['icon' => '✓', 'text' => 'Second point'],
                    ],
                ],
            ],
            'gallery' => [
                'label' => 'Gallery',
                'hint' => 'Image grid, up to 12 photos',
                'custom' => 'gallery',
                'design' => $mediaPack,
                'defaults' => [
                    'columns' => 3,
                    'items' => [
                        ['media_id' => null, 'url' => '', 'alt' => '', 'caption' => '', 'link' => ''],
                        ['media_id' => null, 'url' => '', 'alt' => '', 'caption' => '', 'link' => ''],
                        ['media_id' => null, 'url' => '', 'alt' => '', 'caption' => '', 'link' => ''],
                    ],
                ],
            ],
            'testimonial' => [
                'label' => 'Testimonials',
                'hint' => 'Quotes with name, role, and optional photo',
                'custom' => 'testimonial',
                'design' => $textPack,
                'defaults' => [
                    'items' => [
                        ['quote' => 'A short quote.', 'name' => 'Name', 'role' => '', 'media_id' => null, 'url' => ''],
                    ],
                ],
            ],
            'inner_row' => [
                'label' => 'Inner row',
                'hint' => 'Split this column into nested columns (one level only)',
                'custom' => 'inner_row',
                'design' => $boxPack,
                'defaults' => [],
            ],
        ];
    }

    /** @return array<string, string> */
    public static function moduleTypes(): array
    {
        $out = [];
        foreach (self::moduleCatalog() as $type => $meta) {
            $out[$type] = (string) ($meta['label'] ?? $type);
        }
        return $out;
    }
}
