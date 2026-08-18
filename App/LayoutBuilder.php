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

    /**
     * Allowlisted font stacks (never interpolate user CSS).
     *
     * @var array<string, string>
     */
    public const FONT_FAMILIES = [
        'system' => 'system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif',
        'sans' => 'ui-sans-serif,system-ui,sans-serif',
        'serif' => 'Georgia,"Times New Roman",Times,serif',
        'mono' => 'ui-monospace,SFMono-Regular,Menlo,Consolas,monospace',
    ];

    /**
     * Allowlisted letter-spacing presets (never interpolate user CSS).
     *
     * @var array<string, string>
     */
    public const LETTER_SPACINGS = [
        'tight' => '-0.03em',
        'snug' => '-0.015em',
        'normal' => '0',
        'wide' => '0.05em',
        'wider' => '0.12em',
    ];

    /**
     * Allowlisted z-index values (never interpolate user CSS).
     *
     * @var list<string>
     */
    public const Z_INDEXES = ['1', '2', '5', '10', '20', '50', '100'];

    /**
     * Allowlisted sticky top offsets (never interpolate user CSS).
     *
     * @var array<string, string>
     */
    public const STICKY_OFFSETS = [
        '0' => '0',
        'xs' => '0.5rem',
        'sm' => '1rem',
        'md' => '2rem',
        'lg' => '4.5rem',
    ];

    /** Allowlisted section shape divider keys (never user SVG). */
    public const SHAPE_DIVIDERS = ['wave', 'tilt', 'curve', 'triangle'];

    /**
     * Allowlisted shape heights (never interpolate user CSS).
     *
     * @var array<string, string>
     */
    public const SHAPE_HEIGHTS = [
        'sm' => '32px',
        'md' => '56px',
        'lg' => '88px',
    ];

    /**
     * Hardcoded divider markup (fill via currentColor).
     *
     * @var array<string, string>
     */
    public const SHAPE_SVGS = [
        'wave' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 60C150 150 350-20 600 60 850 140 1050-20 1200 60V120H0Z"/></svg>',
        'tilt' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><polygon fill="currentColor" points="0,80 1200,0 1200,120 0,120"/></svg>',
        'curve' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 120V40Q600 120 1200 40V120Z"/></svg>',
        'triangle' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><polygon fill="currentColor" points="0,120 600,20 1200,120"/></svg>',
    ];
}
