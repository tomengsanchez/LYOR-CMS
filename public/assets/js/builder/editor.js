/**
 * Divi-style visual layout builder.
 * Config: #cmsBuilderConfig data-* attributes.
 */
(function (global) {
    'use strict';

    var cfgEl = document.getElementById('cmsBuilderConfig');
    if (!cfgEl) {
        return;
    }

    var canvas = document.getElementById('cmsBuilderCanvas');
    var panel = document.getElementById('cmsBuilderPanel');
    var panelBody = document.getElementById('cmsBuilderPanelBody');
    var panelTitle = document.getElementById('cmsBuilderPanelTitle');
    var statusEl = document.getElementById('cmsBuilderStatus');
    var shell = document.querySelector('.cms-builder-shell');

    var saveUrl = cfgEl.getAttribute('data-save-url') || '';
    var templatesUrl = cfgEl.getAttribute('data-templates-url') || '';
    var csrf = cfgEl.getAttribute('data-csrf') || '';
    var canUpload = cfgEl.getAttribute('data-can-upload') === '1';
    var uploadUrl = cfgEl.getAttribute('data-upload-url') || '/admin/media/upload-json';
    var canvasWrap = document.getElementById('cmsBuilderCanvasWrap');
    var currentDevice = 'desktop';

    var layout;
    var moduleTypes;
    var mediaList;
    var templatesList;
    try {
        layout = JSON.parse(cfgEl.getAttribute('data-layout') || '{}');
    } catch (e) {
        layout = { version: 1, sections: [] };
    }
    try {
        moduleTypes = JSON.parse(cfgEl.getAttribute('data-modules') || '{}');
    } catch (e2) {
        moduleTypes = {};
    }
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

    global.CmsMediaConfig = {
        uploadUrl: uploadUrl,
        csrfToken: csrf,
        canUpload: canUpload
    };

    var selection = { kind: null, sectionIdx: -1, rowIdx: -1, colIdx: -1, modIdx: -1 };
    var activeTab = 'content';
    /** When set, Content panel shows module type buttons for this column. */
    var modulePickTarget = null;

    function uid() {
        return 'el_' + Math.random().toString(16).slice(2, 14);
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setStatus(msg, isError) {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = msg || '';
        statusEl.style.color = isError ? '#f5a9a9' : '#9ad29a';
    }

    function mediaById(id) {
        id = Number(id) || 0;
        for (var i = 0; i < mediaList.length; i++) {
            if (Number(mediaList[i].id) === id) {
                return mediaList[i];
            }
        }
        return null;
    }

    function defaultModule(type) {
        var data = {};
        switch (type) {
            case 'heading':
                data = { text: 'Heading', level: 2 };
                break;
            case 'text':
                data = { text: 'Add your text here.' };
                break;
            case 'image':
                data = { media_id: null, url: '', alt: '', caption: '', link: '' };
                break;
            case 'button':
                data = { label: 'Learn more', url: '#', style: 'primary' };
                break;
            case 'cta':
                data = { title: 'Call to action', text: 'Supporting text.', label: 'Get started', url: '#' };
                break;
            case 'spacer':
                data = { size: 'md' };
                break;
            case 'divider':
                data = { style: 'solid' };
                break;
            case 'html':
                data = { html: '<p>Custom HTML</p>' };
                break;
            case 'blurb':
                data = { title: 'Feature', text: 'Short description.', icon: '★', media_id: null, url: '' };
                break;
            case 'carousel':
                data = {
                    autoplay: true,
                    interval_ms: 5000,
                    show_arrows: true,
                    show_dots: true,
                    slides: [
                        { media_id: null, url: '', alt: '', caption: '', link: '' },
                        { media_id: null, url: '', alt: '', caption: '', link: '' }
                    ]
                };
                break;
            default:
                data = {};
        }
        return {
            id: uid(),
            type: type,
            data: data,
            design: {},
            advanced: {}
        };
    }

    /** Equal and common Divi-like column width presets (Bootstrap 12-grid). */
    var COLUMN_LAYOUTS = [
        { id: '1', label: '1 column', widths: [12] },
        { id: '2', label: '2 equal', widths: [6, 6] },
        { id: '3', label: '3 equal', widths: [4, 4, 4] },
        { id: '4', label: '4 equal', widths: [3, 3, 3, 3] },
        { id: '8-4', label: '2/3 + 1/3', widths: [8, 4] },
        { id: '4-8', label: '1/3 + 2/3', widths: [4, 8] },
        { id: '9-3', label: '3/4 + 1/4', widths: [9, 3] },
        { id: '3-9', label: '1/4 + 3/4', widths: [3, 9] },
        { id: '3-6-3', label: '1/4 + 1/2 + 1/4', widths: [3, 6, 3] }
    ];

    function emptyColumn(width) {
        return { id: uid(), width: width || 12, settings: {}, modules: [] };
    }

    function emptyRow() {
        return { id: uid(), settings: {}, columns: [emptyColumn(12)] };
    }

    function layoutIdFromWidths(widths) {
        var key = (widths || []).join('-');
        for (var i = 0; i < COLUMN_LAYOUTS.length; i++) {
            if (COLUMN_LAYOUTS[i].widths.join('-') === key) {
                return COLUMN_LAYOUTS[i].id;
            }
        }
        return '';
    }

    function rowWidths(row) {
        return (row.columns || []).map(function (c) {
            return Number(c.width) || 12;
        });
    }

    /**
     * Resize a row to the given column widths, keeping modules where possible.
     * Extra columns' modules are appended to the last remaining column.
     */
    function applyColumnLayout(row, widths) {
        if (!row || !widths || !widths.length) {
            return;
        }
        var old = row.columns || [];
        var next = [];
        var i;
        for (i = 0; i < widths.length; i++) {
            if (old[i]) {
                next.push({
                    id: old[i].id || uid(),
                    width: widths[i],
                    settings: old[i].settings || {},
                    modules: old[i].modules || []
                });
            } else {
                next.push(emptyColumn(widths[i]));
            }
        }
        if (old.length > widths.length) {
            var spill = [];
            for (i = widths.length; i < old.length; i++) {
                spill = spill.concat(old[i].modules || []);
            }
            if (spill.length) {
                next[next.length - 1].modules = (next[next.length - 1].modules || []).concat(spill);
            }
        }
        row.columns = next;
    }

    function renderColumnLayoutPicker(row, si, ri) {
        var current = layoutIdFromWidths(rowWidths(row));
        var html = '<div class="cms-lb-col-picker" data-si="' + si + '" data-ri="' + ri + '">';
        html += '<label class="form-label">Number of columns</label>';
        html += '<div class="cms-lb-col-picker-grid">';
        COLUMN_LAYOUTS.forEach(function (layout) {
            var active = layout.id === current ? ' is-active' : '';
            html += '<button type="button" class="cms-lb-col-picker-btn' + active + '" data-col-layout="' + esc(layout.id) + '" title="' + esc(layout.label) + '" aria-label="' + esc(layout.label) + '">';
            html += '<span class="cms-lb-col-picker-bars">';
            layout.widths.forEach(function (w) {
                html += '<span class="cms-lb-col-picker-bar" style="flex:' + w + '"></span>';
            });
            html += '</span><span class="cms-lb-col-picker-caption">' + esc(layout.label) + '</span></button>';
        });
        html += '</div>';
        html += '<p class="small text-muted mt-2 mb-0">Changing layout keeps module content when possible. Extra columns merge into the last column.</p>';
        html += '</div>';
        return html;
    }

    function emptySection() {
        return { id: uid(), type: 'regular', settings: {}, rows: [emptyRow()] };
    }

    function designStyle(design) {
        design = design || {};
        var parts = [];
        if (design.text_align) {
            parts.push('text-align:' + design.text_align);
        }
        if (design.text_color) {
            parts.push('color:' + design.text_color);
        }
        if (design.bg_color) {
            parts.push('background-color:' + design.bg_color);
        }
        if (design.padding) {
            parts.push('padding:' + design.padding);
        }
        if (design.margin) {
            parts.push('margin:' + design.margin);
        }
        if (design.font_size) {
            parts.push('font-size:' + design.font_size);
        }
        return parts.join(';');
    }

    function settingsStyle(settings) {
        settings = settings || {};
        var parts = [];
        if (settings.bg_color) {
            parts.push('background-color:' + settings.bg_color);
        }
        if (settings.padding) {
            parts.push('padding:' + settings.padding);
        }
        return parts.join(';');
    }

    function modulePreviewHtml(mod) {
        var d = mod.data || {};
        var style = designStyle(mod.design);
        var wrapOpen = '<div class="cms-layout-module cms-mod-' + esc(mod.type) + '"' + (style ? ' style="' + esc(style) + '"' : '') + '>';
        var wrapClose = '</div>';
        var inner = '';
        switch (mod.type) {
            case 'heading': {
                var lvl = Math.min(6, Math.max(1, Number(d.level) || 2));
                inner = '<h' + lvl + ' class="cms-mod-heading">' + esc(d.text || 'Heading') + '</h' + lvl + '>';
                break;
            }
            case 'text':
                inner = '<div class="cms-mod-text">' + esc(d.text || '').replace(/\n/g, '<br>') + '</div>';
                break;
            case 'image': {
                var m = d.media_id ? mediaById(d.media_id) : null;
                var src = (m && (m.preview || m.url)) || d.url || '';
                if (src) {
                    inner = '<figure class="cms-mod-image"><img src="' + esc(src) + '" alt="' + esc(d.alt || '') + '" class="img-fluid" style="max-height:240px">';
                    if (d.caption) {
                        inner += '<figcaption class="cms-mod-image-caption small text-muted mt-1">' + esc(d.caption) + '</figcaption>';
                    }
                    inner += '</figure>';
                } else {
                    inner = '<div class="text-muted small p-3 border">No image selected</div>';
                }
                break;
            }
            case 'button': {
                var btnClass = 'btn btn-primary';
                if (d.style === 'secondary') {
                    btnClass = 'btn btn-secondary';
                }
                if (d.style === 'outline') {
                    btnClass = 'btn btn-outline-primary';
                }
                inner = '<a href="' + esc(d.url || '#') + '" class="' + btnClass + ' cms-mod-button">' + esc(d.label || 'Button') + '</a>';
                break;
            }
            case 'cta':
                inner = '<div class="cms-mod-cta"><h3 class="cms-mod-cta-title">' + esc(d.title || '') + '</h3>'
                    + '<div class="cms-mod-cta-text">' + esc(d.text || '') + '</div>'
                    + '<a href="' + esc(d.url || '#') + '" class="btn btn-primary">' + esc(d.label || 'Go') + '</a></div>';
                break;
            case 'spacer':
                inner = '<div class="cms-mod-spacer cms-mod-spacer--' + esc(d.size || 'md') + '" aria-hidden="true"></div>';
                break;
            case 'divider':
                inner = '<hr class="cms-mod-divider cms-mod-divider--' + esc(d.style || 'solid') + '">';
                break;
            case 'html':
                inner = '<div class="cms-mod-html text-muted small"><code>Custom HTML</code></div>';
                break;
            case 'blurb':
                inner = '<div class="cms-mod-blurb">'
                    + (d.icon ? '<div class="cms-mod-blurb-icon">' + esc(d.icon) + '</div>' : '')
                    + '<h4 class="cms-mod-blurb-title">' + esc(d.title || '') + '</h4>'
                    + '<div class="cms-mod-blurb-text">' + esc(d.text || '') + '</div></div>';
                break;
            case 'carousel': {
                var slides = Array.isArray(d.slides) ? d.slides : [];
                var thumbs = '';
                var shown = 0;
                slides.forEach(function (slide) {
                    if (shown >= 4) {
                        return;
                    }
                    var sm = slide.media_id ? mediaById(slide.media_id) : null;
                    var ssrc = (sm && (sm.preview || sm.url)) || slide.url || '';
                    if (ssrc) {
                        thumbs += '<img src="' + esc(ssrc) + '" alt="' + esc(slide.alt || '') + '" class="cms-lb-carousel-thumb">';
                        shown += 1;
                    }
                });
                if (thumbs) {
                    inner = '<div class="cms-mod-carousel cms-lb-carousel-preview">' + thumbs
                        + (slides.length > shown ? '<span class="small text-muted">+' + (slides.length - shown) + ' more</span>' : '')
                        + '</div>';
                } else {
                    inner = '<div class="text-muted small p-3 border">Carousel — add slide images</div>';
                }
                break;
            }
            default:
                inner = '<em>' + esc(mod.type) + '</em>';
        }
        return wrapOpen + inner + wrapClose;
    }

    function chrome(label, buttonsHtml) {
        return '<div class="cms-lb-chrome"><span>' + esc(label) + '</span><span class="cms-lb-chrome-actions">' + buttonsHtml + '</span></div>';
    }

    function render() {
        if (!canvas) {
            return;
        }
        if (!layout.sections.length) {
            canvas.innerHTML = '<div class="cms-lb-empty"><p>No sections yet.</p><button type="button" class="btn btn-primary btn-sm" data-action="add-section">+ Add section</button></div>';
            bindCanvas();
            return;
        }

        var html = '<div class="cms-layout">';
        layout.sections.forEach(function (section, si) {
            var sel = selection.kind === 'section' && selection.sectionIdx === si ? ' is-selected' : '';
            var st = settingsStyle(section.settings);
            html += '<section class="cms-lb-section cms-layout-section cms-layout-section--' + esc(section.type || 'regular') + sel + '" data-kind="section" data-si="' + si + '"'
                + (st ? ' style="' + esc(st) + '"' : '') + '>';
            html += chrome('Section', '<button type="button" data-action="sec-up" data-si="' + si + '" title="Move up">↑</button>'
                + '<button type="button" data-action="sec-down" data-si="' + si + '" title="Move down">↓</button>'
                + '<button type="button" data-action="add-row" data-si="' + si + '">+ Row</button>'
                + '<button type="button" data-action="dup-section" data-si="' + si + '">Dup</button>'
                + '<button type="button" data-action="del-section" data-si="' + si + '">Del</button>');
            html += '<div class="cms-layout-section-inner ' + (section.type === 'fullwidth' ? 'cms-layout-section-inner--full' : 'container') + '">';

            (section.rows || []).forEach(function (row, ri) {
                var rsel = selection.kind === 'row' && selection.sectionIdx === si && selection.rowIdx === ri ? ' is-selected' : '';
                html += '<div class="cms-lb-row cms-layout-row row g-3' + rsel + '" data-kind="row" data-si="' + si + '" data-ri="' + ri + '">';
                html += chrome('Row', '<button type="button" data-action="row-up" data-si="' + si + '" data-ri="' + ri + '" title="Move up">↑</button>'
                    + '<button type="button" data-action="row-down" data-si="' + si + '" data-ri="' + ri + '" title="Move down">↓</button>'
                    + '<button type="button" data-action="pick-cols" data-si="' + si + '" data-ri="' + ri + '">Columns</button>'
                    + '<button type="button" data-action="add-col" data-si="' + si + '" data-ri="' + ri + '">+ Col</button>'
                    + '<button type="button" data-action="del-row" data-si="' + si + '" data-ri="' + ri + '">Del</button>');

                (row.columns || []).forEach(function (col, ci) {
                    var w = Number(col.width) || 12;
                    var csel = selection.kind === 'column' && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci ? ' is-selected' : '';
                    var picking = modulePickTarget
                        && modulePickTarget.si === si
                        && modulePickTarget.ri === ri
                        && modulePickTarget.ci === ci;
                    html += '<div class="cms-lb-col cms-layout-column col-md-' + w + csel + (picking ? ' is-picking' : '') + '" data-kind="column" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '">';
                    html += chrome('Col ' + w + '/12', '<button type="button" data-action="add-mod" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '">+ Module</button>'
                        + '<button type="button" data-action="del-col" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '">Del</button>');

                    var mods = col.modules || [];
                    if (!mods.length) {
                        html += '<div class="cms-lb-col-empty">'
                            + '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="add-mod" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '">+ Add module</button>'
                            + '</div>';
                    }

                    mods.forEach(function (mod, mi) {
                        var msel = selection.kind === 'module' && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci && selection.modIdx === mi ? ' is-selected' : '';
                        html += '<div class="cms-lb-mod' + msel + '" data-kind="module" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '">';
                        html += chrome(moduleTypes[mod.type] || mod.type,
                            '<button type="button" data-action="mod-up" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '" title="Move up">↑</button>'
                            + '<button type="button" data-action="mod-down" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '" title="Move down">↓</button>'
                            + '<button type="button" data-action="dup-mod" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '">Dup</button>'
                            + '<button type="button" data-action="del-mod" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '">Del</button>');
                        html += modulePreviewHtml(mod);
                        html += '</div>';
                    });

                    html += '</div>';
                });

                html += '</div>';
            });

            html += '</div></section>';
        });
        html += '</div><div class="cms-lb-add-bar"><button type="button" class="btn btn-outline-primary btn-sm" data-action="add-section">+ Section</button></div>';
        canvas.innerHTML = html;
        bindCanvas();
        updateShell();
    }

    function updateShell() {
        if (!shell) {
            return;
        }
        if (panel && panel.hidden) {
            shell.classList.add('panel-closed');
        } else {
            shell.classList.remove('panel-closed');
        }
    }

    function bindCanvas() {
        canvas.querySelectorAll('[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                handleAction(btn.getAttribute('data-action'), btn);
            });
        });
        canvas.querySelectorAll('[data-kind]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (e.target.closest('[data-action]')) {
                    return;
                }
                e.stopPropagation();
                selectFromEl(el);
            });
        });
    }

    function selectFromEl(el) {
        var kind = el.getAttribute('data-kind');
        if (kind !== 'column') {
            modulePickTarget = null;
        }
        selection = {
            kind: kind,
            sectionIdx: Number(el.getAttribute('data-si')),
            rowIdx: el.hasAttribute('data-ri') ? Number(el.getAttribute('data-ri')) : -1,
            colIdx: el.hasAttribute('data-ci') ? Number(el.getAttribute('data-ci')) : -1,
            modIdx: el.hasAttribute('data-mi') ? Number(el.getAttribute('data-mi')) : -1
        };
        openPanel();
        render();
    }

    function handleAction(action, btn) {
        var si = Number(btn.getAttribute('data-si'));
        var ri = Number(btn.getAttribute('data-ri'));
        var ci = Number(btn.getAttribute('data-ci'));
        var mi = Number(btn.getAttribute('data-mi'));

        if (action === 'add-section') {
            layout.sections.push(emptySection());
            selection = { kind: 'section', sectionIdx: layout.sections.length - 1, rowIdx: -1, colIdx: -1, modIdx: -1 };
            openPanel();
            render();
            return;
        }
        if (action === 'del-section' && layout.sections[si]) {
            layout.sections.splice(si, 1);
            clearSelection();
            render();
            return;
        }
        if (action === 'dup-section' && layout.sections[si]) {
            var copy = JSON.parse(JSON.stringify(layout.sections[si]));
            copy.id = uid();
            (copy.rows || []).forEach(function (r) {
                r.id = uid();
                (r.columns || []).forEach(function (c) {
                    c.id = uid();
                    (c.modules || []).forEach(function (m) {
                        m.id = uid();
                    });
                });
            });
            layout.sections.splice(si + 1, 0, copy);
            render();
            return;
        }
        if (action === 'add-row' && layout.sections[si]) {
            layout.sections[si].rows = layout.sections[si].rows || [];
            layout.sections[si].rows.push(emptyRow());
            selection = { kind: 'row', sectionIdx: si, rowIdx: layout.sections[si].rows.length - 1, colIdx: -1, modIdx: -1 };
            openPanel();
            render();
            return;
        }
        if (action === 'del-row' && layout.sections[si] && layout.sections[si].rows) {
            layout.sections[si].rows.splice(ri, 1);
            if (!layout.sections[si].rows.length) {
                layout.sections[si].rows.push(emptyRow());
            }
            clearSelection();
            render();
            return;
        }
        if (action === 'pick-cols' && layout.sections[si] && layout.sections[si].rows[ri]) {
            selection = { kind: 'row', sectionIdx: si, rowIdx: ri, colIdx: -1, modIdx: -1 };
            activeTab = 'content';
            document.querySelectorAll('[data-panel-tab]').forEach(function (t) {
                t.classList.toggle('active', t.getAttribute('data-panel-tab') === 'content');
            });
            openPanel();
            render();
            return;
        }
        if (action === 'add-col' && layout.sections[si] && layout.sections[si].rows[ri]) {
            var cols = layout.sections[si].rows[ri].columns || [];
            var n = cols.length + 1;
            if (n > 4) {
                setStatus('Max 4 equal columns — use Columns picker for custom layouts', true);
                return;
            }
            var equal = n === 2 ? [6, 6] : (n === 3 ? [4, 4, 4] : (n === 4 ? [3, 3, 3, 3] : [12]));
            applyColumnLayout(layout.sections[si].rows[ri], equal);
            selection = { kind: 'column', sectionIdx: si, rowIdx: ri, colIdx: equal.length - 1, modIdx: -1 };
            openPanel();
            render();
            return;
        }
        if (action === 'del-col' && layout.sections[si] && layout.sections[si].rows[ri]) {
            var cols2 = layout.sections[si].rows[ri].columns || [];
            if (cols2.length <= 1) {
                cols2[0].modules = [];
            } else {
                cols2.splice(ci, 1);
                var nw = cols2.length === 2 ? 6 : (cols2.length === 3 ? 4 : (cols2.length === 4 ? 3 : 12));
                cols2.forEach(function (c) {
                    c.width = nw;
                });
            }
            layout.sections[si].rows[ri].columns = cols2;
            clearSelection();
            render();
            return;
        }
        if (action === 'add-mod' && layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci]) {
            modulePickTarget = { si: si, ri: ri, ci: ci };
            selection = { kind: 'column', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: -1 };
            activeTab = 'content';
            document.querySelectorAll('[data-panel-tab]').forEach(function (t) {
                t.classList.toggle('active', t.getAttribute('data-panel-tab') === 'content');
            });
            openPanel();
            render();
            return;
        }
        if (action === 'del-mod') {
            var col3 = layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci];
            if (col3 && col3.modules) {
                col3.modules.splice(mi, 1);
            }
            modulePickTarget = null;
            clearSelection();
            render();
            return;
        }
        if (action === 'dup-mod') {
            var colDup = layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci];
            if (colDup && colDup.modules && colDup.modules[mi]) {
                var copyMod = JSON.parse(JSON.stringify(colDup.modules[mi]));
                copyMod.id = uid();
                colDup.modules.splice(mi + 1, 0, copyMod);
                selection = { kind: 'module', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: mi + 1 };
                openPanel();
                render();
            }
            return;
        }
        if (action === 'mod-up' || action === 'mod-down') {
            var colMove = layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci];
            if (!colMove || !colMove.modules) {
                return;
            }
            var to = action === 'mod-up' ? mi - 1 : mi + 1;
            if (to < 0 || to >= colMove.modules.length) {
                return;
            }
            var tmp = colMove.modules[mi];
            colMove.modules[mi] = colMove.modules[to];
            colMove.modules[to] = tmp;
            selection = { kind: 'module', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: to };
            openPanel();
            render();
            return;
        }
        if (action === 'sec-up' || action === 'sec-down') {
            var toSec = action === 'sec-up' ? si - 1 : si + 1;
            if (toSec < 0 || toSec >= layout.sections.length) {
                return;
            }
            var tmpSec = layout.sections[si];
            layout.sections[si] = layout.sections[toSec];
            layout.sections[toSec] = tmpSec;
            selection = { kind: 'section', sectionIdx: toSec, rowIdx: -1, colIdx: -1, modIdx: -1 };
            openPanel();
            render();
            return;
        }
        if (action === 'row-up' || action === 'row-down') {
            var rows = layout.sections[si] && layout.sections[si].rows;
            if (!rows) {
                return;
            }
            var toRow = action === 'row-up' ? ri - 1 : ri + 1;
            if (toRow < 0 || toRow >= rows.length) {
                return;
            }
            var tmpRow = rows[ri];
            rows[ri] = rows[toRow];
            rows[toRow] = tmpRow;
            selection = { kind: 'row', sectionIdx: si, rowIdx: toRow, colIdx: -1, modIdx: -1 };
            openPanel();
            render();
        }
    }

    function insertModule(type, si, ri, ci) {
        if (!moduleTypes[type]) {
            setStatus('Unknown module type', true);
            return;
        }
        var col = layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci];
        if (!col) {
            return;
        }
        col.modules = col.modules || [];
        col.modules.push(defaultModule(type));
        modulePickTarget = null;
        selection = { kind: 'module', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: col.modules.length - 1 };
        activeTab = 'content';
        document.querySelectorAll('[data-panel-tab]').forEach(function (t) {
            t.classList.toggle('active', t.getAttribute('data-panel-tab') === 'content');
        });
        openPanel();
        render();
        setStatus('Added ' + (moduleTypes[type] || type));
    }

    function renderModuleTypePicker(si, ri, ci) {
        var html = '<div class="cms-lb-mod-picker" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '">';
        html += '<label class="form-label">Add module</label>';
        html += '<div class="cms-lb-mod-picker-grid">';
        Object.keys(moduleTypes).forEach(function (key) {
            html += '<button type="button" class="cms-lb-mod-picker-btn" data-insert-mod="' + esc(key) + '">'
                + esc(moduleTypes[key]) + '</button>';
        });
        html += '</div></div>';
        return html;
    }

    function clearSelection() {
        selection = { kind: null, sectionIdx: -1, rowIdx: -1, colIdx: -1, modIdx: -1 };
        modulePickTarget = null;
        if (panel) {
            panel.hidden = true;
        }
        updateShell();
    }

    function openPanel() {
        if (!panel) {
            return;
        }
        panel.hidden = false;
        updateShell();
        renderPanel();
    }

    function getSelectedNode() {
        var s = layout.sections[selection.sectionIdx];
        if (!s) {
            return null;
        }
        if (selection.kind === 'section') {
            return s;
        }
        var r = s.rows && s.rows[selection.rowIdx];
        if (selection.kind === 'row') {
            return r || null;
        }
        var c = r && r.columns && r.columns[selection.colIdx];
        if (selection.kind === 'column') {
            return c || null;
        }
        if (selection.kind === 'module') {
            return c && c.modules && c.modules[selection.modIdx] ? c.modules[selection.modIdx] : null;
        }
        return null;
    }

    function field(label, name, value, type, opts) {
        type = type || 'text';
        var id = 'bf_' + name;
        var html = '<label class="form-label" for="' + id + '">' + esc(label) + '</label>';
        if (type === 'textarea') {
            html += '<textarea class="form-control form-control-sm" id="' + id + '" data-field="' + esc(name) + '" rows="4">' + esc(value || '') + '</textarea>';
        } else if (type === 'select') {
            html += '<select class="form-select form-select-sm" id="' + id + '" data-field="' + esc(name) + '">';
            (opts || []).forEach(function (o) {
                html += '<option value="' + esc(o.v) + '"' + (String(o.v) === String(value) ? ' selected' : '') + '>' + esc(o.l) + '</option>';
            });
            html += '</select>';
        } else if (type === 'checkbox') {
            html += '<div class="form-check"><input type="checkbox" class="form-check-input" id="' + id + '" data-field="' + esc(name) + '"' + (value ? ' checked' : '') + '>'
                + '<label class="form-check-label" for="' + id + '">' + esc(label) + '</label></div>';
            return html;
        } else {
            html += '<input type="' + esc(type) + '" class="form-control form-control-sm" id="' + id + '" data-field="' + esc(name) + '" value="' + esc(value || '') + '">';
        }
        return html;
    }

    function renderPanel() {
        var node = getSelectedNode();
        if (!node || !panelBody) {
            if (panel) {
                panel.hidden = true;
            }
            updateShell();
            return;
        }

        var title = selection.kind === 'module'
            ? (moduleTypes[node.type] || node.type)
            : (selection.kind.charAt(0).toUpperCase() + selection.kind.slice(1));
        if (modulePickTarget && selection.kind === 'column') {
            title = 'Add module';
        }
        if (panelTitle) {
            panelTitle.textContent = title;
        }

        var html = '';
        if (activeTab === 'content') {
            if (selection.kind === 'module') {
                html += renderModuleContent(node);
            } else if (selection.kind === 'section') {
                html += field('Section type', 'type', node.type || 'regular', 'select', [
                    { v: 'regular', l: 'Regular (contained)' },
                    { v: 'fullwidth', l: 'Full width' }
                ]);
                html += field('Background color', 'bg_color', (node.settings && node.settings.bg_color) || '', 'text');
                html += field('Padding (CSS)', 'padding', (node.settings && node.settings.padding) || '', 'text');
            } else if (selection.kind === 'column') {
                var pick = modulePickTarget || {
                    si: selection.sectionIdx,
                    ri: selection.rowIdx,
                    ci: selection.colIdx
                };
                html += renderModuleTypePicker(pick.si, pick.ri, pick.ci);
                html += '<hr class="my-3">';
                html += field('Width (Bootstrap cols)', 'width', node.width || 12, 'select', [
                    { v: 12, l: '12 (full)' },
                    { v: 9, l: '9' },
                    { v: 8, l: '8' },
                    { v: 6, l: '6 (half)' },
                    { v: 4, l: '4 (third)' },
                    { v: 3, l: '3 (quarter)' }
                ]);
                html += '<p class="small text-muted mt-2 mb-0">Or select the parent <strong>Row</strong> and use the column layout picker.</p>';
            } else if (selection.kind === 'row') {
                html += renderColumnLayoutPicker(node, selection.sectionIdx, selection.rowIdx);
            } else {
                html += '<p class="small text-muted mb-0">Use Design / Advanced for styling. Add modules from the column chrome.</p>';
            }
        } else if (activeTab === 'design') {
            if (selection.kind === 'module') {
                var des = node.design || {};
                html += field('Text align', 'text_align', des.text_align || '', 'select', [
                    { v: '', l: 'Default' },
                    { v: 'left', l: 'Left' },
                    { v: 'center', l: 'Center' },
                    { v: 'right', l: 'Right' }
                ]);
                html += field('Text color', 'text_color', des.text_color || '', 'text');
                html += field('Background', 'bg_color', des.bg_color || '', 'text');
                html += field('Padding', 'padding', des.padding || '', 'text');
                html += field('Margin', 'margin', des.margin || '', 'text');
                html += field('Font size', 'font_size', des.font_size || '', 'text');
            } else {
                var set = node.settings || {};
                html += field('Background', 'bg_color', set.bg_color || '', 'text');
                html += field('Padding', 'padding', set.padding || '', 'text');
            }
        } else {
            if (selection.kind === 'module') {
                var adv = node.advanced || {};
                html += field('CSS class', 'css_class', adv.css_class || '', 'text');
                html += field('Hide on mobile', 'hide_mobile', !!adv.hide_mobile, 'checkbox');
                html += field('Hide on desktop', 'hide_desktop', !!adv.hide_desktop, 'checkbox');
            } else {
                var set2 = node.settings || {};
                html += field('CSS class', 'css_class', set2.css_class || '', 'text');
            }
        }

        panelBody.innerHTML = html;
        bindPanelFields();
    }

    function renderModuleContent(mod) {
        var d = mod.data || {};
        var html = '';
        switch (mod.type) {
            case 'heading':
                html += field('Text', 'text', d.text, 'text');
                html += field('Level', 'level', d.level || 2, 'select', [1, 2, 3, 4, 5, 6].map(function (n) {
                    return { v: n, l: 'H' + n };
                }));
                break;
            case 'text':
                html += field('Text', 'text', d.text, 'textarea');
                break;
            case 'image':
                html += mediaField(d);
                html += field('Alt text', 'alt', d.alt, 'text');
                html += field('Caption (can include image URL)', 'caption', d.caption, 'textarea');
                html += field('Link URL', 'link', d.link, 'text');
                break;
            case 'button':
                html += field('Label', 'label', d.label, 'text');
                html += field('URL', 'url', d.url, 'text');
                html += field('Style', 'style', d.style || 'primary', 'select', [
                    { v: 'primary', l: 'Primary' },
                    { v: 'secondary', l: 'Secondary' },
                    { v: 'outline', l: 'Outline' }
                ]);
                break;
            case 'cta':
                html += field('Title', 'title', d.title, 'text');
                html += field('Text', 'text', d.text, 'textarea');
                html += field('Button label', 'label', d.label, 'text');
                html += field('Button URL', 'url', d.url, 'text');
                break;
            case 'spacer':
                html += field('Size', 'size', d.size || 'md', 'select', [
                    { v: 'sm', l: 'Small' },
                    { v: 'md', l: 'Medium' },
                    { v: 'lg', l: 'Large' },
                    { v: 'xl', l: 'Extra large' }
                ]);
                break;
            case 'divider':
                html += field('Style', 'style', d.style || 'solid', 'select', [
                    { v: 'solid', l: 'Solid' },
                    { v: 'dashed', l: 'Dashed' },
                    { v: 'dotted', l: 'Dotted' }
                ]);
                break;
            case 'html':
                html += field('HTML', 'html', d.html, 'textarea');
                break;
            case 'blurb':
                html += field('Title', 'title', d.title, 'text');
                html += field('Text', 'text', d.text, 'textarea');
                html += field('Icon / emoji', 'icon', d.icon, 'text');
                html += mediaField(d);
                html += field('Link URL', 'url', d.url, 'text');
                break;
            case 'carousel':
                html += carouselFields(d);
                break;
            default:
                html += '<p class="small text-muted">No fields.</p>';
        }
        return html;
    }

    function mediaField(d) {
        var opts = [{ v: '', l: '— Select media —' }];
        mediaList.forEach(function (m) {
            opts.push({ v: String(m.id), l: (m.name || m.original_name || ('#' + m.id)) });
        });
        var html = field('Media', 'media_id', d.media_id ? String(d.media_id) : '', 'select', opts);
        html += field('Or image URL', 'url', d.url || '', 'text');
        if (canUpload && global.CmsMediaPicker) {
            html += '<div class="mt-2"><input type="file" accept="image/*" class="form-control form-control-sm" data-upload-file>'
                + '<button type="button" class="btn btn-outline-secondary btn-sm mt-1" data-upload-btn>Upload image</button>'
                + '<span class="small text-muted ms-1" data-upload-status></span></div>';
        }
        return html;
    }

    function emptyCarouselSlide() {
        return { media_id: null, url: '', alt: '', caption: '', link: '' };
    }

    function ensureCarouselSlides(d) {
        if (!Array.isArray(d.slides) || !d.slides.length) {
            d.slides = [emptyCarouselSlide()];
        }
        return d.slides;
    }

    function carouselFields(d) {
        var slides = ensureCarouselSlides(d);
        var html = '';
        html += field('Autoplay', 'autoplay', !!d.autoplay, 'checkbox');
        html += field('Interval (ms)', 'interval_ms', d.interval_ms || 5000, 'number');
        html += field('Show arrows', 'show_arrows', d.show_arrows !== false, 'checkbox');
        html += field('Show dots', 'show_dots', d.show_dots !== false, 'checkbox');
        html += '<hr class="my-2"><div class="d-flex justify-content-between align-items-center mb-2">'
            + '<strong class="small">Slides</strong>'
            + '<button type="button" class="btn btn-outline-primary btn-sm" data-carousel-add-slide>+ Add slide</button></div>';

        var mediaOpts = [{ v: '', l: '— Select media —' }];
        mediaList.forEach(function (m) {
            mediaOpts.push({ v: String(m.id), l: (m.name || m.original_name || ('#' + m.id)) });
        });

        slides.forEach(function (slide, idx) {
            html += '<div class="border rounded p-2 mb-2 cms-lb-carousel-slide-edit" data-slide-wrap="' + idx + '">';
            html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                + '<span class="small fw-semibold">Slide ' + (idx + 1) + '</span><span>'
                + '<button type="button" class="btn btn-outline-secondary btn-sm" data-carousel-slide-up="' + idx + '" title="Move up">↑</button> '
                + '<button type="button" class="btn btn-outline-secondary btn-sm" data-carousel-slide-down="' + idx + '" title="Move down">↓</button> '
                + '<button type="button" class="btn btn-outline-danger btn-sm" data-carousel-del-slide="' + idx + '" title="Remove">×</button>'
                + '</span></div>';
            html += slideField(idx, 'Media', 'media_id', slide.media_id ? String(slide.media_id) : '', 'select', mediaOpts);
            html += slideField(idx, 'Or image URL', 'url', slide.url || '', 'text');
            html += slideField(idx, 'Alt text', 'alt', slide.alt || '', 'text');
            html += slideField(idx, 'Caption', 'caption', slide.caption || '', 'text');
            html += slideField(idx, 'Link URL', 'link', slide.link || '', 'text');
            if (canUpload && global.CmsMediaPicker) {
                html += '<div class="mt-1"><input type="file" accept="image/*" class="form-control form-control-sm" data-upload-file data-slide-idx="' + idx + '">'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm mt-1" data-upload-btn data-slide-idx="' + idx + '">Upload image</button>'
                    + '<span class="small text-muted ms-1" data-upload-status data-slide-idx="' + idx + '"></span></div>';
            }
            html += '</div>';
        });
        return html;
    }

    function slideField(slideIdx, label, name, value, type, opts) {
        type = type || 'text';
        var id = 'bf_slide_' + slideIdx + '_' + name;
        var html = '<label class="form-label" for="' + id + '">' + esc(label) + '</label>';
        if (type === 'select') {
            html += '<select class="form-select form-select-sm" id="' + id + '" data-slide-idx="' + slideIdx + '" data-slide-field="' + esc(name) + '">';
            (opts || []).forEach(function (o) {
                html += '<option value="' + esc(o.v) + '"' + (String(o.v) === String(value) ? ' selected' : '') + '>' + esc(o.l) + '</option>';
            });
            html += '</select>';
        } else {
            html += '<input type="' + esc(type) + '" class="form-control form-control-sm" id="' + id + '" data-slide-idx="' + slideIdx
                + '" data-slide-field="' + esc(name) + '" value="' + esc(value || '') + '">';
        }
        return html;
    }

    function bindPanelFields() {
        panelBody.querySelectorAll('[data-field]').forEach(function (el) {
            var evt = el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function () {
                applyField(el.getAttribute('data-field'), el);
            });
        });
        panelBody.querySelectorAll('[data-slide-field]').forEach(function (el) {
            var evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function () {
                applySlideField(Number(el.getAttribute('data-slide-idx')), el.getAttribute('data-slide-field'), el);
            });
        });
        panelBody.querySelectorAll('[data-col-layout]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var id = btn.getAttribute('data-col-layout');
                var preset = null;
                for (var i = 0; i < COLUMN_LAYOUTS.length; i++) {
                    if (COLUMN_LAYOUTS[i].id === id) {
                        preset = COLUMN_LAYOUTS[i];
                        break;
                    }
                }
                if (!preset || selection.kind !== 'row') {
                    return;
                }
                var row = getSelectedNode();
                if (!row) {
                    return;
                }
                applyColumnLayout(row, preset.widths.slice());
                setStatus('Columns: ' + preset.label);
                render();
                renderPanel();
            });
        });
        panelBody.querySelectorAll('[data-insert-mod]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var type = btn.getAttribute('data-insert-mod');
                var wrap = btn.closest('.cms-lb-mod-picker');
                if (!wrap) {
                    return;
                }
                insertModule(
                    type,
                    Number(wrap.getAttribute('data-si')),
                    Number(wrap.getAttribute('data-ri')),
                    Number(wrap.getAttribute('data-ci'))
                );
            });
        });

        var addSlideBtn = panelBody.querySelector('[data-carousel-add-slide]');
        if (addSlideBtn) {
            addSlideBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var node = getSelectedNode();
                if (!node || node.type !== 'carousel') {
                    return;
                }
                node.data = node.data || {};
                ensureCarouselSlides(node.data);
                if (node.data.slides.length >= 12) {
                    setStatus('Maximum 12 slides', true);
                    return;
                }
                node.data.slides.push(emptyCarouselSlide());
                render();
                renderPanel();
            });
        }
        panelBody.querySelectorAll('[data-carousel-del-slide]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var node = getSelectedNode();
                if (!node || node.type !== 'carousel') {
                    return;
                }
                var idx = Number(btn.getAttribute('data-carousel-del-slide'));
                ensureCarouselSlides(node.data);
                if (node.data.slides.length <= 1) {
                    node.data.slides[0] = emptyCarouselSlide();
                } else {
                    node.data.slides.splice(idx, 1);
                }
                render();
                renderPanel();
            });
        });
        panelBody.querySelectorAll('[data-carousel-slide-up]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var node = getSelectedNode();
                if (!node || node.type !== 'carousel') {
                    return;
                }
                var idx = Number(btn.getAttribute('data-carousel-slide-up'));
                ensureCarouselSlides(node.data);
                if (idx <= 0) {
                    return;
                }
                var tmp = node.data.slides[idx - 1];
                node.data.slides[idx - 1] = node.data.slides[idx];
                node.data.slides[idx] = tmp;
                render();
                renderPanel();
            });
        });
        panelBody.querySelectorAll('[data-carousel-slide-down]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var node = getSelectedNode();
                if (!node || node.type !== 'carousel') {
                    return;
                }
                var idx = Number(btn.getAttribute('data-carousel-slide-down'));
                ensureCarouselSlides(node.data);
                if (idx >= node.data.slides.length - 1) {
                    return;
                }
                var tmp = node.data.slides[idx + 1];
                node.data.slides[idx + 1] = node.data.slides[idx];
                node.data.slides[idx] = tmp;
                render();
                renderPanel();
            });
        });

        panelBody.querySelectorAll('[data-upload-btn]').forEach(function (uploadBtn) {
            if (!global.CmsMediaPicker) {
                return;
            }
            uploadBtn.addEventListener('click', function () {
                var slideIdxAttr = uploadBtn.getAttribute('data-slide-idx');
                var slideIdx = slideIdxAttr !== null && slideIdxAttr !== '' ? Number(slideIdxAttr) : null;
                var fileInput = slideIdx !== null
                    ? panelBody.querySelector('[data-upload-file][data-slide-idx="' + slideIdx + '"]')
                    : panelBody.querySelector('[data-upload-file]:not([data-slide-idx])');
                if (!fileInput) {
                    fileInput = panelBody.querySelector('[data-upload-file]');
                }
                var f = fileInput && fileInput.files && fileInput.files[0];
                if (!f) {
                    setStatus('Choose a file first', true);
                    return;
                }
                var st = slideIdx !== null
                    ? panelBody.querySelector('[data-upload-status][data-slide-idx="' + slideIdx + '"]')
                    : panelBody.querySelector('[data-upload-status]:not([data-slide-idx])');
                if (st) {
                    st.textContent = 'Uploading…';
                }
                global.CmsMediaPicker.uploadFile(f).then(function (item) {
                    mediaList.unshift(item);
                    var node = getSelectedNode();
                    if (node && node.data) {
                        if (slideIdx !== null && node.type === 'carousel') {
                            ensureCarouselSlides(node.data);
                            if (node.data.slides[slideIdx]) {
                                node.data.slides[slideIdx].media_id = item.id;
                                node.data.slides[slideIdx].url = item.share_url || item.url || '';
                            }
                        } else {
                            node.data.media_id = item.id;
                            node.data.url = item.share_url || item.url || '';
                        }
                    }
                    if (st) {
                        st.textContent = 'Done';
                    }
                    render();
                    renderPanel();
                }).catch(function (err) {
                    if (st) {
                        st.textContent = '';
                    }
                    setStatus(err.message || 'Upload failed', true);
                });
            });
        });
    }

    function applySlideField(slideIdx, name, el) {
        var node = getSelectedNode();
        if (!node || selection.kind !== 'module' || node.type !== 'carousel') {
            return;
        }
        node.data = node.data || {};
        ensureCarouselSlides(node.data);
        if (!node.data.slides[slideIdx]) {
            return;
        }
        var val = el.value;
        if (name === 'media_id') {
            node.data.slides[slideIdx].media_id = val ? Number(val) : null;
            var m = mediaById(node.data.slides[slideIdx].media_id);
            if (m) {
                node.data.slides[slideIdx].url = m.share_url || m.url || node.data.slides[slideIdx].url || '';
            }
        } else {
            node.data.slides[slideIdx][name] = val;
        }
        render();
    }

    function applyField(name, el) {
        var node = getSelectedNode();
        if (!node) {
            return;
        }
        var val = el.type === 'checkbox' ? el.checked : el.value;

        if (selection.kind === 'module') {
            if (activeTab === 'content') {
                node.data = node.data || {};
                if (name === 'media_id') {
                    node.data.media_id = val ? Number(val) : null;
                    var m = mediaById(node.data.media_id);
                    if (m) {
                        node.data.url = m.share_url || m.url || node.data.url || '';
                    }
                } else if (name === 'level') {
                    node.data.level = Number(val) || 2;
                } else if (name === 'interval_ms') {
                    node.data.interval_ms = Number(val) || 5000;
                } else if (name === 'autoplay' || name === 'show_arrows' || name === 'show_dots') {
                    node.data[name] = !!val;
                } else {
                    node.data[name] = val;
                }
            } else if (activeTab === 'design') {
                node.design = node.design || {};
                node.design[name] = val;
            } else {
                node.advanced = node.advanced || {};
                node.advanced[name] = val;
            }
        } else if (selection.kind === 'section' && activeTab === 'content' && name === 'type') {
            node.type = val;
        } else if (selection.kind === 'column' && activeTab === 'content' && name === 'width') {
            node.width = Number(val) || 12;
        } else {
            node.settings = node.settings || {};
            node.settings[name] = val;
        }
        render();
    }

    function save() {
        setStatus('Saving…');
        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('layout_json', JSON.stringify(layout));
        fetch(saveUrl, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json || !json.success) {
                    throw new Error((json && json.error && json.error.message) || 'Save failed');
                }
                if (json.data && json.data.layout) {
                    layout = json.data.layout;
                }
                setStatus('Saved');
                render();
            });
        }).catch(function (err) {
            setStatus(err.message || 'Save failed', true);
        });
    }

    function setDevice(device) {
        currentDevice = device === 'tablet' || device === 'mobile' ? device : 'desktop';
        if (canvasWrap) {
            canvasWrap.setAttribute('data-device', currentDevice);
        }
        document.querySelectorAll('.cms-builder-device-toggle [data-device]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-device') === currentDevice);
        });
    }

    function renderTemplatesMenu() {
        var menu = document.getElementById('cmsBuilderTemplatesMenu');
        var emptyEl = document.getElementById('cmsBuilderTemplatesEmpty');
        if (!menu) {
            return;
        }
        menu.querySelectorAll('[data-template-id]').forEach(function (n) {
            n.parentNode && n.parentNode.removeChild(n);
        });
        menu.querySelectorAll('[data-template-delete]').forEach(function (n) {
            n.parentNode && n.parentNode.removeChild(n);
        });
        if (emptyEl) {
            emptyEl.style.display = templatesList.length ? 'none' : '';
        }
        var insertBefore = menu.querySelector('.dropdown-divider');
        templatesList.forEach(function (tpl) {
            var li = document.createElement('li');
            var row = document.createElement('div');
            row.className = 'dropdown-item d-flex align-items-center justify-content-between gap-2';
            row.setAttribute('data-template-id', String(tpl.id));
            var loadBtn = document.createElement('button');
            loadBtn.type = 'button';
            loadBtn.className = 'btn btn-link btn-sm p-0 text-start flex-grow-1 text-decoration-none';
            loadBtn.textContent = tpl.name || ('Template #' + tpl.id);
            loadBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                loadTemplate(Number(tpl.id));
            });
            var delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn btn-link btn-sm p-0 text-danger';
            delBtn.setAttribute('data-template-delete', String(tpl.id));
            delBtn.title = 'Delete template';
            delBtn.textContent = '×';
            delBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                deleteTemplate(Number(tpl.id), tpl.name || '');
            });
            row.appendChild(loadBtn);
            row.appendChild(delBtn);
            li.appendChild(row);
            if (insertBefore) {
                menu.insertBefore(li, insertBefore);
            } else {
                menu.appendChild(li);
            }
        });
    }

    function loadTemplate(id) {
        if (!templatesUrl || !id) {
            return;
        }
        setStatus('Loading template…');
        fetch(templatesUrl.replace(/\/$/, '') + '/' + id, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json || !json.success || !json.data || !json.data.layout) {
                    throw new Error((json && json.error && json.error.message) || 'Load failed');
                }
                layout = json.data.layout;
                clearSelection();
                setStatus('Template applied (not saved yet)');
                render();
            });
        }).catch(function (err) {
            setStatus(err.message || 'Load failed', true);
        });
    }

    function saveAsTemplate() {
        if (!templatesUrl) {
            setStatus('Templates unavailable', true);
            return;
        }
        var name = window.prompt('Template name');
        if (name == null) {
            return;
        }
        name = String(name).trim();
        if (!name) {
            setStatus('Name required', true);
            return;
        }
        setStatus('Saving template…');
        fetch(templatesUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: csrf,
                name: name,
                layout: layout
            })
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json || !json.success) {
                    throw new Error((json && json.error && json.error.message) || 'Template save failed');
                }
                templatesList.push({
                    id: json.data.id,
                    name: json.data.name || name
                });
                templatesList.sort(function (a, b) {
                    return String(a.name).localeCompare(String(b.name));
                });
                renderTemplatesMenu();
                setStatus('Template saved');
            });
        }).catch(function (err) {
            setStatus(err.message || 'Template save failed', true);
        });
    }

    function deleteTemplate(id, name) {
        if (!templatesUrl || !id) {
            return;
        }
        if (!window.confirm('Delete template "' + (name || id) + '"?')) {
            return;
        }
        fetch(templatesUrl.replace(/\/$/, '') + '/' + id + '/delete', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ csrf_token: csrf })
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json || !json.success) {
                    throw new Error((json && json.error && json.error.message) || 'Delete failed');
                }
                templatesList = templatesList.filter(function (t) {
                    return Number(t.id) !== Number(id);
                });
                renderTemplatesMenu();
                setStatus('Template deleted');
            });
        }).catch(function (err) {
            setStatus(err.message || 'Delete failed', true);
        });
    }

    document.getElementById('cmsBuilderAddSection') && document.getElementById('cmsBuilderAddSection').addEventListener('click', function () {
        layout.sections.push(emptySection());
        selection = { kind: 'section', sectionIdx: layout.sections.length - 1, rowIdx: -1, colIdx: -1, modIdx: -1 };
        openPanel();
        render();
    });

    document.getElementById('cmsBuilderSave') && document.getElementById('cmsBuilderSave').addEventListener('click', save);

    document.querySelectorAll('.cms-builder-device-toggle [data-device]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setDevice(btn.getAttribute('data-device') || 'desktop');
        });
    });

    document.getElementById('cmsBuilderSaveTemplate') && document.getElementById('cmsBuilderSaveTemplate').addEventListener('click', function (e) {
        e.preventDefault();
        saveAsTemplate();
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 's') {
            e.preventDefault();
            save();
        }
    });

    document.getElementById('cmsBuilderPanelClose') && document.getElementById('cmsBuilderPanelClose').addEventListener('click', function () {
        clearSelection();
        render();
    });

    document.querySelectorAll('[data-panel-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            activeTab = tab.getAttribute('data-panel-tab') || 'content';
            document.querySelectorAll('[data-panel-tab]').forEach(function (t) {
                t.classList.toggle('active', t === tab);
            });
            renderPanel();
        });
    });

    if (panel) {
        panel.hidden = true;
    }
    setDevice('desktop');
    renderTemplatesMenu();
    updateShell();
    render();

    /** E2E / console hook — apply layout and save via the real editor. */
    global.CmsBuilderApi = {
        getLayout: function () {
            return layout;
        },
        setLayout: function (next) {
            layout = next && next.sections ? next : { version: 1, sections: [] };
            clearSelection();
            render();
        },
        applyColumnLayout: applyColumnLayout,
        columnLayouts: COLUMN_LAYOUTS,
        setDevice: setDevice,
        saveAsTemplate: saveAsTemplate,
        loadTemplate: loadTemplate,
        save: save,
        render: render
    };
})(window);
