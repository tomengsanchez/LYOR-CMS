<?php
namespace App;

/**
 * Divi-inspired visual layout: Section → Row → Column → Module.
 *
 * Public API stays on this class. Implementation is split under App/LayoutBuilder/.
 */
class LayoutBuilder
{
    use LayoutBuilder\ModuleCatalog;
    use LayoutBuilder\Normalizer;
    use LayoutBuilder\Renderer;
    use LayoutBuilder\Sanitize;
    use LayoutBuilder\Css;

    public const VERSION = 1;

    /** Bootstrap column widths allowed (1–12). */
    private const WIDTHS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    /** Public CSS: tablet and below (editor uses data-device, not viewport). */
    public const CSS_TABLET_MAX = '1023.98px';

    /** Public CSS: mobile and below (matches Bootstrap md). */
    public const CSS_MOBILE_MAX = '767.98px';

    /**
     * Design color tokens stored in layout_json (maps to public CSS variables).
     *
     * @var array<string, string>
     */
    public const COLOR_TOKENS = [
        'accent' => '--pub-accent',
        'accent-soft' => '--pub-accent-soft',
        'text' => '--pub-text',
        'muted' => '--pub-muted',
        'surface' => '--pub-surface',
        'bg' => '--pub-bg',
        'border' => '--pub-border',
    ];

    /**
     * Allowlisted box-shadow presets (never interpolate user CSS).
     *
     * @var array<string, string>
     */
    public const BOX_SHADOWS = [
        'sm' => '0 1px 2px rgba(0,0,0,.08)',
        'md' => '0 4px 12px rgba(0,0,0,.12)',
        'lg' => '0 12px 28px rgba(0,0,0,.16)',
        'none' => 'none',
    ];
}
