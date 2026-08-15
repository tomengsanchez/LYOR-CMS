/**
 * Blog list view switcher (List / Grid / Cards / …).
 * Preference is stored in localStorage; falls back to theme default.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'cms_blog_view';
    var STYLE_PREFIX = 'pub-blog-';
    var KNOWN = ['list', 'grid', 'cards', 'magazine', 'compact', 'stacked'];

    function clearBlogClasses(html) {
        KNOWN.forEach(function (style) {
            html.classList.remove(STYLE_PREFIX + style);
        });
    }

    function applyView(style, defaultView) {
        style = KNOWN.indexOf(style) >= 0 ? style : defaultView;
        if (style === 'stacked') {
            style = 'list';
        }
        var html = document.documentElement;
        clearBlogClasses(html);
        html.classList.add(STYLE_PREFIX + style);

        var list = document.querySelector('[data-blog-list]');
        if (list) {
            list.setAttribute('data-blog-style', style);
        }

        document.querySelectorAll('[data-blog-view]').forEach(function (btn) {
            var active = btn.getAttribute('data-blog-view') === style;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function boot() {
        var switcher = document.querySelector('[data-blog-view-switcher]');
        if (!switcher) {
            return;
        }
        var defaultView = switcher.getAttribute('data-default-view') || 'list';
        var saved = null;
        try {
            saved = window.localStorage.getItem(STORAGE_KEY);
        } catch (err) {
            saved = null;
        }
        applyView(saved || defaultView, defaultView);

        switcher.querySelectorAll('[data-blog-view]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var style = btn.getAttribute('data-blog-view') || defaultView;
                applyView(style, defaultView);
                try {
                    window.localStorage.setItem(STORAGE_KEY, style);
                } catch (err) {
                    /* ignore quota / private mode */
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
