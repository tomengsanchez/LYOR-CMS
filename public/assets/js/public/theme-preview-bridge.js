/**
 * Receives Customizer postMessage and applies theme live in the public iframe.
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var THEME_CLASS_RE = /\bpub-(?:theme|font|radius|header|size|leading|btn|footer|shadow|space|link|nav|brand|heading|blog|blogcols|blogratio|img|border|hh|btnsz|sidebar|footalign|prose|logo|motion|focus)-[a-z0-9_-]+\b|\bpub-header-(?:sticky|static)\b|\bpub-admin-link-(?:on|off)\b|\bpub-title-(?:on|off)\b|\bpub-crumbs-(?:on|off)\b|\bpub-nav-(?:upper|normal-case)\b|\bpub-blogsearch-(?:on|off)\b|\bpub-dates-(?:on|off)\b|\bpub-listfeat-(?:on|off)\b|\bpub-blogexcerpt-(?:on|off)\b|\bpub-blogmore-(?:on|off)\b|\bpub-blogcat-(?:on|off)\b|\bpub-blogswitch-(?:on|off)\b/g;
    var WIDTH_CLASS_RE = /\bpublic-main--(?:narrow|normal|wide|full)\b/g;

    function applyColorMode(mode) {
        mode = String(mode || 'system').toLowerCase();
        root.setAttribute('data-default-color-mode', mode);
        var resolved = mode;
        if (mode === 'system') {
            resolved = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
                ? 'dark'
                : 'light';
        }
        if (resolved === 'dark') {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }
    }

    function applyConfig(config) {
        if (!config) {
            return;
        }

        var classes = String(root.className || '').replace(THEME_CLASS_RE, '').replace(/\s+/g, ' ').trim();
        var nextClasses = config.html_classes || [
            'pub-theme-' + (config.preset || 'default'),
            'pub-font-' + (config.font || 'system'),
            'pub-radius-' + (config.radius || 'md'),
            'pub-header-' + (config.header_style || 'solid')
        ].join(' ');
        root.className = (classes + ' ' + nextClasses).replace(/\s+/g, ' ').trim();

        if (config.inline_style) {
            root.setAttribute('style', config.inline_style);
        } else if (config.accent_color) {
            root.style.setProperty('--pub-accent', config.accent_color);
        }

        applyColorMode(config.default_color_mode);

        document.querySelectorAll('.public-nav .btn-admin, .public-footer .public-admin-login').forEach(function (el) {
            el.style.display = config.show_admin_link === false ? 'none' : '';
        });

        var main = document.querySelector('main.public-main');
        if (main) {
            var path = window.location.pathname || '';
            var widthClass = config.content_width_class || 'public-main--full';
            if (path === '/blog' || path.indexOf('/blog/') === 0) {
                widthClass = config.blog_content_width_class || widthClass;
            }
            main.className = String(main.className || '').replace(WIDTH_CLASS_RE, '').replace(/\s+/g, ' ').trim();
            main.className = (main.className + ' ' + widthClass).replace(/\s+/g, ' ').trim();
        }
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }
        var data = event.data;
        if (!data || data.type !== 'cms-theme-preview') {
            return;
        }
        applyConfig(data.config);
    });
})();
