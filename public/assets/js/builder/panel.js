/**
 * Visual layout builder — inspector fields, carousel, and accordion.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function fieldMeta(opts) {
                if (Array.isArray(opts)) {
                    return { choices: opts, hint: '', placeholder: '', rows: 4, min: 1, max: 12, step: 1, overridden: false };
                }
                opts = opts || {};
                return {
                    choices: opts.choices || [],
                    hint: opts.hint || '',
                    placeholder: opts.placeholder || '',
                    rows: opts.rows || 4,
                    min: opts.min != null ? opts.min : 1,
                    max: opts.max != null ? opts.max : 12,
                    step: opts.step != null ? opts.step : 1,
                    overridden: !!opts.overridden
                };
            }
            
            function field(label, name, value, type, opts) {
                type = type || 'text';
                var meta = fieldMeta(opts);
                var id = 'bf_' + (selection.kind || 'x') + '_' + String(name).replace(/[^a-z0-9_]/gi, '_');
                var wrapCls = meta.overridden ? ' cms-lb-is-override' : '';
                var inheritHint = meta.overridden
                    ? '<div class="form-text cms-lb-override-hint">' + esc((currentDevice === 'mobile' ? 'Mobile' : 'Tablet') + ' override') + '</div>'
                    : '';
                if (type === 'checkbox') {
                    return '<div class="form-check' + wrapCls + '">'
                        + '<input type="checkbox" class="form-check-input" id="' + id + '" data-field="' + esc(name) + '"' + (value ? ' checked' : '') + '>'
                        + '<label class="form-check-label" for="' + id + '">' + esc(label) + '</label>'
                        + (meta.hint ? '<div class="form-text">' + esc(meta.hint) + '</div>' : '')
                        + inheritHint
                        + '</div>';
                }
                var html = '<label class="form-label" for="' + id + '">' + esc(label) + '</label>';
                if (type === 'textarea') {
                    html += '<textarea class="form-control form-control-sm" id="' + id + '" data-field="' + esc(name) + '" rows="' + meta.rows + '"'
                        + (meta.placeholder ? ' placeholder="' + esc(meta.placeholder) + '"' : '') + '>' + esc(value || '') + '</textarea>';
                } else if (type === 'select') {
                    html += '<select class="form-select form-select-sm" id="' + id + '" data-field="' + esc(name) + '">';
                    meta.choices.forEach(function (o) {
                        html += '<option value="' + esc(o.v) + '"' + (String(o.v) === String(value) ? ' selected' : '') + '>' + esc(o.l) + '</option>';
                    });
                    html += '</select>';
                } else if (type === 'color') {
                    html += colorFieldRow(name, value, id);
                } else if (type === 'range') {
                    html += '<div class="d-flex align-items-center gap-2">'
                        + '<input type="range" class="form-range" id="' + id + '" data-field="' + esc(name) + '" min="' + meta.min
                        + '" max="' + meta.max + '" step="' + meta.step + '" value="' + esc(value || meta.min) + '">'
                        + '<span class="small text-nowrap" data-range-readout="' + esc(name) + '">' + esc(value || meta.min) + '</span>'
                        + '</div>';
                } else {
                    html += '<input type="' + esc(type) + '" class="form-control form-control-sm" id="' + id + '" data-field="' + esc(name)
                        + '" value="' + esc(value || '') + '"' + (meta.placeholder ? ' placeholder="' + esc(meta.placeholder) + '"' : '') + '>';
                }
                if (meta.hint) {
                    html += '<div class="form-text">' + esc(meta.hint) + '</div>';
                }
                return '<div class="cms-lb-field' + wrapCls + '">' + html + inheritHint + '</div>';
            }
            
            function colorFieldRow(name, value, id) {
                var hex = /^#[0-9a-fA-F]{6}$/.test(String(value || '')) ? String(value) : '#111111';
                return '<div class="cms-lb-color-row">'
                    + '<input type="color" class="form-control form-control-color" data-color-sync="' + esc(name) + '" value="' + esc(hex) + '" aria-label="Pick color">'
                    + '<input type="text" class="form-control form-control-sm" id="' + esc(id) + '" data-field="' + esc(name)
                    + '" value="' + esc(value || '') + '" placeholder="#c27a3a" spellcheck="false">'
                    + '</div>';
            }
            
            function presetRow(name, presets) {
                var html = '<div class="cms-lb-presets" role="group" aria-label="Quick values">';
                presets.forEach(function (p) {
                    html += '<button type="button" class="btn btn-outline-secondary btn-sm" data-preset-field="' + esc(name)
                        + '" data-preset-value="' + esc(p.v) + '">' + esc(p.l) + '</button>';
                });
                return html + '</div>';
            }
            
            function deviceStyleNoticeHtml(node) {
                if (isModSel() && activeTab === 'design' && designState === 'hover') {
                    var html = '<p class="cms-lb-panel-lead cms-lb-device-note">Editing <strong>hover</strong> (same on every device). Empty fields inherit Normal. Buttons need this so site/button styles do not win.</p>';
                    if (hasHoverStyle(node)) {
                        html += '<p class="mb-2"><button type="button" class="btn btn-outline-secondary btn-sm" data-clear-hover-style>Use normal values</button></p>';
                    }
                    return html;
                }
                var bp = deviceStyleName();
                if (!bp) {
                    return '';
                }
                var label = bp === 'mobile' ? 'Mobile' : 'Tablet';
                var cut = bp === 'mobile' ? '768px' : '1024px';
                var html = '<p class="cms-lb-panel-lead cms-lb-device-note">Editing <strong>' + label + '</strong> styles (public CSS below '
                    + cut + '). Same value as desktop inherits. Column width stays one grid for all devices.</p>';
                if (hasDeviceStyle(node)) {
                    html += '<p class="mb-2"><button type="button" class="btn btn-outline-secondary btn-sm" data-clear-device-style>Use desktop values</button></p>';
                }
                return html;
            }

            function designStateToggleHtml() {
                var hover = designState === 'hover';
                return '<div class="cms-lb-state-toggle" role="group" aria-label="Normal or hover">'
                    + '<button type="button" class="btn btn-sm ' + (!hover ? 'btn-dark' : 'btn-outline-secondary') + '" data-design-state="normal"'
                    + ' aria-pressed="' + (!hover ? 'true' : 'false') + '">Normal</button>'
                    + '<button type="button" class="btn btn-sm ' + (hover ? 'btn-dark' : 'btn-outline-secondary') + '" data-design-state="hover"'
                    + ' aria-pressed="' + (hover ? 'true' : 'false') + '">Hover</button>'
                    + '</div>';
            }

            function styleOpts(node, kind, name, extra) {
                extra = extra || {};
                extra.overridden = isStyleOverridden(node, kind, name);
                return extra;
            }

            function parseSpacingSides(value) {
                var parts = String(value || '').trim().split(/\s+/).filter(Boolean);
                if (!parts.length) {
                    return { t: '', r: '', b: '', l: '' };
                }
                if (parts.length === 1) {
                    return { t: parts[0], r: parts[0], b: parts[0], l: parts[0] };
                }
                if (parts.length === 2) {
                    return { t: parts[0], r: parts[1], b: parts[0], l: parts[1] };
                }
                if (parts.length === 3) {
                    return { t: parts[0], r: parts[1], b: parts[2], l: parts[1] };
                }
                return { t: parts[0], r: parts[1], b: parts[2], l: parts[3] };
            }

            function joinSpacingSides(sides) {
                function tok(v) {
                    v = String(v || '').trim();
                    if (v === '0') {
                        return '0px';
                    }
                    return v;
                }
                var t = tok(sides && sides.t);
                var r = tok(sides && sides.r);
                var b = tok(sides && sides.b);
                var l = tok(sides && sides.l);
                if (!t && !r && !b && !l) {
                    return '';
                }
                t = t || '0px';
                r = r || '0px';
                b = b || '0px';
                l = l || '0px';
                if (t === r && r === b && b === l) {
                    return t;
                }
                if (t === b && r === l) {
                    return t + ' ' + r;
                }
                if (r === l) {
                    return t + ' ' + r + ' ' + b;
                }
                return t + ' ' + r + ' ' + b + ' ' + l;
            }

            function syncSpacingInputs(name, joined) {
                if (!panelBody) {
                    return;
                }
                var sides = parseSpacingSides(joined);
                ['t', 'r', 'b', 'l'].forEach(function (side) {
                    var el = panelBody.querySelector('[data-spacing-field="' + name + '"][data-spacing-side="' + side + '"]');
                    if (el) {
                        el.value = sides[side];
                    }
                });
                var hidden = panelBody.querySelector('[data-field="' + name + '"]');
                if (hidden) {
                    hidden.value = joined || '';
                }
            }

            function spacingSidesHtml(node, kind, name, label) {
                var raw = styleFieldValue(node, kind, name) || '';
                var sides = parseSpacingSides(raw);
                var over = isStyleOverridden(node, kind, name);
                var html = '<div class="cms-lb-field cms-lb-spacing' + (over ? ' cms-lb-is-override' : '') + '">';
                html += '<p class="cms-lb-field-group">' + esc(label) + '</p>';
                html += '<input type="hidden" data-field="' + esc(name) + '" value="' + esc(raw) + '">';
                html += '<div class="cms-lb-trbl" role="group" aria-label="' + esc(label) + '">';
                [
                    { k: 't', l: 'Top' },
                    { k: 'r', l: 'Right' },
                    { k: 'b', l: 'Bottom' },
                    { k: 'l', l: 'Left' }
                ].forEach(function (side) {
                    var id = 'bf_sp_' + name + '_' + side.k;
                    html += '<div><label class="form-label" for="' + id + '">' + side.l + '</label>'
                        + '<input type="text" class="form-control form-control-sm" id="' + id
                        + '" data-spacing-field="' + esc(name) + '" data-spacing-side="' + side.k
                        + '" value="' + esc(sides[side.k]) + '" placeholder="0px"></div>';
                });
                html += '</div>';
                if (over) {
                    html += '<div class="form-text cms-lb-override-hint">' + esc((designState === 'hover' ? 'Hover' : (currentDevice === 'mobile' ? 'Mobile' : 'Tablet')) + ' override') + '</div>';
                }
                html += '</div>';
                return html;
            }

            function refreshSelectedLiveCss(node) {
                if (typeof patchLiveCssForNode === 'function' && node) {
                    patchLiveCssForNode(node, isModSel() ? 'design' : 'settings');
                    return;
                }
                refreshLiveCss();
            }

            function chromeStyleFields(node, kind) {
                var html = field('Border width', 'border_width', styleFieldValue(node, kind, 'border_width') || '', 'text', styleOpts(node, kind, 'border_width', { placeholder: '1px' }));
                html += presetRow('border_width', [
                    { v: '', l: 'None' },
                    { v: '1px', l: '1' },
                    { v: '2px', l: '2' },
                    { v: '4px', l: '4' }
                ]);
                html += field('Border style', 'border_style', styleFieldValue(node, kind, 'border_style') || '', 'select', styleOpts(node, kind, 'border_style', {
                    choices: [
                        { v: '', l: 'Default' },
                        { v: 'solid', l: 'Solid' },
                        { v: 'dashed', l: 'Dashed' },
                        { v: 'dotted', l: 'Dotted' },
                        { v: 'none', l: 'None' }
                    ]
                }));
                html += field('Border color', 'border_color', styleFieldValue(node, kind, 'border_color') || '', 'color', styleOpts(node, kind, 'border_color'));
                html += colorTokenRow('border_color');
                html += field('Corners', 'border_radius', styleFieldValue(node, kind, 'border_radius') || '', 'text', styleOpts(node, kind, 'border_radius', { placeholder: '8px' }));
                html += presetRow('border_radius', [
                    { v: '', l: 'None' },
                    { v: '4px', l: 'S' },
                    { v: '8px', l: 'M' },
                    { v: '16px', l: 'L' },
                    { v: '999px', l: 'Pill' }
                ]);
                html += field('Shadow', 'box_shadow', styleFieldValue(node, kind, 'box_shadow') || '', 'select', styleOpts(node, kind, 'box_shadow', {
                    choices: [
                        { v: '', l: 'None' },
                        { v: 'sm', l: 'Small' },
                        { v: 'md', l: 'Medium' },
                        { v: 'lg', l: 'Large' },
                        { v: 'none', l: 'No shadow' }
                    ]
                }));
                return html;
            }

            function styleClipBarHtml() {
                return '<p class="cms-lb-style-clip">'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm" data-copy-style>Copy style</button>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm" data-paste-style' + (canPasteStyle() ? '' : ' disabled') + '>Paste style</button>'
                    + '</p>';
            }

            function colorTokenRow(name) {
                return presetRow(name, [
                    { v: '', l: 'None' },
                    { v: 'accent', l: 'Accent' },
                    { v: 'accent-soft', l: 'Soft' },
                    { v: 'text', l: 'Text' },
                    { v: 'muted', l: 'Muted' },
                    { v: 'surface', l: 'Surface' },
                    { v: 'bg', l: 'Page' }
                ]);
            }

            function backgroundImageFields(node) {
                var set = node.settings || {};
                var html = '<p class="cms-lb-field-group">Background image</p>';
                html += mediaPreviewHtml({ media_id: set.bg_media_id, url: set.bg_image });
                var opts = [{ v: '', l: '— None —' }];
                mediaList.forEach(function (m) {
                    opts.push({ v: String(m.id), l: (m.name || m.original_name || ('#' + m.id)) });
                });
                html += field('Library image', 'bg_media_id', set.bg_media_id ? String(set.bg_media_id) : '', 'select', opts);
                html += field('Or image URL', 'bg_image', set.bg_image || '', 'text', {
                    placeholder: 'https:// or /share/media/…',
                    hint: 'Shared on all devices. Overlay can differ per Desktop / Tablet / Mobile.'
                });
                html += field('Overlay', 'bg_overlay', styleFieldValue(node, 'settings', 'bg_overlay') || '', 'color', styleOpts(node, 'settings', 'bg_overlay'));
                html += colorTokenRow('bg_overlay');
                html += field('Overlay strength', 'bg_overlay_opacity', styleFieldValue(node, 'settings', 'bg_overlay_opacity') || '40', 'range', styleOpts(node, 'settings', 'bg_overlay_opacity', {
                    min: 0,
                    max: 80,
                    step: 5
                }));
                if (canUpload && global.CmsMediaPicker) {
                    html += '<div class="mt-2"><label class="form-label">Upload background</label>'
                        + '<input type="file" accept="image/*" class="form-control form-control-sm" data-upload-file data-upload-target="bg">'
                        + '<button type="button" class="btn btn-outline-secondary btn-sm mt-1" data-upload-btn data-upload-target="bg">Upload image</button>'
                        + '<span class="small text-muted ms-1" data-upload-status data-upload-target="bg"></span></div>';
                }
                return html;
            }

            function videoBgFields(node) {
                var set = node.settings || {};
                var html = '<p class="cms-lb-field-group">Video background</p>';
                html += field('Video URL', 'bg_video_url', set.bg_video_url || '', 'text', {
                    placeholder: 'https://www.youtube.com/watch?v=…',
                    hint: 'YouTube, Vimeo, or HTTPS .mp4/.webm/.ogg. Public page constructs the embed (mute/loop). The canvas shows a placeholder and never loads the iframe.'
                });
                return html;
            }

            function shapeDividerFields(node) {
                var set = node.settings || {};
                var html = '<p class="cms-lb-field-group">Shape dividers</p>';
                html += '<p class="small text-muted">Hardcoded SVG only (wave, tilt, curve, triangle). Fill uses theme tokens or hex — never custom SVG.</p>';
                ['top', 'bottom'].forEach(function (side) {
                    var label = side === 'top' ? 'Top' : 'Bottom';
                    html += field(label + ' shape', 'shape_' + side, set['shape_' + side] || '', 'select', {
                        choices: [
                            { v: '', l: 'None' },
                            { v: 'wave', l: 'Wave' },
                            { v: 'tilt', l: 'Tilt' },
                            { v: 'curve', l: 'Curve' },
                            { v: 'triangle', l: 'Triangle' }
                        ]
                    });
                    html += field(label + ' color', 'shape_' + side + '_color', styleFieldValue(node, 'settings', 'shape_' + side + '_color') || '', 'color', styleOpts(node, 'settings', 'shape_' + side + '_color'));
                    html += colorTokenRow('shape_' + side + '_color');
                    html += field(label + ' height', 'shape_' + side + '_height', styleFieldValue(node, 'settings', 'shape_' + side + '_height') || '', 'select', styleOpts(node, 'settings', 'shape_' + side + '_height', {
                        choices: [
                            { v: '', l: 'Medium' },
                            { v: 'sm', l: 'Small' },
                            { v: 'md', l: 'Medium' },
                            { v: 'lg', l: 'Large' }
                        ]
                    }));
                    html += field('Flip ' + side, 'shape_' + side + '_flip', !!set['shape_' + side + '_flip'], 'checkbox');
                });
                return html;
            }

            function moduleLead(type) {
                var meta = MODULE_META[type];
                if (!meta || !meta.hint) {
                    return '';
                }
                return '<p class="cms-lb-panel-lead">' + esc(meta.hint) + '</p>';
            }

            function moduleDesignGroups(type) {
                var allowed = { align: 1, type: 1, color: 1, space: 1, chrome: 1 };
                var list = (moduleCatalog[type] && moduleCatalog[type].design) || [];
                var out = {};
                if (!list.length) {
                    return { align: 1, type: 1, color: 1, space: 1, chrome: 1 };
                }
                list.forEach(function (k) {
                    if (allowed[k]) {
                        out[k] = 1;
                    }
                });
                return out;
            }
            
            function mediaPreviewHtml(d) {
                var m = d.media_id ? mediaById(d.media_id) : null;
                var src = (m && (m.preview || m.url)) || d.url || '';
                if (!src) {
                    return '';
                }
                return '<div class="cms-lb-media-preview"><img src="' + esc(src) + '" alt=""></div>';
            }
            
            function renderCrumbs() {
                var el = document.getElementById('cmsBuilderPanelCrumbs');
                if (!el) {
                    return;
                }
                if (!selection.kind || (panel && panel.hidden)) {
                    el.innerHTML = '';
                    el.hidden = true;
                    return;
                }
                var bits = [];
                bits.push('<button type="button" class="cms-lb-crumb' + (selection.kind === 'section' ? ' is-current' : '') + '" data-crumb-kind="section"'
                    + (selection.kind === 'section' ? ' disabled' : '') + '>Section</button>');
                if (selection.rowIdx >= 0) {
                    bits.push('<span class="cms-lb-crumb-sep">/</span>');
                    bits.push('<button type="button" class="cms-lb-crumb' + (selection.kind === 'row' ? ' is-current' : '') + '" data-crumb-kind="row"'
                        + (selection.kind === 'row' ? ' disabled' : '') + '>Row</button>');
                }
                if (selection.colIdx >= 0) {
                    bits.push('<span class="cms-lb-crumb-sep">/</span>');
                    bits.push('<button type="button" class="cms-lb-crumb' + (selection.kind === 'column' ? ' is-current' : '') + '" data-crumb-kind="column"'
                        + (selection.kind === 'column' ? ' disabled' : '') + '>Col</button>');
                }
                if (selection.modIdx >= 0 && (selection.kind === 'module' || selection.kind === 'inner_column' || selection.kind === 'inner_module')) {
                    bits.push('<span class="cms-lb-crumb-sep">/</span>');
                    if (selection.kind === 'module') {
                        bits.push('<span class="cms-lb-crumb is-current">' + esc((panelTitle && panelTitle.textContent) || 'Module') + '</span>');
                    } else {
                        bits.push('<button type="button" class="cms-lb-crumb" data-crumb-kind="module">Inner row</button>');
                    }
                }
                if (selection.kind === 'inner_column' || selection.kind === 'inner_module') {
                    bits.push('<span class="cms-lb-crumb-sep">/</span>');
                    bits.push('<button type="button" class="cms-lb-crumb' + (selection.kind === 'inner_column' ? ' is-current' : '') + '" data-crumb-kind="inner_column"'
                        + (selection.kind === 'inner_column' ? ' disabled' : '') + '>Inner col</button>');
                }
                if (selection.kind === 'inner_module') {
                    bits.push('<span class="cms-lb-crumb-sep">/</span>');
                    bits.push('<span class="cms-lb-crumb is-current">' + esc((panelTitle && panelTitle.textContent) || 'Module') + '</span>');
                }
                el.innerHTML = bits.join('');
                el.hidden = false;
                el.querySelectorAll('[data-crumb-kind]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        selectAncestor(btn.getAttribute('data-crumb-kind'));
                    });
                });
            }
            
            function renderPanel() {
                var node = getSelectedNode();
                if (!node || !panelBody) {
                    if (panel) {
                        panel.hidden = true;
                    }
                    updateShell();
                    renderCrumbs();
                    return;
                }
            
                var title = isModSel()
                    ? (moduleTypes[node.type] || node.type)
                    : (selection.kind.charAt(0).toUpperCase() + selection.kind.slice(1).replace(/_/g, ' '));
                if (modulePickTarget && isColSel()) {
                    title = 'Add module';
                }
                if (panelTitle) {
                    panelTitle.textContent = title;
                }
                renderCrumbs();
            
                var html = '';
                if (activeTab === 'content') {
                    if (isModSel()) {
                        if (node.type === 'inner_row') {
                            html += '<p class="cms-lb-panel-lead">Split this column into nested columns. Only one nested row is allowed.</p>';
                            html += renderColumnLayoutPicker(node, selection.sectionIdx, selection.rowIdx);
                        } else {
                            html += renderModuleContent(node);
                        }
                    } else if (selection.kind === 'section') {
                        html += '<p class="cms-lb-panel-lead">Section wraps rows. Full width drops the inner container.</p>';
                        html += deviceStyleNoticeHtml(node);
                        html += field('Section type', 'type', node.type || 'regular', 'select', [
                            { v: 'regular', l: 'Regular (contained)' },
                            { v: 'fullwidth', l: 'Full width' }
                        ]);
                        html += field('Background color', 'bg_color', styleFieldValue(node, 'settings', 'bg_color') || '', 'color', styleOpts(node, 'settings', 'bg_color'));
                        html += colorTokenRow('bg_color');
                        html += spacingSidesHtml(node, 'settings', 'padding', 'Padding');
                        html += presetRow('padding', [
                            { v: '', l: 'Default' },
                            { v: '1rem', l: 'S' },
                            { v: '2rem', l: 'M' },
                            { v: '3rem', l: 'L' },
                            { v: '4rem', l: 'XL' }
                        ]);
                    } else if (isColSel()) {
                        var pick = modulePickTarget || {
                            si: selection.sectionIdx,
                            ri: selection.rowIdx,
                            ci: selection.colIdx
                        };
                        html += renderModuleTypePicker(pick.si, pick.ri, pick.ci);
                        html += '<p class="cms-lb-field-group">Column size</p>';
                        html += deviceStyleNoticeHtml(node);
                        var colW = Number(node.width) || 12;
                        var parentRow = selection.kind === 'inner_column'
                            ? getInnerRowAt(selection.sectionIdx, selection.rowIdx, selection.colIdx, selection.modIdx)
                            : (layout.sections[selection.sectionIdx] && layout.sections[selection.sectionIdx].rows
                                && layout.sections[selection.sectionIdx].rows[selection.rowIdx]);
                        var rowSum = parentRow ? rowWidths(parentRow).reduce(function (a, b) { return a + b; }, 0) : colW;
                        html += field('Width (1–12 grid)', 'width', colW, 'range', {
                            min: 1,
                            max: 12,
                            hint: selection.kind === 'inner_column'
                                ? 'Bootstrap columns inside this inner row. Change the inner-row layout from the parent module.'
                                : 'Bootstrap columns. Drag the blue edge between two columns on the canvas to split their widths.'
                        });
                        html += '<div class="cms-lb-width-bar" aria-hidden="true"><span style="width:' + ((colW / 12) * 100) + '%"></span></div>';
                        html += '<p class="small text-muted mt-1 mb-2">This column ' + colW + '/12 · row total ' + rowSum + '/12'
                            + (rowSum > 12 ? ' (wraps to the next line)' : '') + '.</p>';
                        html += field('Min height', 'min_height', styleFieldValue(node, 'settings', 'min_height') || '', 'text', styleOpts(node, 'settings', 'min_height', {
                            placeholder: '240px',
                            hint: 'Minimum height (px, rem, %, or vh). Content can grow taller.'
                        }));
                        html += presetRow('min_height', [
                            { v: '', l: 'Auto' },
                            { v: '160px', l: 'S' },
                            { v: '240px', l: 'M' },
                            { v: '360px', l: 'L' },
                            { v: '50vh', l: 'Half screen' }
                        ]);
                        html += field('Vertical align', 'valign', styleFieldValue(node, 'settings', 'valign') || '', 'select', styleOpts(node, 'settings', 'valign', {
                            choices: [
                                { v: '', l: 'Top (default)' },
                                { v: 'center', l: 'Middle' },
                                { v: 'bottom', l: 'Bottom' }
                            ]
                        }));
                        html += '<p class="small text-muted mt-2 mb-0">Or select the parent <strong>Row</strong> for equal splits. Drag the ⋮⋮ handle to reorder.</p>';
                    } else if (selection.kind === 'row') {
                        html += renderColumnLayoutPicker(node, selection.sectionIdx, selection.rowIdx);
                        html += field('Reverse columns on mobile', 'col_reverse_mobile', !!(node.settings && node.settings.col_reverse_mobile), 'checkbox', {
                            hint: 'Public site below 768px (and Mobile preview) swaps column order. Shared on every device.'
                        });
                    } else {
                        html += '<p class="small text-muted mb-0">Use Design / Advanced for styling. Add modules from the column chrome.</p>';
                    }
                } else if (activeTab === 'design') {
                    html += '<p class="cms-lb-panel-lead">Appearance for this ' + esc(selection.kind) + '. Colors need #hex (3–8 digits). Spacing needs px, rem, em, or %.</p>';
                    html += styleClipBarHtml();
                    if (isModSel()) {
                        var dg = moduleDesignGroups(node.type);
                        if (dg.color || dg.type || dg.chrome) {
                            html += designStateToggleHtml();
                        }
                    }
                    html += deviceStyleNoticeHtml(node);
                    if (isModSel()) {
                        dg = moduleDesignGroups(node.type);
                        if (dg.align) {
                            html += field('Text align', 'text_align', styleFieldValue(node, 'design', 'text_align') || '', 'select', styleOpts(node, 'design', 'text_align', {
                                choices: [
                                    { v: '', l: 'Default' },
                                    { v: 'left', l: 'Left' },
                                    { v: 'center', l: 'Center' },
                                    { v: 'right', l: 'Right' }
                                ]
                            }));
                        }
                        if (dg.color) {
                            html += field('Text color', 'text_color', styleFieldValue(node, 'design', 'text_color') || '', 'color', styleOpts(node, 'design', 'text_color'));
                            html += colorTokenRow('text_color');
                            html += field('Background', 'bg_color', styleFieldValue(node, 'design', 'bg_color') || '', 'color', styleOpts(node, 'design', 'bg_color'));
                            html += colorTokenRow('bg_color');
                        }
                        if (dg.space) {
                            html += spacingSidesHtml(node, 'design', 'padding', 'Padding');
                            html += presetRow('padding', [
                                { v: '', l: 'Default' },
                                { v: '0px', l: '0' },
                                { v: '0.5rem', l: 'S' },
                                { v: '1rem', l: 'M' },
                                { v: '2rem', l: 'L' }
                            ]);
                            html += spacingSidesHtml(node, 'design', 'margin', 'Margin');
                        }
                        if (dg.type) {
                            html += field('Font size', 'font_size', styleFieldValue(node, 'design', 'font_size') || '', 'text', styleOpts(node, 'design', 'font_size', { placeholder: '1.25rem' }));
                            html += presetRow('font_size', [
                                { v: '', l: 'Default' },
                                { v: '0.9rem', l: 'S' },
                                { v: '1rem', l: 'M' },
                                { v: '1.5rem', l: 'L' },
                                { v: '2rem', l: 'XL' }
                            ]);
                            html += field('Weight', 'font_weight', styleFieldValue(node, 'design', 'font_weight') || '', 'select', styleOpts(node, 'design', 'font_weight', {
                                choices: [
                                    { v: '', l: 'Default' },
                                    { v: '400', l: 'Regular' },
                                    { v: '500', l: 'Medium' },
                                    { v: '600', l: 'Semibold' },
                                    { v: '700', l: 'Bold' }
                                ]
                            }));
                            html += field('Line height', 'line_height', styleFieldValue(node, 'design', 'line_height') || '', 'select', styleOpts(node, 'design', 'line_height', {
                                choices: [
                                    { v: '', l: 'Default' },
                                    { v: '1.2', l: 'Tight' },
                                    { v: '1.4', l: 'Snug' },
                                    { v: '1.6', l: 'Normal' },
                                    { v: '1.8', l: 'Relaxed' }
                                ]
                            }));
                            html += field('Font', 'font_family', styleFieldValue(node, 'design', 'font_family') || '', 'select', styleOpts(node, 'design', 'font_family', {
                                choices: [
                                    { v: '', l: 'Default' },
                                    { v: 'system', l: 'System UI' },
                                    { v: 'sans', l: 'Sans' },
                                    { v: 'serif', l: 'Serif' },
                                    { v: 'mono', l: 'Monospace' }
                                ]
                            }));
                            html += field('Letter spacing', 'letter_spacing', styleFieldValue(node, 'design', 'letter_spacing') || '', 'select', styleOpts(node, 'design', 'letter_spacing', {
                                choices: [
                                    { v: '', l: 'Default' },
                                    { v: 'tight', l: 'Tight' },
                                    { v: 'snug', l: 'Snug' },
                                    { v: 'normal', l: 'Normal' },
                                    { v: 'wide', l: 'Wide' },
                                    { v: 'wider', l: 'Wider' }
                                ]
                            }));
                            html += field('Text transform', 'text_transform', styleFieldValue(node, 'design', 'text_transform') || '', 'select', styleOpts(node, 'design', 'text_transform', {
                                choices: [
                                    { v: '', l: 'Default' },
                                    { v: 'none', l: 'None' },
                                    { v: 'uppercase', l: 'Uppercase' },
                                    { v: 'lowercase', l: 'Lowercase' },
                                    { v: 'capitalize', l: 'Capitalize' }
                                ]
                            }));
                        }
                        if (dg.chrome) {
                            html += chromeStyleFields(node, 'design');
                        }
                    } else {
                        html += field('Background', 'bg_color', styleFieldValue(node, 'settings', 'bg_color') || '', 'color', styleOpts(node, 'settings', 'bg_color'));
                        html += colorTokenRow('bg_color');
                        html += spacingSidesHtml(node, 'settings', 'padding', 'Padding');
                        html += presetRow('padding', [
                            { v: '', l: 'Default' },
                            { v: '1rem', l: 'S' },
                            { v: '2rem', l: 'M' },
                            { v: '3rem', l: 'L' }
                        ]);
                        html += field('Min height', 'min_height', styleFieldValue(node, 'settings', 'min_height') || '', 'text', styleOpts(node, 'settings', 'min_height', { placeholder: '200px' }));
                        html += presetRow('min_height', [
                            { v: '', l: 'Auto' },
                            { v: '160px', l: 'S' },
                            { v: '240px', l: 'M' },
                            { v: '360px', l: 'L' }
                        ]);
                        html += chromeStyleFields(node, 'settings');
                        html += backgroundImageFields(node);
                        if (selection.kind === 'section') {
                            html += videoBgFields(node);
                            html += shapeDividerFields(node);
                        }
                    }
                } else {
                    html += '<p class="cms-lb-panel-lead">Visibility is previewed with Desktop / Tablet / Mobile. Hide classes apply on the public site. Sticky stays in view while its parent scrolls.</p>';
                    html += deviceStyleNoticeHtml(node);
                    html += positioningFields(node, isModSel() ? 'design' : 'settings');
                    if (isModSel()) {
                        var adv = node.advanced || {};
                        html += field('CSS class', 'css_class', adv.css_class || '', 'text', {
                            hint: 'Letters, numbers, hyphens, underscores only.',
                            placeholder: 'my-module'
                        });
                        html += field('Hide on mobile', 'hide_mobile', !!adv.hide_mobile, 'checkbox', {
                            hint: 'Hidden below Bootstrap md. Dimmed in Mobile preview.'
                        });
                        html += field('Hide on desktop', 'hide_desktop', !!adv.hide_desktop, 'checkbox', {
                            hint: 'Hidden at md and up. Dimmed in Desktop preview.'
                        });
                    } else {
                        var set2 = node.settings || {};
                        html += field('CSS class', 'css_class', set2.css_class || '', 'text');
                    }
                }
            
                panelBody.innerHTML = html;
                bindPanelFields();
            }

            function positioningFields(node, kind) {
                var html = '<p class="cms-lb-field-group">Position</p>';
                html += field('Position', 'position', styleFieldValue(node, kind, 'position') || '', 'select', styleOpts(node, kind, 'position', {
                    choices: [
                        { v: '', l: 'Default' },
                        { v: 'relative', l: 'Relative' },
                        { v: 'sticky', l: 'Sticky' }
                    ],
                    hint: 'Absolute and fixed are not offered. Relative is also applied when you set a z-index.'
                }));
                html += field('Z-index', 'z_index', styleFieldValue(node, kind, 'z_index') || '', 'select', styleOpts(node, kind, 'z_index', {
                    choices: [
                        { v: '', l: 'Default' },
                        { v: '1', l: '1' },
                        { v: '2', l: '2' },
                        { v: '5', l: '5' },
                        { v: '10', l: '10' },
                        { v: '20', l: '20' },
                        { v: '50', l: '50' },
                        { v: '100', l: '100' }
                    ],
                    hint: 'Stacking order (max 100). Copy style does not copy position or z-index.'
                }));
                html += field('Sticky offset', 'sticky_offset', styleFieldValue(node, kind, 'sticky_offset') || '', 'select', styleOpts(node, kind, 'sticky_offset', {
                    choices: [
                        { v: '', l: 'Top (0)' },
                        { v: '0', l: '0' },
                        { v: 'xs', l: '0.5rem' },
                        { v: 'sm', l: '1rem' },
                        { v: 'md', l: '2rem' },
                        { v: 'lg', l: '4.5rem' }
                    ],
                    hint: 'Distance from the top when Position is Sticky (for a site header, try 4.5rem).'
                }));
                return html;
            }
            
            function renderModuleContent(mod) {
                var d = mod.data || {};
                var html = moduleLead(mod.type);
                var meta = moduleCatalog[mod.type] || {};
                if (meta.custom === 'carousel') {
                    html += carouselFields(d);
                    return html;
                }
                if (meta.custom === 'accordion') {
                    html += accordionFields(d);
                    return html;
                }
                if (meta.custom === 'tabs') {
                    html += tabsFields(d);
                    return html;
                }
                if (meta.custom === 'icon_list') {
                    html += iconListFields(d);
                    return html;
                }
                if (meta.custom === 'gallery') {
                    html += galleryFields(d);
                    return html;
                }
                if (meta.custom === 'testimonial') {
                    html += testimonialFields(d);
                    return html;
                }
                var fields = meta.fields || [];
                if (!fields.length) {
                    html += '<p class="small text-muted">No fields.</p>';
                    return html;
                }
                fields.forEach(function (spec) {
                    html += renderCatalogField(spec, d);
                });
                return html;
            }

            function renderCatalogField(spec, d) {
                spec = spec || {};
                var type = spec.type || 'text';
                if (type === 'media') {
                    return mediaField(d);
                }
                if (type === 'note') {
                    return spec.text ? '<p class="form-text">' + esc(spec.text) + '</p>' : '';
                }
                if (type === 'html_preview') {
                    var src = spec.source || 'html';
                    return '<p class="cms-lb-field-group">Preview</p><div class="cms-lb-html-preview" data-html-preview>'
                        + sanitizePreviewHtml(d[src] || '') + '</div>';
                }
                var name = spec.name;
                if (!name) {
                    return '';
                }
                var label = spec.label || name;
                var opts = {
                    hint: spec.hint || '',
                    placeholder: spec.placeholder || ''
                };
                if (type === 'checkbox') {
                    return field(label, name, !!d[name], 'checkbox', opts);
                }
                if (type === 'select') {
                    var choices = spec.choices;
                    if (!choices || !choices.length) {
                        choices = (name === 'style') ? btnStyleChoices : [];
                    }
                    opts.choices = choices;
                    var sel = d[name];
                    if (sel == null || sel === '') {
                        sel = spec.default != null ? spec.default : '';
                    }
                    return field(label, name, sel, 'select', opts);
                }
                if (type === 'textarea') {
                    opts.rows = spec.rows || 4;
                    return field(label, name, d[name], 'textarea', opts);
                }
                if (type === 'rich') {
                    opts.hint = spec.hint || opts.hint;
                    var rich = typeof richEditorHtml === 'function'
                        ? richEditorHtml(name, d[name] || '')
                        : field(label, name, d[name], 'textarea', opts);
                    return '<div class="cms-lb-field"><label class="form-label">' + esc(label) + '</label>'
                        + rich
                        + (opts.hint ? '<div class="form-text">' + esc(opts.hint) + '</div>' : '')
                        + '</div>';
                }
                if (type === 'number') {
                    return field(label, name, d[name], 'number', opts);
                }
                return field(label, name, d[name], 'text', opts);
            }
            
            function mediaField(d) {
                var opts = [{ v: '', l: '— Select media —' }];
                mediaList.forEach(function (m) {
                    opts.push({ v: String(m.id), l: (m.name || m.original_name || ('#' + m.id)) });
                });
                var html = mediaPreviewHtml(d);
                html += field('Media library', 'media_id', d.media_id ? String(d.media_id) : '', 'select', opts);
                html += field('Or image URL', 'url', d.url || '', 'text', {
                    placeholder: 'https://',
                    hint: 'HTTPS URL. Used when no library item is selected.'
                });
                if (canUpload && global.CmsMediaPicker) {
                    html += '<div class="mt-2"><label class="form-label">Upload</label>'
                        + '<input type="file" accept="image/*" class="form-control form-control-sm" data-upload-file>'
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
                html += field('Interval (ms)', 'interval_ms', d.interval_ms || 5000, 'number', {
                    hint: 'Between 2000 and 30000. Clamped on save.'
                });
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
                    html += mediaPreviewHtml(slide);
                    html += slideField(idx, 'Media', 'media_id', slide.media_id ? String(slide.media_id) : '', 'select', mediaOpts);
                    html += slideField(idx, 'Or image URL', 'url', slide.url || '', 'text');
                    html += slideField(idx, 'Alt text', 'alt', slide.alt || '', 'text');
                    html += slideField(idx, 'Caption', 'caption', slide.caption || '', 'textarea');
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
            
            function emptyAccordionItem() {
                return { title: 'Question', body: '' };
            }
            
            function ensureAccordionItems(d) {
                if (!Array.isArray(d.items) || !d.items.length) {
                    d.items = [emptyAccordionItem()];
                }
                return d.items;
            }
            
            function accordionFields(d) {
                var items = ensureAccordionItems(d);
                var html = '';
                html += field('First item open', 'first_open', d.first_open !== false, 'checkbox', {
                    hint: 'Public page uses native expand/collapse (no extra JavaScript).'
                });
                html += '<hr class="my-2"><div class="d-flex justify-content-between align-items-center mb-2">'
                    + '<strong class="small">Items</strong>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm" data-acc-add>+ Add item</button></div>';
                items.forEach(function (item, idx) {
                    html += '<div class="border rounded p-2 mb-2 cms-lb-acc-item-edit" data-acc-wrap="' + idx + '">';
                    html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                        + '<span class="small fw-semibold">Item ' + (idx + 1) + '</span><span>'
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-acc-up="' + idx + '" title="Move up">↑</button> '
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-acc-down="' + idx + '" title="Move down">↓</button> '
                        + '<button type="button" class="btn btn-outline-danger btn-sm" data-acc-del="' + idx + '" title="Remove">×</button>'
                        + '</span></div>';
                    html += accField(idx, 'Title', 'title', (item && item.title) || '', 'text');
                    html += accField(idx, 'Body', 'body', (item && item.body) || '', 'rich');
                    html += '</div>';
                });
                return html;
            }
            
            function accField(idx, label, name, value, type) {
                type = type || 'text';
                var id = 'bf_acc_' + idx + '_' + name;
                var html = '<label class="form-label" for="' + id + '">' + esc(label) + '</label>';
                if (type === 'rich' && typeof richEditorHtml === 'function') {
                    html += richEditorHtml(name, value || '', 'data-acc-idx="' + idx + '" data-acc-field="' + esc(name) + '"');
                    return html;
                }
                if (type === 'textarea' || type === 'rich') {
                    html += '<textarea class="form-control form-control-sm" id="' + id + '" data-acc-idx="' + idx
                        + '" data-acc-field="' + esc(name) + '" rows="3">' + esc(value || '') + '</textarea>';
                } else {
                    html += '<input type="text" class="form-control form-control-sm" id="' + id + '" data-acc-idx="' + idx
                        + '" data-acc-field="' + esc(name) + '" value="' + esc(value || '') + '">';
                }
                return html;
            }
            
            function emptyTabsItem() {
                return { title: 'Tab', body: '' };
            }
            
            function ensureTabsItems(d) {
                if (!Array.isArray(d.items) || !d.items.length) {
                    d.items = [emptyTabsItem()];
                }
                return d.items;
            }
            
            function tabsFields(d) {
                var items = ensureTabsItems(d);
                var html = '<p class="form-text">Public tabs use native radios and CSS (no extra JavaScript). First tab is selected on load.</p>';
                html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                    + '<strong class="small">Tabs</strong>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm" data-tabs-add>+ Add tab</button></div>';
                items.forEach(function (item, idx) {
                    html += '<div class="border rounded p-2 mb-2 cms-lb-acc-item-edit">';
                    html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                        + '<span class="small fw-semibold">Tab ' + (idx + 1) + '</span><span>'
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-tabs-up="' + idx + '" title="Move up">↑</button> '
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-tabs-down="' + idx + '" title="Move down">↓</button> '
                        + '<button type="button" class="btn btn-outline-danger btn-sm" data-tabs-del="' + idx + '" title="Remove">×</button>'
                        + '</span></div>';
                    html += repeatField('tabs', idx, 'Title', 'title', (item && item.title) || '', 'text');
                    html += repeatField('tabs', idx, 'Body', 'body', (item && item.body) || '', 'rich');
                    html += '</div>';
                });
                return html;
            }
            
            function emptyIconListItem() {
                return { icon: '✓', text: '' };
            }
            
            function ensureIconListItems(d) {
                if (!Array.isArray(d.items) || !d.items.length) {
                    d.items = [emptyIconListItem()];
                }
                return d.items;
            }
            
            function iconListFields(d) {
                var items = ensureIconListItems(d);
                var html = '<p class="form-text">Emoji or a short symbol plus a line of text. HTML is escaped on the public page.</p>';
                html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                    + '<strong class="small">Items</strong>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm" data-ilist-add>+ Add item</button></div>';
                items.forEach(function (item, idx) {
                    html += '<div class="border rounded p-2 mb-2 cms-lb-acc-item-edit">';
                    html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                        + '<span class="small fw-semibold">Item ' + (idx + 1) + '</span><span>'
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-ilist-up="' + idx + '" title="Move up">↑</button> '
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-ilist-down="' + idx + '" title="Move down">↓</button> '
                        + '<button type="button" class="btn btn-outline-danger btn-sm" data-ilist-del="' + idx + '" title="Remove">×</button>'
                        + '</span></div>';
                    html += repeatField('ilist', idx, 'Icon', 'icon', (item && item.icon) || '', 'text');
                    html += repeatField('ilist', idx, 'Text', 'text', (item && item.text) || '', 'textarea');
                    html += '</div>';
                });
                return html;
            }
            
            function emptyGalleryItem() {
                return { media_id: null, url: '', alt: '', caption: '', link: '' };
            }
            
            function ensureGalleryItems(d) {
                if (!Array.isArray(d.items) || !d.items.length) {
                    d.items = [emptyGalleryItem()];
                }
                return d.items;
            }
            
            function galleryFields(d) {
                var items = ensureGalleryItems(d);
                var html = '';
                html += field('Columns', 'columns', d.columns || 3, 'select', {
                    choices: [
                        { v: 2, l: '2' },
                        { v: 3, l: '3' },
                        { v: 4, l: '4' }
                    ],
                    hint: 'On phones the grid stacks to one column.'
                });
                html += '<hr class="my-2"><div class="d-flex justify-content-between align-items-center mb-2">'
                    + '<strong class="small">Images</strong>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm" data-gal-add>+ Add image</button></div>';
                var mediaOpts = [{ v: '', l: '— Select media —' }];
                mediaList.forEach(function (m) {
                    mediaOpts.push({ v: String(m.id), l: (m.name || m.original_name || ('#' + m.id)) });
                });
                items.forEach(function (item, idx) {
                    html += '<div class="border rounded p-2 mb-2 cms-lb-carousel-slide-edit">';
                    html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                        + '<span class="small fw-semibold">Image ' + (idx + 1) + '</span><span>'
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-gal-up="' + idx + '" title="Move up">↑</button> '
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-gal-down="' + idx + '" title="Move down">↓</button> '
                        + '<button type="button" class="btn btn-outline-danger btn-sm" data-gal-del="' + idx + '" title="Remove">×</button>'
                        + '</span></div>';
                    html += mediaPreviewHtml(item);
                    html += mediaItemField('gal', idx, 'Media', 'media_id', item.media_id ? String(item.media_id) : '', 'select', mediaOpts);
                    html += mediaItemField('gal', idx, 'Or image URL', 'url', item.url || '', 'text');
                    html += mediaItemField('gal', idx, 'Alt text', 'alt', item.alt || '', 'text');
                    html += mediaItemField('gal', idx, 'Caption', 'caption', item.caption || '', 'textarea');
                    html += mediaItemField('gal', idx, 'Link URL', 'link', item.link || '', 'text');
                    if (canUpload && global.CmsMediaPicker) {
                        html += '<div class="mt-1"><input type="file" accept="image/*" class="form-control form-control-sm" data-upload-file data-gal-idx="' + idx + '">'
                            + '<button type="button" class="btn btn-outline-secondary btn-sm mt-1" data-upload-btn data-gal-idx="' + idx + '">Upload image</button>'
                            + '<span class="small text-muted ms-1" data-upload-status data-gal-idx="' + idx + '"></span></div>';
                    }
                    html += '</div>';
                });
                return html;
            }
            
            function emptyTestimonialItem() {
                return { quote: '', name: 'Name', role: '', media_id: null, url: '' };
            }
            
            function ensureTestimonialItems(d) {
                if (!Array.isArray(d.items) || !d.items.length) {
                    d.items = [emptyTestimonialItem()];
                }
                return d.items;
            }
            
            function testimonialFields(d) {
                var items = ensureTestimonialItems(d);
                var html = '<p class="form-text">Quotes are escaped on the public page. Optional photo from Media or a HTTPS URL.</p>';
                html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                    + '<strong class="small">Quotes</strong>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm" data-tml-add>+ Add quote</button></div>';
                var mediaOpts = [{ v: '', l: '— Select media —' }];
                mediaList.forEach(function (m) {
                    mediaOpts.push({ v: String(m.id), l: (m.name || m.original_name || ('#' + m.id)) });
                });
                items.forEach(function (item, idx) {
                    html += '<div class="border rounded p-2 mb-2 cms-lb-acc-item-edit">';
                    html += '<div class="d-flex justify-content-between align-items-center mb-2">'
                        + '<span class="small fw-semibold">Quote ' + (idx + 1) + '</span><span>'
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-tml-up="' + idx + '" title="Move up">↑</button> '
                        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-tml-down="' + idx + '" title="Move down">↓</button> '
                        + '<button type="button" class="btn btn-outline-danger btn-sm" data-tml-del="' + idx + '" title="Remove">×</button>'
                        + '</span></div>';
                    html += mediaPreviewHtml(item);
                    html += mediaItemField('tml', idx, 'Photo', 'media_id', item.media_id ? String(item.media_id) : '', 'select', mediaOpts);
                    html += mediaItemField('tml', idx, 'Or photo URL', 'url', item.url || '', 'text');
                    html += repeatField('tml', idx, 'Quote', 'quote', (item && item.quote) || '', 'textarea');
                    html += repeatField('tml', idx, 'Name', 'name', (item && item.name) || '', 'text');
                    html += repeatField('tml', idx, 'Role', 'role', (item && item.role) || '', 'text');
                    if (canUpload && global.CmsMediaPicker) {
                        html += '<div class="mt-1"><input type="file" accept="image/*" class="form-control form-control-sm" data-upload-file data-tml-idx="' + idx + '">'
                            + '<button type="button" class="btn btn-outline-secondary btn-sm mt-1" data-upload-btn data-tml-idx="' + idx + '">Upload photo</button>'
                            + '<span class="small text-muted ms-1" data-upload-status data-tml-idx="' + idx + '"></span></div>';
                    }
                    html += '</div>';
                });
                return html;
            }
            
            function repeatField(prefix, idx, label, name, value, type) {
                type = type || 'text';
                var id = 'bf_' + prefix + '_' + idx + '_' + name;
                var html = '<label class="form-label" for="' + id + '">' + esc(label) + '</label>';
                if (type === 'rich' && typeof richEditorHtml === 'function') {
                    html += richEditorHtml(name, value || '', 'data-' + prefix + '-idx="' + idx + '" data-' + prefix + '-field="' + esc(name) + '"');
                    return html;
                }
                if (type === 'textarea' || type === 'rich') {
                    html += '<textarea class="form-control form-control-sm" id="' + id + '" data-' + prefix + '-idx="' + idx
                        + '" data-' + prefix + '-field="' + esc(name) + '" rows="3">' + esc(value || '') + '</textarea>';
                } else {
                    html += '<input type="text" class="form-control form-control-sm" id="' + id + '" data-' + prefix + '-idx="' + idx
                        + '" data-' + prefix + '-field="' + esc(name) + '" value="' + esc(value || '') + '">';
                }
                return html;
            }
            
            function mediaItemField(prefix, idx, label, name, value, type, opts) {
                type = type || 'text';
                var id = 'bf_' + prefix + '_' + idx + '_' + name;
                var html = '<label class="form-label" for="' + id + '">' + esc(label) + '</label>';
                if (type === 'select') {
                    html += '<select class="form-select form-select-sm" id="' + id + '" data-' + prefix + '-idx="' + idx
                        + '" data-' + prefix + '-field="' + esc(name) + '">';
                    (opts || []).forEach(function (o) {
                        html += '<option value="' + esc(o.v) + '"' + (String(o.v) === String(value) ? ' selected' : '') + '>' + esc(o.l) + '</option>';
                    });
                    html += '</select>';
                } else if (type === 'textarea') {
                    html += '<textarea class="form-control form-control-sm" id="' + id + '" data-' + prefix + '-idx="' + idx
                        + '" data-' + prefix + '-field="' + esc(name) + '" rows="2">' + esc(value || '') + '</textarea>';
                } else {
                    html += '<input type="text" class="form-control form-control-sm" id="' + id + '" data-' + prefix + '-idx="' + idx
                        + '" data-' + prefix + '-field="' + esc(name) + '" value="' + esc(value || '') + '">';
                }
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
                } else if (type === 'textarea') {
                    html += '<textarea class="form-control form-control-sm" id="' + id + '" data-slide-idx="' + slideIdx
                        + '" data-slide-field="' + esc(name) + '" rows="2">' + esc(value || '') + '</textarea>';
                } else {
                    html += '<input type="' + esc(type) + '" class="form-control form-control-sm" id="' + id + '" data-slide-idx="' + slideIdx
                        + '" data-slide-field="' + esc(name) + '" value="' + esc(value || '') + '">';
                }
                return html;
            }
            
            function bindPanelFields() {
                var inheritBtn = panelBody.querySelector('[data-clear-device-style]');
                if (inheritBtn) {
                    inheritBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var n = getSelectedNode();
                        if (!n) {
                            return;
                        }
                        clearDeviceStyle(n);
                        noteLayoutChange();
                        updateDirtyUi();
                        refreshSelectedLiveCss(n);
                        scheduleHistoryCommit(true);
                        renderPanel();
                    });
                }
                var clearHoverBtn = panelBody.querySelector('[data-clear-hover-style]');
                if (clearHoverBtn) {
                    clearHoverBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var n = getSelectedNode();
                        if (!n) {
                            return;
                        }
                        clearHoverStyle(n);
                        noteLayoutChange();
                        updateDirtyUi();
                        refreshSelectedLiveCss(n);
                        scheduleHistoryCommit(true);
                        renderPanel();
                    });
                }
                panelBody.querySelectorAll('[data-design-state]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        designState = btn.getAttribute('data-design-state') === 'hover' ? 'hover' : 'normal';
                        renderPanel();
                    });
                });
                var copyStyleBtn = panelBody.querySelector('[data-copy-style]');
                if (copyStyleBtn) {
                    copyStyleBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        copyStyle();
                    });
                }
                var pasteStyleBtn = panelBody.querySelector('[data-paste-style]');
                if (pasteStyleBtn) {
                    pasteStyleBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        pasteStyle();
                    });
                }
                panelBody.querySelectorAll('[data-field]').forEach(function (el) {
                    if (el.type === 'hidden') {
                        return;
                    }
                    var evt = el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input';
                    el.addEventListener(evt, function () {
                        var name = el.getAttribute('data-field');
                        var readout = panelBody.querySelector('[data-range-readout="' + name + '"]');
                        if (readout) {
                            readout.textContent = el.value;
                        }
                        applyField(name, el);
                        if (el.getAttribute('data-field') === 'html') {
                            var preview = panelBody.querySelector('[data-html-preview]');
                            if (preview) {
                                preview.innerHTML = sanitizePreviewHtml(el.value);
                            }
                        }
                    });
                });
                panelBody.querySelectorAll('[data-color-sync]').forEach(function (picker) {
                    picker.addEventListener('input', function () {
                        var name = picker.getAttribute('data-color-sync');
                        var text = panelBody.querySelector('[data-field="' + name + '"]');
                        if (text) {
                            text.value = picker.value;
                            applyField(name, text);
                        }
                    });
                });
                panelBody.querySelectorAll('[data-preset-field]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var name = btn.getAttribute('data-preset-field');
                        var val = btn.getAttribute('data-preset-value') || '';
                        var input = panelBody.querySelector('[data-field="' + name + '"]');
                        if (!input) {
                            return;
                        }
                        input.value = val;
                        var pair = panelBody.querySelector('[data-color-sync="' + name + '"]');
                        if (pair && /^#[0-9a-fA-F]{6}$/.test(val)) {
                            pair.value = val;
                        }
                        applyField(name, input);
                        if (name === 'padding' || name === 'margin') {
                            syncSpacingInputs(name, val);
                        }
                    });
                });
                panelBody.querySelectorAll('[data-spacing-side]').forEach(function (el) {
                    el.addEventListener('input', function () {
                        var name = el.getAttribute('data-spacing-field');
                        var side = el.getAttribute('data-spacing-side');
                        var node = getSelectedNode();
                        if (!node || !name || !side) {
                            return;
                        }
                        var kind = isModSel() ? 'design' : 'settings';
                        var sides = parseSpacingSides(styleFieldValue(node, kind, name));
                        sides[side] = el.value;
                        var joined = joinSpacingSides(sides);
                        var hidden = panelBody.querySelector('[data-field="' + name + '"]');
                        if (hidden) {
                            hidden.value = joined;
                            applyField(name, hidden);
                        }
                    });
                });
                panelBody.querySelectorAll('[data-slide-field]').forEach(function (el) {
                    var evt = el.tagName === 'SELECT' ? 'change' : 'input';
                    el.addEventListener(evt, function () {
                        applySlideField(Number(el.getAttribute('data-slide-idx')), el.getAttribute('data-slide-field'), el);
                    });
                });
                panelBody.querySelectorAll('[data-acc-field]').forEach(function (el) {
                    if (el.isContentEditable) {
                        return;
                    }
                    el.addEventListener('input', function () {
                        applyAccordionField(Number(el.getAttribute('data-acc-idx')), el.getAttribute('data-acc-field'), el);
                    });
                });
                panelBody.querySelectorAll('[data-tabs-field]').forEach(function (el) {
                    if (el.isContentEditable) {
                        return;
                    }
                    el.addEventListener('input', function () {
                        applyRepeatableField('tabs', 'tabs', Number(el.getAttribute('data-tabs-idx')), el.getAttribute('data-tabs-field'), el);
                    });
                });
                panelBody.querySelectorAll('[data-ilist-field]').forEach(function (el) {
                    el.addEventListener('input', function () {
                        applyRepeatableField('icon_list', 'ilist', Number(el.getAttribute('data-ilist-idx')), el.getAttribute('data-ilist-field'), el);
                    });
                });
                panelBody.querySelectorAll('[data-gal-field]').forEach(function (el) {
                    var evt = el.tagName === 'SELECT' ? 'change' : 'input';
                    el.addEventListener(evt, function () {
                        applyRepeatableField('gallery', 'gal', Number(el.getAttribute('data-gal-idx')), el.getAttribute('data-gal-field'), el);
                    });
                });
                panelBody.querySelectorAll('[data-tml-field]').forEach(function (el) {
                    var evt = el.tagName === 'SELECT' ? 'change' : 'input';
                    el.addEventListener(evt, function () {
                        applyRepeatableField('testimonial', 'tml', Number(el.getAttribute('data-tml-idx')), el.getAttribute('data-tml-field'), el);
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
                        if (!preset) {
                            return;
                        }
                        var row = getSelectedNode();
                        if (!row) {
                            return;
                        }
                        if (!(selection.kind === 'row' || (selection.kind === 'module' && row.type === 'inner_row'))) {
                            return;
                        }
                        applyColumnLayout(row, preset.widths.slice());
                        setStatus('Columns: ' + preset.label);
                        noteLayoutChange();
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
                        modulePickTarget = {
                            si: Number(wrap.getAttribute('data-si')),
                            ri: Number(wrap.getAttribute('data-ri')),
                            ci: Number(wrap.getAttribute('data-ci')),
                            after: Number(wrap.getAttribute('data-after')),
                            mi: Number(wrap.getAttribute('data-mi')),
                            ici: Number(wrap.getAttribute('data-ici')),
                            afterImi: Number(wrap.getAttribute('data-after-imi'))
                        };
                        insertModule(
                            type,
                            Number(wrap.getAttribute('data-si')),
                            Number(wrap.getAttribute('data-ri')),
                            Number(wrap.getAttribute('data-ci')),
                            Number(wrap.getAttribute('data-after'))
                        );
                    });
                });
                var filter = panelBody.querySelector('[data-mod-filter]');
                if (filter) {
                    filter.addEventListener('input', function () {
                        var q = String(filter.value || '').toLowerCase().trim();
                        panelBody.querySelectorAll('[data-insert-mod]').forEach(function (btn) {
                            var hay = (btn.textContent || '').toLowerCase();
                            btn.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
                        });
                    });
                }
            
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
                        noteLayoutChange();
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
                        noteLayoutChange();
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
                        noteLayoutChange();
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
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                });
            
                var addAccBtn = panelBody.querySelector('[data-acc-add]');
                if (addAccBtn) {
                    addAccBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== 'accordion') {
                            return;
                        }
                        node.data = node.data || {};
                        ensureAccordionItems(node.data);
                        if (node.data.items.length >= 12) {
                            setStatus('Maximum 12 accordion items', true);
                            return;
                        }
                        node.data.items.push(emptyAccordionItem());
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                }
                panelBody.querySelectorAll('[data-acc-del]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== 'accordion') {
                            return;
                        }
                        var idx = Number(btn.getAttribute('data-acc-del'));
                        ensureAccordionItems(node.data);
                        if (node.data.items.length <= 1) {
                            node.data.items[0] = emptyAccordionItem();
                        } else {
                            node.data.items.splice(idx, 1);
                        }
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                });
                panelBody.querySelectorAll('[data-acc-up]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== 'accordion') {
                            return;
                        }
                        var idx = Number(btn.getAttribute('data-acc-up'));
                        ensureAccordionItems(node.data);
                        if (idx <= 0) {
                            return;
                        }
                        var tmp = node.data.items[idx - 1];
                        node.data.items[idx - 1] = node.data.items[idx];
                        node.data.items[idx] = tmp;
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                });
                panelBody.querySelectorAll('[data-acc-down]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== 'accordion') {
                            return;
                        }
                        var idx = Number(btn.getAttribute('data-acc-down'));
                        ensureAccordionItems(node.data);
                        if (idx >= node.data.items.length - 1) {
                            return;
                        }
                        var tmp = node.data.items[idx + 1];
                        node.data.items[idx + 1] = node.data.items[idx];
                        node.data.items[idx] = tmp;
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                });
            
                bindRepeatableList('tabs', 'tabs', ensureTabsItems, emptyTabsItem, 'tabs');
                bindRepeatableList('icon_list', 'ilist', ensureIconListItems, emptyIconListItem, 'icon list items');
                bindRepeatableList('gallery', 'gal', ensureGalleryItems, emptyGalleryItem, 'gallery images');
                bindRepeatableList('testimonial', 'tml', ensureTestimonialItems, emptyTestimonialItem, 'testimonials');
            
                panelBody.querySelectorAll('[data-upload-btn]').forEach(function (uploadBtn) {
                    if (!global.CmsMediaPicker) {
                        return;
                    }
                    uploadBtn.addEventListener('click', function () {
                        var slideIdxAttr = uploadBtn.getAttribute('data-slide-idx');
                        var slideIdx = slideIdxAttr !== null && slideIdxAttr !== '' ? Number(slideIdxAttr) : null;
                        var galIdxAttr = uploadBtn.getAttribute('data-gal-idx');
                        var galIdx = galIdxAttr !== null && galIdxAttr !== '' ? Number(galIdxAttr) : null;
                        var tmlIdxAttr = uploadBtn.getAttribute('data-tml-idx');
                        var tmlIdx = tmlIdxAttr !== null && tmlIdxAttr !== '' ? Number(tmlIdxAttr) : null;
                        var rowIdx = slideIdx !== null ? slideIdx : (galIdx !== null ? galIdx : tmlIdx);
                        var rowAttr = slideIdx !== null ? 'data-slide-idx' : (galIdx !== null ? 'data-gal-idx' : (tmlIdx !== null ? 'data-tml-idx' : ''));
                        var target = uploadBtn.getAttribute('data-upload-target');
                        var fileInput = rowIdx !== null
                            ? panelBody.querySelector('[data-upload-file][' + rowAttr + '="' + rowIdx + '"]')
                            : (target
                                ? panelBody.querySelector('[data-upload-file][data-upload-target="' + target + '"]')
                                : panelBody.querySelector('[data-upload-file]:not([data-slide-idx]):not([data-gal-idx]):not([data-tml-idx])'));
                        if (!fileInput) {
                            fileInput = panelBody.querySelector('[data-upload-file]');
                        }
                        var f = fileInput && fileInput.files && fileInput.files[0];
                        if (!f) {
                            setStatus('Choose a file first', true);
                            return;
                        }
                        var st = rowIdx !== null
                            ? panelBody.querySelector('[data-upload-status][' + rowAttr + '="' + rowIdx + '"]')
                            : (target
                                ? panelBody.querySelector('[data-upload-status][data-upload-target="' + target + '"]')
                                : panelBody.querySelector('[data-upload-status]:not([data-slide-idx]):not([data-gal-idx]):not([data-tml-idx])'));
                        if (st) {
                            st.textContent = 'Uploading…';
                        }
                        global.CmsMediaPicker.uploadFile(f).then(function (item) {
                            mediaList.unshift(item);
                            var node = getSelectedNode();
                            var target = uploadBtn.getAttribute('data-upload-target');
                            if (node && target === 'bg') {
                                node.settings = node.settings || {};
                                node.settings.bg_media_id = item.id;
                                node.settings.bg_image = item.share_url || item.url || '';
                            } else if (node && node.data) {
                                if (slideIdx !== null && node.type === 'carousel') {
                                    ensureCarouselSlides(node.data);
                                    if (node.data.slides[slideIdx]) {
                                        node.data.slides[slideIdx].media_id = item.id;
                                        node.data.slides[slideIdx].url = item.share_url || item.url || '';
                                    }
                                } else if (galIdx !== null && node.type === 'gallery') {
                                    ensureGalleryItems(node.data);
                                    if (node.data.items[galIdx]) {
                                        node.data.items[galIdx].media_id = item.id;
                                        node.data.items[galIdx].url = item.share_url || item.url || '';
                                    }
                                } else if (tmlIdx !== null && node.type === 'testimonial') {
                                    ensureTestimonialItems(node.data);
                                    if (node.data.items[tmlIdx]) {
                                        node.data.items[tmlIdx].media_id = item.id;
                                        node.data.items[tmlIdx].url = item.share_url || item.url || '';
                                    }
                                } else {
                                    node.data.media_id = item.id;
                                    node.data.url = item.share_url || item.url || '';
                                }
                            }
                            if (st) {
                                st.textContent = 'Done';
                            }
                            noteLayoutChange();
                            if (target === 'bg') {
                                refreshSelectedLiveCss(node);
                                scheduleHistoryCommit(true);
                                renderPanel();
                            } else {
                                render();
                                renderPanel();
                            }
                        }).catch(function (err) {
                            if (st) {
                                st.textContent = '';
                            }
                            setStatus(err.message || 'Upload failed', true);
                        });
                    });
                });
                if (typeof bindRichEditors === 'function') {
                    bindRichEditors(panelBody, function (ed, html) {
                        var accIdx = ed.getAttribute('data-acc-idx');
                        var accField = ed.getAttribute('data-acc-field');
                        if (accIdx !== null && accIdx !== '' && accField) {
                            applyAccordionField(Number(accIdx), accField, html);
                            return;
                        }
                        var tabsIdx = ed.getAttribute('data-tabs-idx');
                        var tabsField = ed.getAttribute('data-tabs-field');
                        if (tabsIdx !== null && tabsIdx !== '' && tabsField) {
                            applyRepeatableField('tabs', 'tabs', Number(tabsIdx), tabsField, html);
                            return;
                        }
                        var name = ed.getAttribute('data-rich-for');
                        if (name) {
                            applyField(name, { value: html, type: 'text', tagName: 'TEXTAREA' });
                        }
                    });
                }
            }
            
            function applySlideField(slideIdx, name, el) {
                var node = getSelectedNode();
                if (!node || !isModSel() || node.type !== 'carousel') {
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
                noteLayoutChange();
                scheduleCanvasRender(el.tagName === 'SELECT');
            }
            
            function applyAccordionField(idx, name, el) {
                var node = getSelectedNode();
                if (!node || !isModSel() || node.type !== 'accordion') {
                    return;
                }
                node.data = node.data || {};
                ensureAccordionItems(node.data);
                if (!node.data.items[idx]) {
                    return;
                }
                node.data.items[idx][name] = (el && typeof el === 'object' && 'value' in el) ? el.value : el;
                noteLayoutChange();
                if (patchSelectedModulePreview()) {
                    scheduleHistoryCommit(false);
                } else {
                    scheduleCanvasRender(false);
                }
            }
            
            function bindRepeatableList(type, prefix, ensureFn, emptyFn, label) {
                var addBtn = panelBody.querySelector('[data-' + prefix + '-add]');
                if (addBtn) {
                    addBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== type) {
                            return;
                        }
                        node.data = node.data || {};
                        ensureFn(node.data);
                        if (node.data.items.length >= 12) {
                            setStatus('Maximum 12 ' + label, true);
                            return;
                        }
                        node.data.items.push(emptyFn());
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                }
                panelBody.querySelectorAll('[data-' + prefix + '-del]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== type) {
                            return;
                        }
                        var idx = Number(btn.getAttribute('data-' + prefix + '-del'));
                        ensureFn(node.data);
                        if (node.data.items.length <= 1) {
                            node.data.items[0] = emptyFn();
                        } else {
                            node.data.items.splice(idx, 1);
                        }
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                });
                panelBody.querySelectorAll('[data-' + prefix + '-up]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== type) {
                            return;
                        }
                        var idx = Number(btn.getAttribute('data-' + prefix + '-up'));
                        ensureFn(node.data);
                        if (idx <= 0) {
                            return;
                        }
                        var tmp = node.data.items[idx - 1];
                        node.data.items[idx - 1] = node.data.items[idx];
                        node.data.items[idx] = tmp;
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                });
                panelBody.querySelectorAll('[data-' + prefix + '-down]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var node = getSelectedNode();
                        if (!node || node.type !== type) {
                            return;
                        }
                        var idx = Number(btn.getAttribute('data-' + prefix + '-down'));
                        ensureFn(node.data);
                        if (idx >= node.data.items.length - 1) {
                            return;
                        }
                        var tmp = node.data.items[idx + 1];
                        node.data.items[idx + 1] = node.data.items[idx];
                        node.data.items[idx] = tmp;
                        noteLayoutChange();
                        render();
                        renderPanel();
                    });
                });
            }
            
            function applyRepeatableField(type, prefix, idx, name, el) {
                var node = getSelectedNode();
                if (!node || !isModSel() || node.type !== type) {
                    return;
                }
                node.data = node.data || {};
                if (type === 'tabs') {
                    ensureTabsItems(node.data);
                } else if (type === 'icon_list') {
                    ensureIconListItems(node.data);
                } else if (type === 'gallery') {
                    ensureGalleryItems(node.data);
                } else if (type === 'testimonial') {
                    ensureTestimonialItems(node.data);
                }
                if (!node.data.items[idx]) {
                    return;
                }
                var val = (el && typeof el === 'object' && 'value' in el) ? el.value : el;
                if (name === 'media_id') {
                    node.data.items[idx].media_id = val ? Number(val) : null;
                    var m = mediaById(node.data.items[idx].media_id);
                    if (m) {
                        node.data.items[idx].url = m.share_url || m.url || node.data.items[idx].url || '';
                    }
                } else {
                    node.data.items[idx][name] = val;
                }
                noteLayoutChange();
                if (patchSelectedModulePreview()) {
                    scheduleHistoryCommit(false);
                } else {
                    scheduleCanvasRender(false);
                }
            }
            
            function applyField(name, el) {
                var node = getSelectedNode();
                if (!node) {
                    return;
                }
                var val = el.type === 'checkbox' ? el.checked : el.value;
            
                if (isModSel()) {
                    if (isPositionField(name)) {
                        writeStyleValue(node, 'design', name, val);
                    } else if (activeTab === 'content') {
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
                        } else if (name === 'columns') {
                            var cols = Number(val) || 3;
                            if (cols < 2) {
                                cols = 2;
                            }
                            if (cols > 4) {
                                cols = 4;
                            }
                            node.data.columns = cols;
                        } else if (name === 'autoplay' || name === 'show_arrows' || name === 'show_dots' || name === 'new_tab' || name === 'first_open') {
                            node.data[name] = !!val;
                        } else {
                            node.data[name] = val;
                        }
                    } else if (activeTab === 'design') {
                        writeStyleValue(node, 'design', name, val);
                    } else {
                        node.advanced = node.advanced || {};
                        node.advanced[name] = val;
                    }
                } else if (selection.kind === 'section' && activeTab === 'content' && name === 'type') {
                    node.type = val;
                } else if (isColSel() && name === 'width') {
                    var w = Number(val) || 12;
                    if (w < 1) {
                        w = 1;
                    }
                    if (w > 12) {
                        w = 12;
                    }
                    node.width = w;
                } else if (name === 'bg_media_id' || name === 'bg_image') {
                    node.settings = node.settings || {};
                    if (name === 'bg_media_id') {
                        var bgId = val ? Number(val) : 0;
                        if (bgId > 0) {
                            node.settings.bg_media_id = bgId;
                            var bgMedia = mediaById(bgId);
                            if (bgMedia) {
                                node.settings.bg_image = bgMedia.share_url || bgMedia.url || node.settings.bg_image || '';
                            }
                        } else {
                            delete node.settings.bg_media_id;
                        }
                    } else {
                        node.settings.bg_image = val;
                    }
                } else if (name === 'bg_video_url') {
                    node.settings = node.settings || {};
                    var vu = String(val || '').trim();
                    if (vu) {
                        node.settings.bg_video_url = vu;
                    } else {
                        delete node.settings.bg_video_url;
                    }
                } else if (name === 'shape_top' || name === 'shape_bottom') {
                    node.settings = node.settings || {};
                    var sk = String(val || '').toLowerCase();
                    if (sk === 'wave' || sk === 'tilt' || sk === 'curve' || sk === 'triangle') {
                        node.settings[name] = sk;
                    } else {
                        delete node.settings[name];
                    }
                } else if (name === 'shape_top_flip' || name === 'shape_bottom_flip' || name === 'col_reverse_mobile') {
                    node.settings = node.settings || {};
                    if (val) {
                        node.settings[name] = true;
                    } else {
                        delete node.settings[name];
                    }
                } else if (name === 'css_class') {
                    node.settings = node.settings || {};
                    node.settings.css_class = val;
                } else {
                    writeStyleValue(node, 'settings', name, val);
                }
                var live = el.tagName === 'TEXTAREA' || el.type === 'text' || el.type === 'number' || el.type === 'range';
                noteLayoutChange();
                updateDirtyUi();
                if (isLiveStyleField(name)) {
                    refreshSelectedLiveCss(node);
                    patchLiveChrome(name, val);
                    scheduleHistoryCommit(el.tagName === 'SELECT' || el.type === 'checkbox');
                } else if (isModSel() && activeTab === 'content' && patchSelectedModulePreview()) {
                    scheduleHistoryCommit(el.tagName === 'SELECT' || el.type === 'checkbox');
                } else {
                    scheduleCanvasRender(!live);
                }
                if (el.type === 'text' && panelBody) {
                    var pair = panelBody.querySelector('[data-color-sync="' + name + '"]');
                    if (pair && /^#[0-9a-fA-F]{6}$/.test(String(el.value || ''))) {
                        pair.value = el.value;
                    }
                }
            }
            
            ctx.fieldMeta = fieldMeta;
            ctx.field = field;
            ctx.colorFieldRow = colorFieldRow;
            ctx.presetRow = presetRow;
            ctx.colorTokenRow = colorTokenRow;
            ctx.deviceStyleNoticeHtml = deviceStyleNoticeHtml;
            ctx.designStateToggleHtml = designStateToggleHtml;
            ctx.backgroundImageFields = backgroundImageFields;
            ctx.moduleLead = moduleLead;
            ctx.mediaPreviewHtml = mediaPreviewHtml;
            ctx.renderCrumbs = renderCrumbs;
            ctx.renderPanel = renderPanel;
            ctx.renderModuleContent = renderModuleContent;
            ctx.mediaField = mediaField;
            ctx.emptyCarouselSlide = emptyCarouselSlide;
            ctx.ensureCarouselSlides = ensureCarouselSlides;
            ctx.carouselFields = carouselFields;
            ctx.emptyAccordionItem = emptyAccordionItem;
            ctx.ensureAccordionItems = ensureAccordionItems;
            ctx.accordionFields = accordionFields;
            ctx.accField = accField;
            ctx.emptyTabsItem = emptyTabsItem;
            ctx.ensureTabsItems = ensureTabsItems;
            ctx.tabsFields = tabsFields;
            ctx.emptyIconListItem = emptyIconListItem;
            ctx.ensureIconListItems = ensureIconListItems;
            ctx.iconListFields = iconListFields;
            ctx.emptyGalleryItem = emptyGalleryItem;
            ctx.ensureGalleryItems = ensureGalleryItems;
            ctx.galleryFields = galleryFields;
            ctx.emptyTestimonialItem = emptyTestimonialItem;
            ctx.ensureTestimonialItems = ensureTestimonialItems;
            ctx.testimonialFields = testimonialFields;
            ctx.mediaItemField = mediaItemField;
            ctx.repeatField = repeatField;
            ctx.bindRepeatableList = bindRepeatableList;
            ctx.applyRepeatableField = applyRepeatableField;
            ctx.applyAccordionField = applyAccordionField;
            ctx.slideField = slideField;
            ctx.bindPanelFields = bindPanelFields;
            ctx.applySlideField = applySlideField;
            ctx.applyField = applyField;
        }
    });
})();
