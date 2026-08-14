(function () {
    'use strict';

    var STORAGE_KEY = 'cms_public_theme';
    var root = document.documentElement;

    function applyTheme(theme) {
        if (theme === 'dark') {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }
    }

    function currentTheme() {
        return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
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

    function initTheme() {
        var saved = null;
        try {
            saved = localStorage.getItem(STORAGE_KEY);
        } catch (err) {
            saved = null;
        }
        if (saved === 'dark' || saved === 'light') {
            applyTheme(saved);
            return;
        }
        applyTheme(serverDefault());
    }

    initTheme();

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('publicThemeToggle');
        if (!toggle) {
            return;
        }

        if (root.getAttribute('data-show-color-toggle') === '0') {
            toggle.style.display = 'none';
            return;
        }

        function syncToggle() {
            var dark = currentTheme() === 'dark';
            toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
            toggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
            toggle.textContent = dark ? 'Light' : 'Dark';
        }

        syncToggle();
        toggle.addEventListener('click', function () {
            var next = currentTheme() === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            try {
                localStorage.setItem(STORAGE_KEY, next);
            } catch (err) {
                /* ignore */
            }
            syncToggle();
        });
    });
})();
