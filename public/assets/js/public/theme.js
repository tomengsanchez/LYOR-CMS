/**
 * Public site color mode — driven only by System → General (admin).
 * No visitor toggle or localStorage override.
 */
(function () {
    'use strict';

    var root = document.documentElement;

    function applyTheme(theme) {
        if (theme === 'dark') {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }
    }

    function serverDefault() {
        var mode = (root.getAttribute('data-default-color-mode') || 'system').toLowerCase();
        if (mode === 'dark') {
            return 'dark';
        }
        if (mode === 'light') {
            return 'light';
        }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    applyTheme(serverDefault());
})();
