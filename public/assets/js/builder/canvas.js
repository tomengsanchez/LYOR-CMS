/**
 * Visual layout builder — canvas render and inline edit.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function modulePreviewHtml(mod) {
                var d = mod.data || {};
                var style = designStyle(mod.design);
                var adv = mod.advanced || {};
                var wrapCls = 'cms-layout-module cms-mod-' + esc(mod.type);
                if (adv.hide_mobile) {
                    wrapCls += ' cms-lb-hide-mobile';
                }
                if (adv.hide_desktop) {
                    wrapCls += ' cms-lb-hide-desktop';
                }
                var wrapOpen = '<div class="' + wrapCls + '"' + (style ? ' style="' + esc(style) + '"' : '') + '>';
                var wrapClose = '</div>';
                var inner = '';
                switch (mod.type) {
                    case 'heading': {
                        var lvl = Math.min(6, Math.max(1, Number(d.level) || 2));
                        inner = '<h' + lvl + ' class="cms-mod-heading" data-inline-field="text" contenteditable="true" spellcheck="true">' + esc(d.text || 'Heading') + '</h' + lvl + '>';
                        break;
                    }
                    case 'text':
                        inner = '<div class="cms-mod-text" data-inline-field="text" contenteditable="true" spellcheck="true">' + esc(d.text || '').replace(/\n/g, '<br>') + '</div>';
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
                            + '<div class="cms-mod-cta-text">' + esc(d.text || '').replace(/\n/g, '<br>') + '</div>'
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
                            + '<div class="cms-mod-blurb-text">' + esc(d.text || '').replace(/\n/g, '<br>') + '</div></div>';
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
                    default:
                        inner = '<em>' + esc(mod.type) + '</em>';
                }
                return wrapOpen + inner + wrapClose;
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
            
            function chrome(label, buttonsHtml) {
                return '<div class="cms-lb-chrome"><span class="cms-lb-chrome-label">'
                    + '<button type="button" class="cms-lb-chrome-btn cms-lb-drag-handle" data-drag-handle draggable="true" title="Drag to reorder" aria-label="Drag to reorder">⋮⋮</button>'
                    + '<span>' + esc(label) + '</span></span><span class="cms-lb-chrome-actions">' + buttonsHtml + '</span></div>';
            }
            
            function render() {
                if (!canvas) {
                    return;
                }
                if (!layout.sections.length) {
                    canvas.innerHTML = '<div class="cms-lb-empty"><h2>Start this layout</h2>'
                        + '<p>Add a section, pick columns, then modules. Drag ⋮⋮ to reorder. Layers lists the tree. Copy/paste with Ctrl+C / V. Undo with Ctrl+Z, save with Ctrl+S.</p>'
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
                    var st = settingsStyle(section.settings);
                    html += '<section class="cms-lb-section cms-layout-section cms-layout-section--' + esc(section.type || 'regular') + sel + '" data-kind="section" data-si="' + si + '"'
                        + (st ? ' style="' + esc(st) + '"' : '') + '>';
                    html += chrome('Section',
                        chromeBtn('sec-up', 'data-si="' + si + '"', '↑', 'Move section up')
                        + chromeBtn('sec-down', 'data-si="' + si + '"', '↓', 'Move section down')
                        + chromeBtn('add-row', 'data-si="' + si + '"', '+ Row', 'Add row')
                        + chromeBtn('dup-section', 'data-si="' + si + '"', 'Dup', 'Duplicate section')
                        + chromeBtn('del-section', 'data-si="' + si + '"', 'Del', 'Delete section'));
                    html += '<div class="cms-layout-section-inner ' + (section.type === 'fullwidth' ? 'cms-layout-section-inner--full' : 'container') + '">';
            
                    (section.rows || []).forEach(function (row, ri) {
                        var rsel = selection.kind === 'row' && selection.sectionIdx === si && selection.rowIdx === ri ? ' is-selected' : '';
                        html += '<div class="cms-lb-row cms-layout-row row g-3' + rsel + '" data-kind="row" data-si="' + si + '" data-ri="' + ri + '">';
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
                            var cst = settingsStyle(col.settings);
                            var colsInRow = (row.columns || []).length;
                            html += '<div class="cms-lb-col cms-layout-column col-md-' + w + csel + (picking ? ' is-picking' : '') + valignCls
                                + '" data-kind="column" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '"'
                                + (cst ? ' style="' + esc(cst) + '"' : '') + '>';
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
                                html += modulePreviewHtml(mod);
                                html += '</div>';
                                html += '<button type="button" class="cms-lb-insert-between" data-action="add-mod-after" ' + chromeExtra
                                    + ' title="Insert module after" aria-label="Insert module after">+</button>';
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
                            var already = el.getAttribute('data-kind') === 'module'
                                && selection.kind === 'module'
                                && Number(el.getAttribute('data-si')) === selection.sectionIdx
                                && Number(el.getAttribute('data-ri')) === selection.rowIdx
                                && Number(el.getAttribute('data-ci')) === selection.colIdx
                                && Number(el.getAttribute('data-mi')) === selection.modIdx;
                            if (!already) {
                                selectFromEl(el);
                                var again = canvas.querySelector('.cms-lb-mod.is-selected [data-inline-field="' + fieldName + '"]');
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
                canvas.querySelectorAll('[data-cms-canvas-link]').forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var wrap = a.closest('[data-kind="module"]');
                        if (wrap) {
                            selectFromEl(wrap);
                        }
                    });
                });
                bindDragAndDrop();
                bindColumnResize();
                bindInlineEditing();
            }
            
            function bindInlineEditing() {
                canvas.querySelectorAll('[data-inline-field]').forEach(function (el) {
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
                        if (selection.kind === 'module') {
                            renderPanel();
                        }
                    });
                });
            }
            
            function applyInlineFromEl(el) {
                var wrap = el.closest('.cms-lb-mod');
                if (!wrap) {
                    return;
                }
                var si = Number(wrap.getAttribute('data-si'));
                var ri = Number(wrap.getAttribute('data-ri'));
                var ci = Number(wrap.getAttribute('data-ci'));
                var mi = Number(wrap.getAttribute('data-mi'));
                var col = getColAt(si, ri, ci);
                var node = col && col.modules ? col.modules[mi] : null;
                if (!node || !node.data) {
                    return;
                }
                var field = el.getAttribute('data-inline-field') || 'text';
                var val = (el.innerText || el.textContent || '').replace(/\u00a0/g, ' ');
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
            }
            
            ctx.modulePreviewHtml = modulePreviewHtml;
            ctx.sanitizePreviewHtml = sanitizePreviewHtml;
            ctx.chromeBtn = chromeBtn;
            ctx.chrome = chrome;
            ctx.render = render;
            ctx.updateShell = updateShell;
            ctx.bindCanvas = bindCanvas;
            ctx.bindInlineEditing = bindInlineEditing;
            ctx.applyInlineFromEl = applyInlineFromEl;
        }
    });
})();
