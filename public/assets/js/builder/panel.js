/**
 * Visual layout builder — inspector fields and carousel.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function fieldMeta(opts) {
                if (Array.isArray(opts)) {
                    return { choices: opts, hint: '', placeholder: '', rows: 4, min: 1, max: 12, step: 1 };
                }
                opts = opts || {};
                return {
                    choices: opts.choices || [],
                    hint: opts.hint || '',
                    placeholder: opts.placeholder || '',
                    rows: opts.rows || 4,
                    min: opts.min != null ? opts.min : 1,
                    max: opts.max != null ? opts.max : 12,
                    step: opts.step != null ? opts.step : 1
                };
            }
            
            function field(label, name, value, type, opts) {
                type = type || 'text';
                var meta = fieldMeta(opts);
                var id = 'bf_' + (selection.kind || 'x') + '_' + String(name).replace(/[^a-z0-9_]/gi, '_');
                if (type === 'checkbox') {
                    return '<div class="form-check">'
                        + '<input type="checkbox" class="form-check-input" id="' + id + '" data-field="' + esc(name) + '"' + (value ? ' checked' : '') + '>'
                        + '<label class="form-check-label" for="' + id + '">' + esc(label) + '</label>'
                        + (meta.hint ? '<div class="form-text">' + esc(meta.hint) + '</div>' : '')
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
                return html;
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
                        html += field('Section type', 'type', node.type || 'regular', 'select', [
                            { v: 'regular', l: 'Regular (contained)' },
                            { v: 'fullwidth', l: 'Full width' }
                        ]);
                        html += field('Background color', 'bg_color', (node.settings && node.settings.bg_color) || '', 'color');
                        html += field('Padding', 'padding', (node.settings && node.settings.padding) || '', 'text', {
                            hint: 'CSS values only, e.g. 2rem or 24px. Invalid values are cleared on save.',
                            placeholder: '2rem'
                        });
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
                        html += field('Min height', 'min_height', (node.settings && node.settings.min_height) || '', 'text', {
                            placeholder: '240px',
                            hint: 'Minimum height (px, rem, %, or vh). Content can grow taller.'
                        });
                        html += presetRow('min_height', [
                            { v: '', l: 'Auto' },
                            { v: '160px', l: 'S' },
                            { v: '240px', l: 'M' },
                            { v: '360px', l: 'L' },
                            { v: '50vh', l: 'Half screen' }
                        ]);
                        html += field('Vertical align', 'valign', (node.settings && node.settings.valign) || '', 'select', [
                            { v: '', l: 'Top (default)' },
                            { v: 'center', l: 'Middle' },
                            { v: 'bottom', l: 'Bottom' }
                        ]);
                        html += '<p class="small text-muted mt-2 mb-0">Or select the parent <strong>Row</strong> for equal splits. Drag the ⋮⋮ handle to reorder.</p>';
                    } else if (selection.kind === 'row') {
                        html += renderColumnLayoutPicker(node, selection.sectionIdx, selection.rowIdx);
                    } else {
                        html += '<p class="small text-muted mb-0">Use Design / Advanced for styling. Add modules from the column chrome.</p>';
                    }
                } else if (activeTab === 'design') {
                    html += '<p class="cms-lb-panel-lead">Appearance for this ' + esc(selection.kind) + '. Colors need #hex (3–8 digits). Spacing needs px, rem, em, or %.</p>';
                    if (selection.kind === 'module') {
                        var des = node.design || {};
                        html += field('Text align', 'text_align', des.text_align || '', 'select', [
                            { v: '', l: 'Default' },
                            { v: 'left', l: 'Left' },
                            { v: 'center', l: 'Center' },
                            { v: 'right', l: 'Right' }
                        ]);
                        html += field('Text color', 'text_color', des.text_color || '', 'color');
                        html += field('Background', 'bg_color', des.bg_color || '', 'color');
                        html += field('Padding', 'padding', des.padding || '', 'text', { placeholder: '1rem' });
                        html += presetRow('padding', [
                            { v: '', l: 'Default' },
                            { v: '0px', l: '0' },
                            { v: '0.5rem', l: 'S' },
                            { v: '1rem', l: 'M' },
                            { v: '2rem', l: 'L' }
                        ]);
                        html += field('Margin', 'margin', des.margin || '', 'text', { placeholder: '0 0 1rem' });
                        html += field('Font size', 'font_size', des.font_size || '', 'text', { placeholder: '1.25rem' });
                        html += presetRow('font_size', [
                            { v: '', l: 'Default' },
                            { v: '0.9rem', l: 'S' },
                            { v: '1rem', l: 'M' },
                            { v: '1.5rem', l: 'L' },
                            { v: '2rem', l: 'XL' }
                        ]);
                    } else {
                        var set = node.settings || {};
                        html += field('Background', 'bg_color', set.bg_color || '', 'color');
                        html += field('Padding', 'padding', set.padding || '', 'text', { placeholder: '2rem' });
                        html += presetRow('padding', [
                            { v: '', l: 'Default' },
                            { v: '1rem', l: 'S' },
                            { v: '2rem', l: 'M' },
                            { v: '3rem', l: 'L' }
                        ]);
                        html += field('Min height', 'min_height', set.min_height || '', 'text', { placeholder: '200px' });
                        html += presetRow('min_height', [
                            { v: '', l: 'Auto' },
                            { v: '160px', l: 'S' },
                            { v: '240px', l: 'M' },
                            { v: '360px', l: 'L' }
                        ]);
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
                switch (mod.type) {
                    case 'heading':
                        html += field('Text', 'text', d.text, 'text');
                        html += field('Level', 'level', d.level || 2, 'select', {
                            choices: [1, 2, 3, 4, 5, 6].map(function (n) {
                                return { v: n, l: 'H' + n };
                            }),
                            hint: 'H1 is for the page’s main heading. The admin title is not repeated as an H1 on the public page.'
                        });
                        break;
                    case 'text':
                        html += field('Text', 'text', d.text, 'textarea', {
                            rows: 8,
                            hint: 'Line breaks become paragraphs. For lists, bold, or embeds, use Custom HTML.'
                        });
                        break;
                    case 'image':
                        html += mediaField(d);
                        html += field('Alt text', 'alt', d.alt, 'text', { hint: 'Describe the image for accessibility and SEO.' });
                        html += field('Caption', 'caption', d.caption, 'textarea', {
                            rows: 2,
                            hint: 'Shown under the image. Use Image URL below for the photo, not this field.'
                        });
                        html += field('Link URL', 'link', d.link || '', 'text', {
                            placeholder: '/about or https://',
                            hint: 'Optional. Makes the image clickable.'
                        });
                        break;
                    case 'button':
                        html += field('Label', 'label', d.label, 'text');
                        html += field('URL', 'url', d.url, 'text', { placeholder: '/about or https://' });
                        html += field('Style', 'style', d.style || 'primary', 'select', btnStyleChoices);
                        html += field('Open in new tab', 'new_tab', !!d.new_tab, 'checkbox');
                        break;
                    case 'cta':
                        html += field('Title', 'title', d.title, 'text');
                        html += field('Text', 'text', d.text, 'textarea', { rows: 3 });
                        html += field('Button label', 'label', d.label, 'text');
                        html += field('Button URL', 'url', d.url, 'text', { placeholder: '/contact or https://' });
                        html += field('Button style', 'style', d.style || 'primary', 'select', btnStyleChoices);
                        html += field('Open in new tab', 'new_tab', !!d.new_tab, 'checkbox');
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
                        html += '<p class="form-text">Set Design → Text color to tint the line.</p>';
                        break;
                    case 'html':
                        html += field('HTML', 'html', d.html, 'textarea', {
                            rows: 10,
                            hint: 'script, iframe, form, and input tags are stripped on the public site.'
                        });
                        html += '<p class="cms-lb-field-group">Preview</p><div class="cms-lb-html-preview" data-html-preview>'
                            + sanitizePreviewHtml(d.html || '') + '</div>';
                        break;
                    case 'blurb':
                        html += field('Title', 'title', d.title, 'text');
                        html += field('Text', 'text', d.text, 'textarea', { rows: 3 });
                        html += field('Icon / emoji', 'icon', d.icon, 'text', {
                            hint: 'Used only when no image is selected. Image wins if both are set.'
                        });
                        html += mediaField(d);
                        html += field('Link URL', 'url', d.url, 'text', {
                            placeholder: '/page or https://',
                            hint: 'Optional. Wraps the title.'
                        });
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
                panelBody.querySelectorAll('[data-field]').forEach(function (el) {
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
                            noteLayoutChange();
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
                        node.design = node.design || {};
                        node.design[name] = val;
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
                } else {
                    node.settings = node.settings || {};
                    node.settings[name] = val;
                }
                var live = el.tagName === 'TEXTAREA' || el.type === 'text' || el.type === 'number' || el.type === 'range';
                noteLayoutChange();
                updateDirtyUi();
                scheduleCanvasRender(!live);
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
