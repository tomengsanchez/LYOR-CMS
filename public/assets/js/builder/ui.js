/**
 * Visual layout builder — shortcuts, context menu, session prefs, layers filter.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        var PREFS_KEY = 'cmsBuilder.prefs';

        function readPrefs() {
            try {
                return JSON.parse(sessionStorage.getItem(PREFS_KEY) || '{}') || {};
            } catch (err) {
                return {};
            }
        }

        function writePrefs() {
            try {
                sessionStorage.setItem(PREFS_KEY, JSON.stringify({
                    zoom: ctx.canvasZoom,
                    device: ctx.currentDevice,
                    layers: !!(ctx.layersEl && !ctx.layersEl.hidden)
                }));
            } catch (err) { /* private mode */ }
        }

        function applyPrefs() {
            var prefs = readPrefs();
            if (prefs.device) {
                ctx.setDevice(prefs.device);
            }
            if (prefs.zoom) {
                ctx.setZoom(Number(prefs.zoom) || 1);
            }
            if (prefs.layers && ctx.layersEl && ctx.layersEl.hidden) {
                ctx.toggleLayers();
            }
        }

        var origSetZoom = ctx.setZoom;
        ctx.setZoom = function (scale) {
            origSetZoom(scale);
            writePrefs();
        };
        var origSetDevice = ctx.setDevice;
        ctx.setDevice = function (device) {
            origSetDevice(device);
            writePrefs();
        };
        var origToggleLayers = ctx.toggleLayers;
        ctx.toggleLayers = function () {
            origToggleLayers();
            writePrefs();
        };

        function shortcutsEl() {
            return document.getElementById('cmsBuilderShortcuts');
        }

        function setShortcutsOpen(open) {
            var box = shortcutsEl();
            if (!box) {
                return;
            }
            box.hidden = !open;
            var btn = document.getElementById('cmsBuilderHelp');
            if (btn) {
                btn.classList.toggle('is-active', !!open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        function toggleShortcuts() {
            var box = shortcutsEl();
            setShortcutsOpen(box ? box.hidden : true);
        }

        function hideContext() {
            var menu = document.getElementById('cmsBuilderContext');
            if (menu) {
                menu.hidden = true;
            }
        }

        function showContext(x, y) {
            var menu = document.getElementById('cmsBuilderContext');
            if (!menu || !ctx.selection.kind) {
                return;
            }
            menu.hidden = false;
            menu.style.left = Math.max(8, x) + 'px';
            menu.style.top = Math.max(8, y) + 'px';
            var pasteBtn = menu.querySelector('[data-ctx="paste"]');
            if (pasteBtn) {
                pasteBtn.disabled = !(ctx.clip && ctx.clip.payload);
            }
            var pasteStyleBtn = menu.querySelector('[data-ctx="paste-style"]');
            if (pasteStyleBtn) {
                pasteStyleBtn.disabled = !(ctx.canPasteStyle && ctx.canPasteStyle());
            }
        }

        ctx.applyPrefs = applyPrefs;
        ctx.writePrefs = writePrefs;
        ctx.toggleShortcuts = toggleShortcuts;
        ctx.setShortcutsOpen = setShortcutsOpen;
        ctx.hideContext = hideContext;
        ctx.showContext = showContext;

        var filter = document.getElementById('cmsBuilderLayersFilter');
        if (filter) {
            filter.addEventListener('input', function () {
                ctx.applyLayersFilter();
            });
        }

        document.getElementById('cmsBuilderHelp') && document.getElementById('cmsBuilderHelp').addEventListener('click', function () {
            toggleShortcuts();
        });
        document.getElementById('cmsBuilderShortcutsClose') && document.getElementById('cmsBuilderShortcutsClose').addEventListener('click', function () {
            setShortcutsOpen(false);
        });

        if (ctx.canvas) {
            ctx.canvas.addEventListener('contextmenu', function (e) {
                var wrap = e.target && e.target.closest ? e.target.closest('[data-kind]') : null;
                if (!wrap) {
                    return;
                }
                e.preventDefault();
                ctx.selectFromEl(wrap);
                showContext(e.clientX, e.clientY);
            });
        }

        document.addEventListener('click', function (e) {
            var menu = document.getElementById('cmsBuilderContext');
            if (menu && !menu.hidden && !(e.target.closest && e.target.closest('#cmsBuilderContext'))) {
                hideContext();
            }
        });

        var ctxMenu = document.getElementById('cmsBuilderContext');
        if (ctxMenu) {
            ctxMenu.addEventListener('click', function (e) {
                var btn = e.target.closest ? e.target.closest('[data-ctx]') : null;
                if (!btn) {
                    return;
                }
                e.preventDefault();
                var act = btn.getAttribute('data-ctx');
                hideContext();
                if (act === 'copy') {
                    ctx.copySelected();
                } else if (act === 'paste') {
                    ctx.pasteClipboard();
                } else if (act === 'copy-style') {
                    ctx.copyStyle();
                } else if (act === 'paste-style') {
                    ctx.pasteStyle();
                } else if (act === 'dup') {
                    ctx.duplicateSelected();
                } else if (act === 'del') {
                    ctx.deleteSelected();
                }
            });
        }
    });
})();
