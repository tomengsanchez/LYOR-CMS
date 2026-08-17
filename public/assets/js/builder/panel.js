/**
 * Visual layout builder — inspector fields and carousel.
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
                if (selection.kind === 'module' && activeTab === 'design' && designState === 'hover') {
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
                    patchLiveCssForNode(node, selection.kind === 'module' ? 'design' : 'settings');
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

            function moduleLead(type) {
                var meta = MODULE_META[type];
                if (!meta || !meta.hint) {
                    return '';
                }
                return '<p class="cms-lb-panel-lead">' + esc(meta.hint) + '</p>';
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
                if (selection.kind === 'module') {
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
            
                var title = selection.kind === 'module'
                    ? (moduleTypes[node.type] || node.type)
                    : (selection.kind.charAt(0).toUpperCase() + selection.kind.slice(1));
                if (modulePickTarget && selection.kind === 'column') {
                    title = 'Add module';
                }
                if (panelTitle) {
                    panelTitle.textContent = title;
                }
                renderCrumbs();
            
                var html = '';
                if (activeTab === 'content') {
                    if (selection.kind === 'module') {
                        html += renderModuleContent(node);
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
                    } else if (selection.kind === 'column') {
                        var pick = modulePickTarget || {
                            si: selection.sectionIdx,
                            ri: selection.rowIdx,
                            ci: selection.colIdx
                        };
                        html += renderModuleTypePicker(pick.si, pick.ri, pick.ci);
                        html += '<p class="cms-lb-field-group">Column size</p>';
                        html += deviceStyleNoticeHtml(node);
                        var colW = Number(node.width) || 12;
                        var parentRow = layout.sections[selection.sectionIdx] && layout.sections[selection.sectionIdx].rows
                            && layout.sections[selection.sectionIdx].rows[selection.rowIdx];
                        var rowSum = parentRow ? rowWidths(parentRow).reduce(function (a, b) { return a + b; }, 0) : colW;
                        html += field('Width (1–12 grid)', 'width', colW, 'range', {
                            min: 1,
                            max: 12,
                            hint: 'Bootstrap columns. Drag the blue edge between two columns on the canvas to split their widths.'
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
                    } else {
                        html += '<p class="small text-muted mb-0">Use Design / Advanced for styling. Add modules from the column chrome.</p>';
                    }
                } else if (activeTab === 'design') {
                    html += '<p class="cms-lb-panel-lead">Appearance for this ' + esc(selection.kind) + '. Colors need #hex (3–8 digits). Spacing needs px, rem, em, or %.</p>';
                    html += styleClipBarHtml();
                    if (selection.kind === 'module') {
                        html += designStateToggleHtml();
                    }
                    html += deviceStyleNoticeHtml(node);
                    if (selection.kind === 'module') {
                        html += field('Text align', 'text_align', styleFieldValue(node, 'design', 'text_align') || '', 'select', styleOpts(node, 'design', 'text_align', {
                            choices: [
                                { v: '', l: 'Default' },
                                { v: 'left', l: 'Left' },
                                { v: 'center', l: 'Center' },
                                { v: 'right', l: 'Right' }
                            ]
                        }));
                        html += field('Text color', 'text_color', styleFieldValue(node, 'design', 'text_color') || '', 'color', styleOpts(node, 'design', 'text_color'));
                        html += colorTokenRow('text_color');
                        html += field('Background', 'bg_color', styleFieldValue(node, 'design', 'bg_color') || '', 'color', styleOpts(node, 'design', 'bg_color'));
                        html += colorTokenRow('bg_color');
                        html += spacingSidesHtml(node, 'design', 'padding', 'Padding');
                        html += presetRow('padding', [
                            { v: '', l: 'Default' },
                            { v: '0px', l: '0' },
                            { v: '0.5rem', l: 'S' },
                            { v: '1rem', l: 'M' },
                            { v: '2rem', l: 'L' }
                        ]);
                        html += spacingSidesHtml(node, 'design', 'margin', 'Margin');
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
                        html += chromeStyleFields(node, 'design');
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
                    }
                } else {
                    html += '<p class="cms-lb-panel-lead">Visibility is previewed with Desktop / Tablet / Mobile. Hide classes apply on the public site.</p>';
                    if (selection.kind === 'module') {
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
            
            function renderModuleContent(mod) {
                var d = mod.data || {};
                var html = moduleLead(mod.type);
                var meta = moduleCatalog[mod.type] || {};
                if (meta.custom === 'carousel') {
                    html += carouselFields(d);
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
                        var kind = selection.kind === 'module' ? 'design' : 'settings';
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
            
                panelBody.querySelectorAll('[data-upload-btn]').forEach(function (uploadBtn) {
                    if (!global.CmsMediaPicker) {
                        return;
                    }
                    uploadBtn.addEventListener('click', function () {
                        var slideIdxAttr = uploadBtn.getAttribute('data-slide-idx');
                        var slideIdx = slideIdxAttr !== null && slideIdxAttr !== '' ? Number(slideIdxAttr) : null;
                        var target = uploadBtn.getAttribute('data-upload-target');
                        var fileInput = slideIdx !== null
                            ? panelBody.querySelector('[data-upload-file][data-slide-idx="' + slideIdx + '"]')
                            : (target
                                ? panelBody.querySelector('[data-upload-file][data-upload-target="' + target + '"]')
                                : panelBody.querySelector('[data-upload-file]:not([data-slide-idx])'));
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
                            : (target
                                ? panelBody.querySelector('[data-upload-status][data-upload-target="' + target + '"]')
                                : panelBody.querySelector('[data-upload-status]:not([data-slide-idx])'));
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
                noteLayoutChange();
                scheduleCanvasRender(el.tagName === 'SELECT');
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
                        } else if (name === 'autoplay' || name === 'show_arrows' || name === 'show_dots' || name === 'new_tab') {
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
                } else if (selection.kind === 'column' && name === 'width') {
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
                } else if (selection.kind === 'module' && activeTab === 'content' && patchSelectedModulePreview()) {
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
            ctx.slideField = slideField;
            ctx.bindPanelFields = bindPanelFields;
            ctx.applySlideField = applySlideField;
            ctx.applyField = applyField;
        }
    });
})();
