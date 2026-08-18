/**
 * Visual layout builder — boot (config, events, CmsBuilderApi).
 * Modules: ns, history, model, styles, canvas, layers, dnd, actions, wysiwyg, panel, save, ui.
 */
(function (global) {
    'use strict';

    var cfgEl = document.getElementById('cmsBuilderConfig');
    if (!cfgEl || !global.CmsBuilder) {
        return;
    }

    function normalizeModuleCatalog(raw) {
        var cat = {};
        if (!raw || typeof raw !== 'object') {
            return cat;
        }
        Object.keys(raw).forEach(function (key) {
            var v = raw[key];
            if (typeof v === 'string') {
                cat[key] = { label: v, hint: '', fields: [], defaults: {}, custom: '' };
                return;
            }
            if (v && typeof v === 'object') {
                cat[key] = {
                    label: String(v.label || key),
                    hint: String(v.hint || ''),
                    fields: Array.isArray(v.fields) ? v.fields : [],
                    defaults: v.defaults && typeof v.defaults === 'object' ? v.defaults : {},
                    custom: String(v.custom || ''),
                    design: Array.isArray(v.design) ? v.design : []
                };
            }
        });
        return cat;
    }

    var layout;
    var moduleCatalog;
    var moduleTypes;
    var MODULE_META;
    var mediaList;
    var templatesList;
    try {
        layout = JSON.parse(cfgEl.getAttribute('data-layout') || '{}');
    } catch (e) {
        layout = { version: 1, sections: [] };
    }
    try {
        moduleCatalog = normalizeModuleCatalog(JSON.parse(cfgEl.getAttribute('data-modules') || '{}'));
    } catch (e2) {
        moduleCatalog = {};
    }
    moduleTypes = {};
    MODULE_META = {};
    Object.keys(moduleCatalog).forEach(function (key) {
        moduleTypes[key] = moduleCatalog[key].label || key;
        MODULE_META[key] = { hint: moduleCatalog[key].hint || '' };
    });
    try {
        mediaList = JSON.parse(cfgEl.getAttribute('data-media') || '[]');
    } catch (e3) {
        mediaList = [];
    }
    try {
        templatesList = JSON.parse(cfgEl.getAttribute('data-templates') || '[]');
    } catch (e4) {
        templatesList = [];
    }
    if (!layout.sections) {
        layout.sections = [];
    }
    if (!Array.isArray(templatesList)) {
        templatesList = [];
    }

    var csrf = cfgEl.getAttribute('data-csrf') || '';
    var canUpload = cfgEl.getAttribute('data-can-upload') === '1';
    var uploadUrl = cfgEl.getAttribute('data-upload-url') || '/admin/media/upload-json';

    global.CmsMediaConfig = {
        uploadUrl: uploadUrl,
        csrfToken: csrf,
        canUpload: canUpload
    };

    var ctx = global.CmsBuilder.start({
        global: global,
        canvas: document.getElementById('cmsBuilderCanvas'),
        liveCssEl: document.getElementById('cmsBuilderLiveCss'),
        panel: document.getElementById('cmsBuilderPanel'),
        panelBody: document.getElementById('cmsBuilderPanelBody'),
        panelTitle: document.getElementById('cmsBuilderPanelTitle'),
        statusEl: document.getElementById('cmsBuilderStatus'),
        shell: document.querySelector('.cms-builder-shell'),
        saveUrl: cfgEl.getAttribute('data-save-url') || '',
        templatesUrl: cfgEl.getAttribute('data-templates-url') || '',
        csrf: csrf,
        canUpload: canUpload,
        uploadUrl: uploadUrl,
        canvasWrap: document.getElementById('cmsBuilderCanvasWrap'),
        currentDevice: 'desktop',
        layersEl: document.getElementById('cmsBuilderLayers'),
        layersBody: document.getElementById('cmsBuilderLayersBody'),
        layersFilter: '',
        clip: null,
        styleClip: null,
        canvasZoom: 1,
        saveInFlight: false,
        saveQueued: false,
        autoSaveTimer: null,
        AUTOSAVE_MS: 12000,
        layout: layout,
        moduleCatalog: moduleCatalog,
        moduleTypes: moduleTypes,
        mediaList: mediaList,
        templatesList: templatesList,
        selection: { kind: null, sectionIdx: -1, rowIdx: -1, colIdx: -1, modIdx: -1 },
        activeTab: 'content',
        designState: 'normal',
        modulePickTarget: null,
        dragSource: null,
        dropHint: { el: null, place: '' },
        canvasDndBound: false,
        layersDndBound: false,
        layerDragMoved: false,
        savedSnapshot: JSON.stringify(layout),
        renderTimer: null,
        historyStack: [],
        historyIndex: -1,
        historyLock: false,
        historyPending: false,
        historyCommitTimer: null,
        HISTORY_MAX: 50,
        btnStyleChoices: [
            { v: 'primary', l: 'Primary' },
            { v: 'secondary', l: 'Secondary' },
            { v: 'outline', l: 'Outline' }
        ],
        MODULE_META: MODULE_META
    });

    window.addEventListener('beforeunload', function (e) {
        if (!ctx.isDirty()) {
            return;
        }
        e.preventDefault();
        e.returnValue = '';
    });

    document.getElementById('cmsBuilderAddSection') && document.getElementById('cmsBuilderAddSection').addEventListener('click', function () {
        ctx.layout.sections.push(ctx.emptySection());
        ctx.selection = { kind: 'section', sectionIdx: ctx.layout.sections.length - 1, rowIdx: -1, colIdx: -1, modIdx: -1 };
        ctx.openPanel();
        ctx.noteLayoutChange();
        ctx.render();
    });

    document.getElementById('cmsBuilderSave') && document.getElementById('cmsBuilderSave').addEventListener('click', function () {
        ctx.save(false);
    });
    document.getElementById('cmsBuilderLayersBtn') && document.getElementById('cmsBuilderLayersBtn').addEventListener('click', function () {
        ctx.toggleLayers();
    });
    document.getElementById('cmsBuilderLayersClose') && document.getElementById('cmsBuilderLayersClose').addEventListener('click', function () {
        if (ctx.layersEl && !ctx.layersEl.hidden) {
            ctx.toggleLayers();
        }
    });
    document.getElementById('cmsBuilderCopy') && document.getElementById('cmsBuilderCopy').addEventListener('click', function () {
        ctx.copySelected();
    });
    document.getElementById('cmsBuilderPaste') && document.getElementById('cmsBuilderPaste').addEventListener('click', function () {
        ctx.pasteClipboard();
    });
    document.querySelectorAll('.cms-builder-zoom [data-zoom]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ctx.setZoom(Number(btn.getAttribute('data-zoom')) || 1);
        });
    });
    document.getElementById('cmsBuilderUndo') && document.getElementById('cmsBuilderUndo').addEventListener('click', function () {
        ctx.undo();
    });
    document.getElementById('cmsBuilderRedo') && document.getElementById('cmsBuilderRedo').addEventListener('click', function () {
        ctx.redo();
    });
    document.querySelectorAll('.cms-builder-device-toggle [data-device]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ctx.setDevice(btn.getAttribute('data-device') || 'desktop');
        });
    });
    document.getElementById('cmsBuilderSaveTemplate') && document.getElementById('cmsBuilderSaveTemplate').addEventListener('click', function (e) {
        e.preventDefault();
        ctx.saveAsTemplate();
    });

    document.addEventListener('keydown', function (e) {
        var key = String(e.key || '');
        var mod = e.ctrlKey || e.metaKey;
        if (key === '?' || (mod && key === '/')) {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            ctx.toggleShortcuts();
            return;
        }
        if (mod && key.toLowerCase() === 's') {
            e.preventDefault();
            ctx.save(false);
            return;
        }
        if (mod && key.toLowerCase() === 'z') {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            if (e.shiftKey) {
                ctx.redo();
            } else {
                ctx.undo();
            }
            return;
        }
        if (mod && key.toLowerCase() === 'y') {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            ctx.redo();
            return;
        }
        if (mod && key.toLowerCase() === 'c') {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            ctx.copySelected();
            return;
        }
        if (mod && key.toLowerCase() === 'x') {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            ctx.cutSelected();
            return;
        }
        if (mod && key.toLowerCase() === 'v') {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            ctx.pasteClipboard();
            return;
        }
        if (mod && key.toLowerCase() === 'd') {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            ctx.duplicateSelected();
            return;
        }
        if (e.altKey && (key === 'ArrowUp' || key === 'ArrowDown' || key === 'ArrowLeft' || key === 'ArrowRight')) {
            if (ctx.isTypingTarget(e.target)) {
                return;
            }
            e.preventDefault();
            if (key === 'ArrowUp' || key === 'ArrowLeft') {
                ctx.nudgeSelected(-1);
            } else {
                ctx.nudgeSelected(1);
            }
            return;
        }
        if (key === 'Escape') {
            ctx.hideContext();
            var shortcuts = document.getElementById('cmsBuilderShortcuts');
            if (shortcuts && !shortcuts.hidden) {
                ctx.setShortcutsOpen(false);
                return;
            }
            if (ctx.isTypingTarget(e.target) && e.target && e.target.blur) {
                e.target.blur();
                return;
            }
            if (ctx.modulePickTarget) {
                ctx.modulePickTarget = null;
                ctx.render();
                return;
            }
            if (ctx.selection.kind) {
                ctx.clearSelection();
                ctx.render();
                return;
            }
            if (ctx.layersEl && !ctx.layersEl.hidden) {
                ctx.toggleLayers();
            }
            return;
        }
        if ((key === 'Delete' || key === 'Backspace') && !mod && !ctx.isTypingTarget(e.target) && ctx.selection.kind) {
            e.preventDefault();
            ctx.deleteSelected();
        }
    });

    document.getElementById('cmsBuilderPanelClose') && document.getElementById('cmsBuilderPanelClose').addEventListener('click', function () {
        ctx.clearSelection();
        ctx.render();
    });

    document.querySelectorAll('[data-panel-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            ctx.setPanelTab(tab.getAttribute('data-panel-tab') || 'content');
            ctx.renderPanel();
        });
    });

    if (ctx.panel) {
        ctx.panel.hidden = true;
    }
    ctx.setDevice('desktop');
    ctx.renderTemplatesMenu();
    ctx.updateShell();
    ctx.render();
    ctx.resetHistory();
    if (ctx.applyPrefs) {
        ctx.applyPrefs();
    }

    global.CmsBuilderApi = {
        getLayout: function () {
            return ctx.layout;
        },
        setLayout: function (next) {
            ctx.layout = next && next.sections ? next : { version: 1, sections: [] };
            ctx.clearSelection();
            ctx.render();
            ctx.resetHistory();
        },
        applyReorder: function (src, dest, place) {
            ctx.applyReorder(src, dest, place);
        },
        applyColumnLayout: ctx.applyColumnLayout,
        columnLayouts: ctx.COLUMN_LAYOUTS,
        setDevice: function (device) { ctx.setDevice(device); },
        saveAsTemplate: function () { ctx.saveAsTemplate(); },
        loadTemplate: function (id) { ctx.loadTemplate(id); },
        save: function () { ctx.save(false); },
        render: function () { ctx.render(); },
        refreshLiveCss: function () { ctx.refreshLiveCss(); },
        undo: function () { ctx.undo(); },
        redo: function () { ctx.redo(); },
        isDirty: function () { return ctx.isDirty(); },
        copySelected: function () { return ctx.copySelected(); },
        pasteClipboard: function () { ctx.pasteClipboard(); }
    };
})(window);
