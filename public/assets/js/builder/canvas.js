/**
 * Visual layout builder — canvas render and inline edit.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            var SHAPE_SVGS = {
                wave: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 60C150 150 350-20 600 60 850 140 1050-20 1200 60V120H0Z"/></svg>',
                tilt: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><polygon fill="currentColor" points="0,80 1200,0 1200,120 0,120"/></svg>',
                curve: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 120V40Q600 120 1200 40V120Z"/></svg>',
                triangle: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true"><polygon fill="currentColor" points="0,120 600,20 1200,120"/></svg>'
            };

            function sectionBgPreviewHtml(section) {
                var url = section && section.settings && section.settings.bg_video_url
                    ? String(section.settings.bg_video_url).trim()
                    : '';
                if (!url) {
                    return '';
                }
                return '<div class="cms-layout-section-bg cms-lb-section-bg-ph" aria-hidden="true"><span>Video background (preview)</span></div>';
            }

            function shapeDividerHtml(section, side) {
                var set = section && section.settings ? section.settings : {};
                var key = String(set['shape_' + side] || '').toLowerCase();
                if (!SHAPE_SVGS[key]) {
                    return '';
                }
                var h = String(set['shape_' + side + '_height'] || 'md').toLowerCase();
                if (h !== 'sm' && h !== 'md' && h !== 'lg') {
                    h = 'md';
                }
                var flip = set['shape_' + side + '_flip'] ? ' cms-shape--flip' : '';
                return '<div class="cms-shape cms-shape--' + side + ' cms-shape--' + key + ' cms-shape--' + h + flip + '" aria-hidden="true">'
                    + SHAPE_SVGS[key] + '</div>';
            }

            function modulePreviewHtml(mod) {
                var d = mod.data || {};
                var adv = mod.advanced || {};
                var mid = ensureNodeId(mod);
                var wrapCls = 'cms-layout-module cms-mod-' + esc(mod.type) + (elementCssClass(mid) ? ' ' + elementCssClass(mid) : '');
                if (adv.hide_mobile) {
                    wrapCls += ' cms-lb-hide-mobile';
                }
                if (adv.hide_desktop) {
                    wrapCls += ' cms-lb-hide-desktop';
                }
                var wrapOpen = '<div class="' + wrapCls + '"' + (mid ? ' data-el-id="' + esc(mid) + '"' : '') + '>';
                var wrapClose = '</div>';
                var inner = '';
                switch (mod.type) {
                    case 'heading': {
                        var lvl = Math.min(6, Math.max(1, Number(d.level) || 2));
                        inner = '<h' + lvl + ' class="cms-mod-heading" data-inline-field="text" contenteditable="true" spellcheck="true">' + esc(d.text || 'Heading') + '</h' + lvl + '>';
                        break;
                    }
                    case 'text':
                        inner = '<div class="cms-mod-text" data-inline-field="text" data-inline-html="1" contenteditable="true" spellcheck="true">'
                            + (typeof formatRichPreview === 'function' ? formatRichPreview(d.text) : esc(d.text || '').replace(/\n/g, '<br>'))
                            + '</div>';
                        break;
                    case 'image': {
                        var m = d.media_id ? mediaById(d.media_id) : null;
                        var src = (m && (m.preview || m.url)) || d.url || '';
                        if (src) {
                            var img = '<img src="' + esc(src) + '" alt="' + esc(d.alt || '') + '" class="img-fluid" style="max-height:240px">';
                            if (d.link) {
                                img = '<a href="' + esc(d.link) + '" class="cms-mod-image-link" data-cms-canvas-link="1">' + img + '</a>';
                            }
                            inner = '<figure class="cms-mod-image">' + img;
                            if (d.caption) {
                                inner += '<figcaption class="cms-mod-image-caption small text-muted mt-1">' + esc(d.caption).replace(/\n/g, '<br>') + '</figcaption>';
                            }
                            inner += '</figure>';
                        } else {
                            inner = '<div class="text-muted small p-3 border">No image selected — pick media or paste a URL in the panel.</div>';
                        }
                        break;
                    }
                    case 'button':
                        inner = canvasLink(d.url, btnClassFromStyle(d.style) + ' cms-mod-button', d.label || 'Button');
                        break;
                    case 'cta':
                        inner = '<div class="cms-mod-cta"><h3 class="cms-mod-cta-title">' + esc(d.title || '') + '</h3>'
                            + '<div class="cms-mod-cta-text">'
                            + (typeof formatRichPreview === 'function' ? formatRichPreview(d.text) : esc(d.text || '').replace(/\n/g, '<br>'))
                            + '</div>'
                            + canvasLink(d.url, btnClassFromStyle(d.style) + ' cms-mod-cta-btn', d.label || 'Go') + '</div>';
                        break;
                    case 'spacer':
                        inner = '<div class="cms-mod-spacer cms-mod-spacer--' + esc(d.size || 'md') + '" aria-hidden="true"></div>';
                        break;
                    case 'divider':
                        inner = '<hr class="cms-mod-divider cms-mod-divider--' + esc(d.style || 'solid') + '">';
                        break;
                    case 'html':
                        inner = '<div class="cms-mod-html cms-lb-html-preview">' + sanitizePreviewHtml(d.html || '') + '</div>';
                        break;
                    case 'blurb': {
                        var bm = d.media_id ? mediaById(d.media_id) : null;
                        var bsrc = (bm && (bm.preview || bm.url)) || '';
                        var mediaBit = '';
                        if (bsrc) {
                            mediaBit = '<img src="' + esc(bsrc) + '" alt="' + esc(d.title || '') + '" class="cms-mod-blurb-img img-fluid">';
                        } else if (d.icon) {
                            mediaBit = '<div class="cms-mod-blurb-icon">' + esc(d.icon) + '</div>';
                        }
                        var blurbTitle = '<h4 class="cms-mod-blurb-title">' + esc(d.title || '') + '</h4>';
                        if (d.url) {
                            blurbTitle = '<a href="' + esc(d.url) + '" data-cms-canvas-link="1">' + blurbTitle + '</a>';
                        }
                        inner = '<div class="cms-mod-blurb">' + mediaBit + blurbTitle
                            + '<div class="cms-mod-blurb-text">'
                            + (typeof formatRichPreview === 'function' ? formatRichPreview(d.text) : esc(d.text || '').replace(/\n/g, '<br>'))
                            + '</div></div>';
                        break;
                    }
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
                            inner = '<div class="text-muted small p-3 border">Carousel — add slide images in the panel.</div>';
                        }
                        break;
                    }
                    case 'accordion': {
                        var accItems = Array.isArray(d.items) ? d.items : [];
                        var accRows = '';
                        accItems.forEach(function (item) {
                            var t = (item && item.title) ? String(item.title) : 'Item';
                            accRows += '<div class="cms-lb-acc-item">' + esc(t) + '</div>';
                        });
                        inner = accRows
                            ? '<div class="cms-mod-accordion cms-lb-accordion-preview">' + accRows + '</div>'
                            : '<div class="text-muted small p-3 border">Accordion — add items in the panel.</div>';
                        break;
                    }
                    case 'video': {
                        var vurl = String(d.url || '');
                        var vkind = 'Video';
                        if (/youtu\.be|youtube/i.test(vurl)) {
                            vkind = 'YouTube';
                        } else if (/vimeo/i.test(vurl)) {
                            vkind = 'Vimeo';
                        } else if (/\.(mp4|webm|ogg)(\?|$)/i.test(vurl)) {
                            vkind = 'Video file';
                        }
                        inner = '<div class="cms-mod-video cms-lb-video-placeholder">'
                            + '<div class="cms-lb-video-frame">' + esc(vkind) + '</div>'
                            + (vurl ? '<div class="small text-muted mt-1">' + esc(vurl) + '</div>' : '<div class="small text-muted mt-1">Paste a YouTube, Vimeo, or HTTPS .mp4 URL</div>')
                            + (d.caption ? '<div class="small">' + esc(d.caption) + '</div>' : '')
                            + '</div>';
                        break;
                    }
                    case 'tabs': {
                        var tabItems = Array.isArray(d.items) ? d.items : [];
                        var tabChips = '';
                        tabItems.forEach(function (item, ti) {
                            var tt = (item && item.title) ? String(item.title) : 'Tab';
                            tabChips += '<span class="cms-lb-tab-chip' + (ti === 0 ? ' is-on' : '') + '">' + esc(tt) + '</span>';
                        });
                        inner = tabChips
                            ? '<div class="cms-mod-tabs cms-lb-tabs-preview">' + tabChips + '</div>'
                            : '<div class="text-muted small p-3 border">Tabs — add panels in the inspector.</div>';
                        break;
                    }
                    case 'icon_list': {
                        var listItems = Array.isArray(d.items) ? d.items : [];
                        var listRows = '';
                        listItems.forEach(function (item) {
                            var ic = (item && item.icon) ? String(item.icon) : '•';
                            var tx = (item && item.text) ? String(item.text) : '';
                            listRows += '<div class="cms-lb-ilist-item"><span class="cms-lb-ilist-icon">' + esc(ic)
                                + '</span><span>' + esc(tx) + '</span></div>';
                        });
                        inner = listRows
                            ? '<div class="cms-mod-icon-list cms-lb-ilist-preview">' + listRows + '</div>'
                            : '<div class="text-muted small p-3 border">Icon list — add points in the inspector.</div>';
                        break;
                    }
                    case 'gallery': {
                        var galItems = Array.isArray(d.items) ? d.items : [];
                        var galThumbs = '';
                        var galShown = 0;
                        galItems.forEach(function (item) {
                            if (galShown >= 4) {
                                return;
                            }
                            var gm = item.media_id ? mediaById(item.media_id) : null;
                            var gsrc = (gm && (gm.preview || gm.url)) || item.url || '';
                            if (gsrc) {
                                galThumbs += '<img src="' + esc(gsrc) + '" alt="' + esc(item.alt || '') + '" class="cms-lb-carousel-thumb">';
                                galShown += 1;
                            }
                        });
                        inner = galThumbs
                            ? '<div class="cms-mod-gallery cms-lb-gallery-preview">' + galThumbs
                                + (galItems.length > galShown ? '<span class="small text-muted">+' + (galItems.length - galShown) + ' more</span>' : '')
                                + '</div>'
                            : '<div class="text-muted small p-3 border">Gallery — add images in the inspector.</div>';
                        break;
                    }
                    case 'testimonial': {
                        var tItems = Array.isArray(d.items) ? d.items : [];
                        var tRows = '';
                        tItems.forEach(function (item) {
                            var nm = (item && item.name) ? String(item.name) : 'Name';
                            var qt = (item && item.quote) ? String(item.quote) : '';
                            tRows += '<div class="cms-lb-tml-item"><strong>' + esc(nm) + '</strong>'
                                + (qt ? '<div class="small text-muted">' + esc(qt) + '</div>' : '') + '</div>';
                        });
                        inner = tRows
                            ? '<div class="cms-mod-testimonials cms-lb-tml-preview">' + tRows + '</div>'
                            : '<div class="text-muted small p-3 border">Testimonials — add quotes in the inspector.</div>';
                        break;
                    }
                    default:
                        inner = '<em>' + esc(mod.type) + '</em>';
                }
                return wrapOpen + inner + wrapClose;
            }

            function innerRowCanvasHtml(mod, si, ri, ci, mi) {
                var mid = ensureNodeId(mod);
                var wrapCls = 'cms-layout-module cms-mod-inner-row' + (elementCssClass(mid) ? ' ' + elementCssClass(mid) : '');
                var html = '<div class="' + wrapCls + '"' + (mid ? ' data-el-id="' + esc(mid) + '"' : '') + '>';
                html += '<div class="cms-lb-inner-row row g-3">';
                var cols = mod.columns || [];
                if (!cols.length) {
                    html += '<p class="small text-muted mb-0">Choose a column layout in the inspector.</p>';
                }
                cols.forEach(function (icol, ici) {
                    var w = Number(icol.width) || 12;
                    var icsel = selection.kind === 'inner_column'
                        && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci
                        && selection.modIdx === mi && selIci() === ici ? ' is-selected' : '';
                    var picking = modulePickTarget
                        && modulePickTarget.si === si
                        && modulePickTarget.ri === ri
                        && modulePickTarget.ci === ci
                        && modulePickTarget.mi === mi
                        && modulePickTarget.ici === ici;
                    var icid = ensureNodeId(icol);
                    var path = 'data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '" data-ici="' + ici + '"';
                    html += '<div class="cms-lb-inner-col cms-layout-column col-md-' + w + icsel + (picking ? ' is-picking' : '')
                        + (elementCssClass(icid) ? ' ' + elementCssClass(icid) : '')
                        + '" data-kind="inner_column" ' + path
                        + (icid ? ' data-el-id="' + esc(icid) + '"' : '') + '>';
                    html += chrome('Inner ' + w + '/12',
                        chromeBtn('add-inner-mod', path, '+ Module', 'Add module in this inner column')
                        + chromeBtn('del-inner-col', path, 'Del', 'Delete inner column'),
                        true);
                    var imods = icol.modules || [];
                    if (!imods.length) {
                        html += '<div class="cms-lb-col-empty">'
                            + '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="add-inner-mod" ' + path + '>+ Add module</button>'
                            + '</div>';
                    }
                    imods.forEach(function (imod, imi) {
                        var imsel = selection.kind === 'inner_module'
                            && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci
                            && selection.modIdx === mi && selIci() === ici && selImi() === imi ? ' is-selected' : '';
                        var ipath = path + ' data-imi="' + imi + '"';
                        html += '<div class="cms-lb-inner-mod' + imsel + '" data-kind="inner_module" ' + ipath + '>';
                        html += chrome(moduleTypes[imod.type] || imod.type,
                            chromeBtn('inner-mod-up', ipath, '↑', 'Move module up')
                            + chromeBtn('inner-mod-down', ipath, '↓', 'Move module down')
                            + chromeBtn('add-inner-mod-after', ipath, '+', 'Add module after this one')
                            + chromeBtn('dup-inner-mod', ipath, 'Dup', 'Duplicate module')
                            + chromeBtn('del-inner-mod', ipath, 'Del', 'Delete module'),
                            true);
                        html += modulePreviewHtml(imod);
                        html += '</div>';
                    });
                    html += '</div>';
                });
                html += '</div></div>';
                return html;
            }
            
            function sanitizePreviewHtml(html) {
                var box = document.createElement('div');
                box.innerHTML = String(html || '');
                box.querySelectorAll('script,iframe,form,input,button,object,embed,link,meta,style').forEach(function (n) {
                    n.parentNode && n.parentNode.removeChild(n);
                });
                return box.innerHTML || '<span class="text-muted">Empty HTML</span>';
            }
            
            function chromeBtn(action, extraAttrs, label, title) {
                return '<button type="button" class="cms-lb-chrome-btn" data-action="' + action + '" ' + extraAttrs
                    + ' title="' + esc(title) + '" aria-label="' + esc(title) + '">' + esc(label) + '</button>';
            }
            
            function chrome(label, buttonsHtml, noDrag) {
                var drag = noDrag
                    ? ''
                    : '<button type="button" class="cms-lb-chrome-btn cms-lb-drag-handle" data-drag-handle draggable="true" title="Drag to reorder" aria-label="Drag to reorder">⋮⋮</button>';
                return '<div class="cms-lb-chrome"><span class="cms-lb-chrome-label">'
                    + drag
                    + '<span>' + esc(label) + '</span></span><span class="cms-lb-chrome-actions">' + buttonsHtml + '</span></div>';
            }
            
            function render() {
                if (!canvas) {
                    return;
                }
                if (refreshLiveCss) {
                    refreshLiveCss();
                }
                if (!layout.sections.length) {
                    canvas.innerHTML = '<div class="cms-lb-empty"><h2>Start this layout</h2>'
                        + '<p>Add a section, pick columns, then modules. Drag ⋮⋮ on the canvas or rows in Layers to reorder. Copy/paste with Ctrl+C / V. Undo with Ctrl+Z, save with Ctrl+S.</p>'
                        + '<button type="button" class="btn btn-primary btn-sm" data-action="add-section">+ Add section</button></div>';
                    bindCanvas();
                    updateShell();
                    updateDirtyUi();
                    if (layersEl && !layersEl.hidden) {
                        renderLayers();
                    }
                    if (historyPending) {
                        historyPending = false;
                        commitHistoryNow();
                    }
                    return;
                }

                var html = '<div class="cms-layout">';
                layout.sections.forEach(function (section, si) {
                    var sel = selection.kind === 'section' && selection.sectionIdx === si ? ' is-selected' : '';
                    var sid = ensureNodeId(section);
                    html += '<section class="cms-lb-section cms-layout-section cms-layout-section--' + esc(section.type || 'regular')
                        + (elementCssClass(sid) ? ' ' + elementCssClass(sid) : '') + sel
                        + '" data-kind="section" data-si="' + si + '"' + (sid ? ' data-el-id="' + esc(sid) + '"' : '') + '>';
                    html += chrome('Section',
                        chromeBtn('sec-up', 'data-si="' + si + '"', '↑', 'Move section up')
                        + chromeBtn('sec-down', 'data-si="' + si + '"', '↓', 'Move section down')
                        + chromeBtn('add-row', 'data-si="' + si + '"', '+ Row', 'Add row')
                        + chromeBtn('dup-section', 'data-si="' + si + '"', 'Dup', 'Duplicate section')
                        + chromeBtn('del-section', 'data-si="' + si + '"', 'Del', 'Delete section'));
                    html += sectionBgPreviewHtml(section);
                    html += shapeDividerHtml(section, 'top');
                    html += '<div class="cms-layout-section-inner ' + (section.type === 'fullwidth' ? 'cms-layout-section-inner--full' : 'container') + '">';
            
                    (section.rows || []).forEach(function (row, ri) {
                        var rsel = selection.kind === 'row' && selection.sectionIdx === si && selection.rowIdx === ri ? ' is-selected' : '';
                        var rid = ensureNodeId(row);
                        html += '<div class="cms-lb-row cms-layout-row row g-3'
                            + ((row.settings && row.settings.col_reverse_mobile) ? ' cms-layout-row--reverse-mobile' : '')
                            + (elementCssClass(rid) ? ' ' + elementCssClass(rid) : '') + rsel
                            + '" data-kind="row" data-si="' + si + '" data-ri="' + ri + '"'
                            + (rid ? ' data-el-id="' + esc(rid) + '"' : '') + '>';
                        html += chrome('Row',
                            chromeBtn('row-up', 'data-si="' + si + '" data-ri="' + ri + '"', '↑', 'Move row up')
                            + chromeBtn('row-down', 'data-si="' + si + '" data-ri="' + ri + '"', '↓', 'Move row down')
                            + chromeBtn('pick-cols', 'data-si="' + si + '" data-ri="' + ri + '"', 'Columns', 'Choose column layout')
                            + chromeBtn('add-col', 'data-si="' + si + '" data-ri="' + ri + '"', '+ Col', 'Add column')
                            + chromeBtn('dup-row', 'data-si="' + si + '" data-ri="' + ri + '"', 'Dup', 'Duplicate row')
                            + chromeBtn('del-row', 'data-si="' + si + '" data-ri="' + ri + '"', 'Del', 'Delete row'));
            
                        (row.columns || []).forEach(function (col, ci) {
                            var w = Number(col.width) || 12;
                            var csel = selection.kind === 'column' && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci ? ' is-selected' : '';
                            var picking = modulePickTarget
                                && modulePickTarget.si === si
                                && modulePickTarget.ri === ri
                                && modulePickTarget.ci === ci;
                            var valign = (col.settings && col.settings.valign) || '';
                            var valignCls = (valign === 'center' || valign === 'bottom') ? ' cms-layout-column--valign-' + valign : '';
                            var cid = ensureNodeId(col);
                            var colsInRow = (row.columns || []).length;
                            html += '<div class="cms-lb-col cms-layout-column col-md-' + w + csel + (picking ? ' is-picking' : '') + valignCls
                                + (elementCssClass(cid) ? ' ' + elementCssClass(cid) : '')
                                + '" data-kind="column" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '"'
                                + (cid ? ' data-el-id="' + esc(cid) + '"' : '') + '>';
                            html += chrome('Col ' + w + '/12',
                                chromeBtn('add-mod', 'data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '"', '+ Module', 'Add module')
                                + chromeBtn('dup-col', 'data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '"', 'Dup', 'Duplicate column')
                                + chromeBtn('del-col', 'data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '"', 'Del', 'Delete column'));
                            if (ci < colsInRow - 1) {
                                html += '<div class="cms-lb-col-resize" data-col-resize data-si="' + si + '" data-ri="' + ri
                                    + '" data-ci="' + ci + '" title="Drag to change column widths" role="separator" aria-orientation="vertical" aria-label="Resize column"></div>';
                            }
            
                            var mods = col.modules || [];
                            if (!mods.length) {
                                html += '<div class="cms-lb-col-empty">'
                                    + '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="add-mod" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '">+ Add module</button>'
                                    + '</div>';
                            }
            
                            mods.forEach(function (mod, mi) {
                                var msel = selection.kind === 'module' && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci && selection.modIdx === mi ? ' is-selected' : '';
                                html += '<div class="cms-lb-mod' + msel + '" data-kind="module" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '">';
                                var chromeExtra = 'data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '"';
                                html += chrome(moduleTypes[mod.type] || mod.type,
                                    chromeBtn('mod-up', chromeExtra, '↑', 'Move module up')
                                    + chromeBtn('mod-down', chromeExtra, '↓', 'Move module down')
                                    + chromeBtn('add-mod-after', chromeExtra, '+', 'Add module after this one')
                                    + chromeBtn('dup-mod', chromeExtra, 'Dup', 'Duplicate module')
                                    + chromeBtn('del-mod', chromeExtra, 'Del', 'Delete module'));
                                if (mod.type === 'inner_row') {
                                    html += innerRowCanvasHtml(mod, si, ri, ci, mi);
                                } else {
                                    html += modulePreviewHtml(mod);
                                }
                                html += '</div>';
                                html += '<button type="button" class="cms-lb-insert-between" data-action="add-mod-after" ' + chromeExtra
                                    + ' title="Insert module after" aria-label="Insert module after">+</button>';
                            });
            
                            html += '</div>';
                        });
            
                        html += '</div>';
                    });
            
                    html += '</div>';
                    html += shapeDividerHtml(section, 'bottom');
                    html += '</section>';
                });
                html += '</div><div class="cms-lb-add-bar"><button type="button" class="btn btn-outline-primary btn-sm" data-action="add-section">+ Section</button></div>';
                canvas.innerHTML = html;
                bindCanvas();
                updateShell();
                updateDirtyUi();
                if (layersEl && !layersEl.hidden) {
                    renderLayers();
                }
                scrollSelectionIntoView();
                if (historyPending) {
                    historyPending = false;
                    commitHistoryNow();
                }
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
                if (layersEl && !layersEl.hidden) {
                    shell.classList.add('layers-open');
                } else {
                    shell.classList.remove('layers-open');
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
                        if (e.target.closest('[data-action]') || e.target.closest('[data-drag-handle]') || e.target.closest('[data-col-resize]')) {
                            return;
                        }
                        if (e.target.closest('[data-inline-field]')) {
                            e.stopPropagation();
                            var fieldEl = e.target.closest('[data-inline-field]');
                            var fieldName = fieldEl.getAttribute('data-inline-field');
                            var already = (el.getAttribute('data-kind') === 'module' || el.getAttribute('data-kind') === 'inner_module')
                                && isModSel()
                                && Number(el.getAttribute('data-si')) === selection.sectionIdx
                                && Number(el.getAttribute('data-ri')) === selection.rowIdx
                                && Number(el.getAttribute('data-ci')) === selection.colIdx
                                && Number(el.getAttribute('data-mi')) === selection.modIdx
                                && (el.getAttribute('data-kind') !== 'inner_module'
                                    || (selIci() === Number(el.getAttribute('data-ici')) && selImi() === Number(el.getAttribute('data-imi'))));
                            if (!already) {
                                selectFromEl(el);
                                var again = canvas.querySelector((el.getAttribute('data-kind') === 'inner_module' ? '.cms-lb-inner-mod' : '.cms-lb-mod') + '.is-selected [data-inline-field="' + fieldName + '"]');
                                if (again) {
                                    again.focus();
                                }
                            }
                            return;
                        }
                        e.stopPropagation();
                        selectFromEl(el);
                    });
                });
                bindCanvasLinks(canvas);
                bindDragAndDrop();
                bindColumnResize();
                bindInlineEditing(canvas);
            }

            function bindCanvasLinks(root) {
                root = root || canvas;
                if (!root) {
                    return;
                }
                root.querySelectorAll('[data-cms-canvas-link]').forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var wrap = a.closest('[data-kind="inner_module"]') || a.closest('[data-kind="module"]');
                        if (wrap) {
                            selectFromEl(wrap);
                        }
                    });
                });
            }
            
            function bindInlineEditing(root) {
                root = root || canvas;
                if (!root) {
                    return;
                }
                root.querySelectorAll('[data-inline-field]').forEach(function (el) {
                    el.addEventListener('paste', function (e) {
                        e.preventDefault();
                        var text = '';
                        if (e.clipboardData) {
                            text = e.clipboardData.getData('text/plain') || '';
                        }
                        if (document.queryCommandSupported && document.queryCommandSupported('insertText')) {
                            document.execCommand('insertText', false, text);
                        } else {
                            el.textContent = (el.textContent || '') + text;
                        }
                    });
                    el.addEventListener('input', function () {
                        applyInlineFromEl(el);
                    });
                    el.addEventListener('blur', function () {
                        applyInlineFromEl(el);
                        commitHistoryNow();
                        if (isModSel()) {
                            renderPanel();
                        }
                    });
                });
            }
            
            function applyInlineFromEl(el) {
                var wrap = el.closest('.cms-lb-inner-mod') || el.closest('.cms-lb-mod');
                if (!wrap) {
                    return;
                }
                var si = Number(wrap.getAttribute('data-si'));
                var ri = Number(wrap.getAttribute('data-ri'));
                var ci = Number(wrap.getAttribute('data-ci'));
                var mi = Number(wrap.getAttribute('data-mi'));
                var node;
                if (wrap.getAttribute('data-kind') === 'inner_module') {
                    var ici = Number(wrap.getAttribute('data-ici'));
                    var imi = Number(wrap.getAttribute('data-imi'));
                    var innerCol = getInnerColAt(si, ri, ci, mi, ici);
                    node = innerCol && innerCol.modules ? innerCol.modules[imi] : null;
                } else {
                    var col = getColAt(si, ri, ci);
                    node = col && col.modules ? col.modules[mi] : null;
                }
                if (!node || !node.data) {
                    return;
                }
                var field = el.getAttribute('data-inline-field') || 'text';
                var val;
                if (el.getAttribute('data-inline-html') === '1') {
                    val = typeof sanitizeRichClient === 'function' ? sanitizeRichClient(el.innerHTML) : el.innerHTML;
                } else {
                    val = (el.innerText || el.textContent || '').replace(/\u00a0/g, ' ');
                }
                if (String(node.data[field] || '') === val) {
                    return;
                }
                node.data[field] = val;
                noteLayoutChange();
                updateDirtyUi();
                var panelField = panelBody ? panelBody.querySelector('[data-field="' + field + '"]') : null;
                if (panelField && document.activeElement !== panelField) {
                    panelField.value = val;
                }
                var panelRich = panelBody ? panelBody.querySelector('.cms-lb-rich-ed[data-rich-for="' + field + '"]') : null;
                if (panelRich && document.activeElement !== panelRich) {
                    panelRich.innerHTML = typeof formatRichPreview === 'function' ? formatRichPreview(val) : val;
                }
            }

            function patchSelectedModulePreview() {
                if (!canvas || !isModSel()) {
                    return false;
                }
                var node = getSelectedNode();
                if (!node || node.type === 'carousel' || node.type === 'inner_row') {
                    return false;
                }
                var chromeEl = selectedChromeEl();
                if (!chromeEl) {
                    return false;
                }
                var wrap = chromeEl.querySelector('.cms-layout-module');
                if (!wrap) {
                    return false;
                }
                var box = document.createElement('div');
                box.innerHTML = modulePreviewHtml(node);
                var next = box.firstElementChild;
                if (!next) {
                    return false;
                }
                wrap.replaceWith(next);
                bindCanvasLinks(chromeEl);
                bindInlineEditing(chromeEl);
                if (layersEl && !layersEl.hidden) {
                    renderLayers();
                }
                return true;
            }
            
            ctx.modulePreviewHtml = modulePreviewHtml;
            ctx.sanitizePreviewHtml = sanitizePreviewHtml;
            ctx.chromeBtn = chromeBtn;
            ctx.chrome = chrome;
            ctx.render = render;
            ctx.updateShell = updateShell;
            ctx.bindCanvas = bindCanvas;
            ctx.bindCanvasLinks = bindCanvasLinks;
            ctx.bindInlineEditing = bindInlineEditing;
            ctx.applyInlineFromEl = applyInlineFromEl;
            ctx.patchSelectedModulePreview = patchSelectedModulePreview;
        }
    });
})();
