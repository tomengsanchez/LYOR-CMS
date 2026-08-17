/**
 * Visual layout builder — layers tree and zoom.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function scrollSelectionIntoView() {
                if (!canvas || !selection.kind) {
                    return;
                }
                var el = canvas.querySelector('[data-kind].is-selected');
                if (el && typeof el.scrollIntoView === 'function') {
                    el.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                }
            }
            
            function canvasElFromLayer(btn) {
                if (!canvas || !btn) {
                    return null;
                }
                var kind = btn.getAttribute('data-kind');
                var sel = '';
                if (kind === 'section') {
                    sel = '.cms-lb-section[data-si="' + btn.getAttribute('data-si') + '"]';
                } else if (kind === 'row') {
                    sel = '.cms-lb-row[data-si="' + btn.getAttribute('data-si') + '"][data-ri="' + btn.getAttribute('data-ri') + '"]';
                } else if (kind === 'column') {
                    sel = '.cms-lb-col[data-si="' + btn.getAttribute('data-si') + '"][data-ri="' + btn.getAttribute('data-ri') + '"][data-ci="' + btn.getAttribute('data-ci') + '"]';
                } else if (kind === 'module') {
                    sel = '.cms-lb-mod[data-si="' + btn.getAttribute('data-si') + '"][data-ri="' + btn.getAttribute('data-ri') + '"][data-ci="' + btn.getAttribute('data-ci') + '"][data-mi="' + btn.getAttribute('data-mi') + '"]';
                }
                return sel ? canvas.querySelector(sel) : null;
            }
            
            function clearLayerPeek() {
                if (!canvas) {
                    return;
                }
                canvas.querySelectorAll('.cms-lb-peek').forEach(function (n) {
                    n.classList.remove('cms-lb-peek');
                });
            }
            
            function layerVisHint(node) {
                var s = node && node.settings;
                if (!s) {
                    return '';
                }
                if (s.hide_mobile && s.hide_desktop) {
                    return ' (hidden)';
                }
                if (s.hide_mobile) {
                    return ' (no mobile)';
                }
                if (s.hide_desktop) {
                    return ' (no desktop)';
                }
                return '';
            }
            
            function layerLabel(kind, node, fallback) {
                if (kind === 'module') {
                    var t = moduleTypes[node.type] || node.type;
                    var bit = (node.data && (node.data.text || node.data.title || node.data.label)) || '';
                    bit = String(bit).replace(/\s+/g, ' ').trim();
                    if (bit.length > 28) {
                        bit = bit.slice(0, 27) + '…';
                    }
                    return bit ? t + ': ' + bit : t;
                }
                if (kind === 'column') {
                    return 'Col ' + (Number(node.width) || 12) + '/12';
                }
                return fallback;
            }
            
            function renderLayers() {
                if (!layersBody) {
                    return;
                }
                if (!layout.sections.length) {
                    layersBody.innerHTML = '<p class="small text-muted px-2">No sections yet.</p>';
                    return;
                }
                var html = '';
                layout.sections.forEach(function (section, si) {
                    var secOn = selection.kind === 'section' && selection.sectionIdx === si;
                    html += '<button type="button" class="cms-lb-layer cms-lb-layer--section' + (secOn ? ' is-current' : '') + '" data-kind="section" data-si="' + si + '">Section ' + (si + 1) + esc(layerVisHint(section)) + '</button>';
                    (section.rows || []).forEach(function (row, ri) {
                        var rowOn = selection.kind === 'row' && selection.sectionIdx === si && selection.rowIdx === ri;
                        html += '<button type="button" class="cms-lb-layer cms-lb-layer--row' + (rowOn ? ' is-current' : '') + '" data-kind="row" data-si="' + si + '" data-ri="' + ri + '">Row ' + (ri + 1) + '</button>';
                        (row.columns || []).forEach(function (col, ci) {
                            var colOn = selection.kind === 'column' && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci;
                            html += '<button type="button" class="cms-lb-layer cms-lb-layer--column' + (colOn ? ' is-current' : '') + '" data-kind="column" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '">' + esc(layerLabel('column', col, 'Column')) + '</button>';
                            (col.modules || []).forEach(function (mod, mi) {
                                var modOn = selection.kind === 'module' && selection.sectionIdx === si && selection.rowIdx === ri && selection.colIdx === ci && selection.modIdx === mi;
                            html += '<button type="button" class="cms-lb-layer cms-lb-layer--module' + (modOn ? ' is-current' : '') + '" data-kind="module" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-mi="' + mi + '">' + esc(layerLabel('module', mod, 'Module')) + esc(layerVisHint(mod)) + '</button>';
                            });
                        });
                    });
                });
                layersBody.innerHTML = html;
                layersBody.querySelectorAll('[data-kind]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (layerDragMoved) {
                            return;
                        }
                        selectFromEl(btn);
                    });
                    btn.addEventListener('mouseenter', function () {
                        if (dragSource) {
                            return;
                        }
                        clearLayerPeek();
                        var match = canvasElFromLayer(btn);
                        if (match) {
                            match.classList.add('cms-lb-peek');
                            if (typeof match.scrollIntoView === 'function') {
                                match.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                            }
                        }
                    });
                    btn.addEventListener('mouseleave', function () {
                        if (dragSource) {
                            return;
                        }
                        clearLayerPeek();
                    });
                });
                bindLayersDnd();
                applyLayersFilter();
            }

            function findLayerDropTarget(e) {
                if (!dragSource || !layersBody) {
                    return null;
                }
                var t = e.target;
                if (t && t.nodeType !== 1) {
                    t = t.parentElement;
                }
                if (!t || !t.closest) {
                    return null;
                }
                var btn = t.closest('#cmsBuilderLayersBody [data-kind]');
                if (!btn || btn.hidden) {
                    return null;
                }
                var loc = locFromEl(btn);
                if (dragSource.kind === 'module' && (loc.kind === 'module' || loc.kind === 'column')) {
                    return { el: btn, loc: loc };
                }
                if (loc.kind === dragSource.kind) {
                    return { el: btn, loc: loc };
                }
                return null;
            }

            function dropPlaceY(e, dest) {
                if (!dest || (dest.loc.kind === 'column' && dragSource && dragSource.kind === 'module')) {
                    return 'after';
                }
                var rect = dest.el.getBoundingClientRect();
                return e.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
            }

            function bindLayersDnd() {
                if (!layersBody || typeof applyReorder !== 'function') {
                    return;
                }
                layersBody.querySelectorAll('[data-kind]').forEach(function (btn) {
                    btn.setAttribute('draggable', 'true');
                    btn.addEventListener('dragstart', function (e) {
                        e.stopPropagation();
                        layerDragMoved = true;
                        dragSource = locFromEl(btn);
                        try {
                            e.dataTransfer.setData('text/plain', JSON.stringify(dragSource));
                            e.dataTransfer.effectAllowed = 'move';
                        } catch (err) { /* IE */ }
                        btn.classList.add('cms-lb-dragging');
                        clearLayerPeek();
                    });
                    btn.addEventListener('dragend', function () {
                        btn.classList.remove('cms-lb-dragging');
                        clearDropHint();
                        dragSource = null;
                        setTimeout(function () {
                            layerDragMoved = false;
                        }, 0);
                    });
                });
                if (layersDndBound) {
                    return;
                }
                layersDndBound = true;
                layersBody.addEventListener('dragover', function (e) {
                    if (!dragSource) {
                        return;
                    }
                    var dest = findLayerDropTarget(e);
                    if (!dest) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    var place = dropPlaceY(e, dest);
                    if (dropHint.el === dest.el && dropHint.place === place) {
                        return;
                    }
                    clearDropHint();
                    dest.el.classList.add(place === 'before' ? 'cms-lb-drop-before' : 'cms-lb-drop-after');
                    dropHint = { el: dest.el, place: place };
                });
                layersBody.addEventListener('drop', function (e) {
                    if (!dragSource) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    var dest = findLayerDropTarget(e);
                    var src = dragSource;
                    var place = dest ? dropPlaceY(e, dest) : '';
                    clearDropHint();
                    if (!dest || locEqual(src, dest.loc)) {
                        dragSource = null;
                        return;
                    }
                    applyReorder(src, dest.loc, place);
                    dragSource = null;
                    modulePickTarget = null;
                    openPanel();
                    noteLayoutChange();
                    render();
                    setStatus('Moved');
                });
            }

            function applyLayersFilter() {
                var input = document.getElementById('cmsBuilderLayersFilter');
                var q = String((input && input.value) || layersFilter || '').toLowerCase().trim();
                layersFilter = q;
                if (!layersBody) {
                    return;
                }
                layersBody.querySelectorAll('[data-kind]').forEach(function (btn) {
                    var hay = (btn.textContent || '').toLowerCase();
                    btn.hidden = !!q && hay.indexOf(q) === -1;
                });
            }
            
            function toggleLayers() {
                if (!layersEl) {
                    return;
                }
                layersEl.hidden = !layersEl.hidden;
                var btn = document.getElementById('cmsBuilderLayersBtn');
                if (btn) {
                    btn.classList.toggle('is-active', !layersEl.hidden);
                    btn.setAttribute('aria-pressed', layersEl.hidden ? 'false' : 'true');
                }
                updateShell();
                if (!layersEl.hidden) {
                    renderLayers();
                }
            }
            
            function setZoom(scale) {
                canvasZoom = scale;
                if (canvas) {
                    canvas.style.setProperty('--cms-lb-zoom', String(scale));
                }
                document.querySelectorAll('.cms-builder-zoom [data-zoom]').forEach(function (btn) {
                    var on = Number(btn.getAttribute('data-zoom')) === scale;
                    btn.classList.toggle('is-active', on);
                });
            }
            
            ctx.scrollSelectionIntoView = scrollSelectionIntoView;
            ctx.canvasElFromLayer = canvasElFromLayer;
            ctx.clearLayerPeek = clearLayerPeek;
            ctx.layerVisHint = layerVisHint;
            ctx.layerLabel = layerLabel;
            ctx.renderLayers = renderLayers;
            ctx.applyLayersFilter = applyLayersFilter;
            ctx.toggleLayers = toggleLayers;
            ctx.setZoom = setZoom;
            ctx.bindLayersDnd = bindLayersDnd;
        }
    });
})();
