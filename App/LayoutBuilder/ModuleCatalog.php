<?php
namespace App\LayoutBuilder;

/**
 * LayoutBuilder implementation: ModuleCatalog.
 */
trait ModuleCatalog
{
    /**
     * Module labels, picker hints, Content-field schemas, and editor defaults.
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

        return [
            'heading' => [
                'label' => 'Heading',
                'hint' => 'Page or section title (H1–H6)',
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
                'hint' => 'Plain paragraphs. Use Custom HTML for lists or bold.',
                'defaults' => ['text' => 'Add your text here.'],
                'fields' => [
                    [
                        'name' => 'text',
                        'type' => 'textarea',
                        'label' => 'Text',
                        'rows' => 8,
                        'hint' => 'Line breaks become paragraphs. For lists, bold, or embeds, use Custom HTML.',
                    ],
                ],
            ],
            'image' => [
                'label' => 'Image',
                'hint' => 'Library, upload, or URL — with caption and optional link',
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
                'defaults' => ['title' => 'Call to action', 'text' => 'Supporting text.', 'label' => 'Get started', 'url' => '#', 'style' => 'primary', 'new_tab' => false],
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title'],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Text', 'rows' => 3],
                    ['name' => 'label', 'type' => 'text', 'label' => 'Button label'],
                    ['name' => 'url', 'type' => 'text', 'label' => 'Button URL', 'placeholder' => '/contact or https://'],
                    ['name' => 'style', 'type' => 'select', 'label' => 'Button style', 'choices' => $btnStyle],
                    ['name' => 'new_tab', 'type' => 'checkbox', 'label' => 'Open in new tab'],
                ],
            ],
            'spacer' => [
                'label' => 'Spacer',
                'hint' => 'Vertical space between modules',
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
                'defaults' => ['title' => 'Feature', 'text' => 'Short description.', 'icon' => '★', 'media_id' => null, 'url' => ''],
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title'],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Text', 'rows' => 3],
                    ['name' => 'icon', 'type' => 'text', 'label' => 'Icon / emoji', 'hint' => 'Used only when no image is selected. Image wins if both are set.'],
                    ['name' => 'media', 'type' => 'media', 'label' => 'Image'],
                    ['name' => 'url', 'type' => 'text', 'label' => 'Link URL', 'placeholder' => '/page or https://', 'hint' => 'Optional. Wraps the title.'],
                ],
            ],
            'carousel' => [
                'label' => 'Carousel',
                'hint' => 'Up to 12 image slides',
                'custom' => 'carousel',
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
