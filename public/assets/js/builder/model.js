/**
 * Visual layout builder — ids, modules, column layouts, styles.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function uid() {
                return 'el_' + Math.random().toString(16).slice(2, 14);
            }
            
            function reindexCopy(node) {
                var copy = JSON.parse(JSON.stringify(node));
                (function walk(n) {
                    if (!n || typeof n !== 'object') {
                        return;
                    }
                    if (n.id) {
                        n.id = uid();
                    }
                    if (Array.isArray(n.rows)) {
                        n.rows.forEach(walk);
                    }
                    if (Array.isArray(n.columns)) {
                        n.columns.forEach(walk);
                    }
                    if (Array.isArray(n.modules)) {
                        n.modules.forEach(walk);
                    }
                })(copy);
                return copy;
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
                statusEl.classList.toggle('is-error', !!isError);
                statusEl.classList.toggle('is-ok', !isError);
                statusEl.style.color = '';
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
                        data = { label: 'Learn more', url: '#', style: 'primary', new_tab: false };
                        break;
                    case 'cta':
                        data = { title: 'Call to action', text: 'Supporting text.', label: 'Get started', url: '#', style: 'primary', new_tab: false };
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
                COLUMN_LAYOUTS.forEach(function (preset) {
                    var active = preset.id === current ? ' is-active' : '';
                    html += '<button type="button" class="cms-lb-col-picker-btn' + active + '" data-col-layout="' + esc(preset.id) + '" title="' + esc(preset.label) + '" aria-label="' + esc(preset.label) + '">';
                    html += '<span class="cms-lb-col-picker-bars">';
                    preset.widths.forEach(function (w) {
                        html += '<span class="cms-lb-col-picker-bar" style="flex:' + w + '"></span>';
                    });
                    html += '</span><span class="cms-lb-col-picker-caption">' + esc(preset.label) + '</span></button>';
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
                if (settings.min_height) {
                    parts.push('min-height:' + settings.min_height);
                }
                return parts.join(';');
            }
            
            function btnClassFromStyle(style) {
                if (style === 'secondary') {
                    return 'btn btn-secondary';
                }
                if (style === 'outline') {
                    return 'btn btn-outline-primary';
                }
                return 'btn btn-primary';
            }
            
            function canvasLink(url, cls, label) {
                return '<a href="' + esc(url || '#') + '" class="' + esc(cls) + '" data-cms-canvas-link="1">' + esc(label) + '</a>';
            }
            
            ctx.uid = uid;
            ctx.reindexCopy = reindexCopy;
            ctx.esc = esc;
            ctx.setStatus = setStatus;
            ctx.mediaById = mediaById;
            ctx.defaultModule = defaultModule;
            ctx.emptyColumn = emptyColumn;
            ctx.emptyRow = emptyRow;
            ctx.layoutIdFromWidths = layoutIdFromWidths;
            ctx.rowWidths = rowWidths;
            ctx.applyColumnLayout = applyColumnLayout;
            ctx.renderColumnLayoutPicker = renderColumnLayoutPicker;
            ctx.emptySection = emptySection;
            ctx.designStyle = designStyle;
            ctx.settingsStyle = settingsStyle;
            ctx.btnClassFromStyle = btnClassFromStyle;
            ctx.canvasLink = canvasLink;
            ctx.COLUMN_LAYOUTS = COLUMN_LAYOUTS;
        }
    });
})();
