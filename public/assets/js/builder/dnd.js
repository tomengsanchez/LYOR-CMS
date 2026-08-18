/**
 * Visual layout builder — drag-reorder and column resize.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function locFromEl(el) {
                return {
                    kind: el.getAttribute('data-kind'),
                    si: Number(el.getAttribute('data-si')),
                    ri: el.hasAttribute('data-ri') ? Number(el.getAttribute('data-ri')) : -1,
                    ci: el.hasAttribute('data-ci') ? Number(el.getAttribute('data-ci')) : -1,
                    mi: el.hasAttribute('data-mi') ? Number(el.getAttribute('data-mi')) : -1,
                    ici: el.hasAttribute('data-ici') ? Number(el.getAttribute('data-ici')) : -1,
                    imi: el.hasAttribute('data-imi') ? Number(el.getAttribute('data-imi')) : -1
                };
            }
            
            function locEqual(a, b) {
                return a && b && a.kind === b.kind && a.si === b.si && a.ri === b.ri && a.ci === b.ci && a.mi === b.mi
                    && (a.ici == null ? -1 : a.ici) === (b.ici == null ? -1 : b.ici)
                    && (a.imi == null ? -1 : a.imi) === (b.imi == null ? -1 : b.imi);
            }
            
            function getColAt(si, ri, ci) {
                var row = layout.sections[si] && layout.sections[si].rows && layout.sections[si].rows[ri];
                return row && row.columns ? row.columns[ci] : null;
            }
            
            function clearDropHint() {
                [canvas, layersBody].forEach(function (root) {
                    if (!root) {
                        return;
                    }
                    root.querySelectorAll('.cms-lb-drop-before, .cms-lb-drop-after').forEach(function (n) {
                        n.classList.remove('cms-lb-drop-before', 'cms-lb-drop-after');
                    });
                });
                dropHint = { el: null, place: '' };
            }
            
            function findDropTarget(e) {
                var t = e.target;
                if (t && t.nodeType !== 1) {
                    t = t.parentElement;
                }
                if (!t || !t.closest || !dragSource) {
                    return null;
                }
                if (dragSource.kind === 'module') {
                    var mod = t.closest('.cms-lb-mod');
                    if (mod) {
                        return { el: mod, loc: locFromEl(mod) };
                    }
                    var col = t.closest('.cms-lb-col');
                    return col ? { el: col, loc: locFromEl(col) } : null;
                }
                if (dragSource.kind === 'column') {
                    var col2 = t.closest('.cms-lb-col');
                    return col2 ? { el: col2, loc: locFromEl(col2) } : null;
                }
                if (dragSource.kind === 'row') {
                    var row = t.closest('.cms-lb-row');
                    return row ? { el: row, loc: locFromEl(row) } : null;
                }
                if (dragSource.kind === 'section') {
                    var sec = t.closest('.cms-lb-section');
                    return sec ? { el: sec, loc: locFromEl(sec) } : null;
                }
                return null;
            }
            
            function dropPlace(e, dest) {
                if (!dest || dest.loc.kind === 'column' && dragSource.kind === 'module') {
                    return 'after';
                }
                var rect = dest.el.getBoundingClientRect();
                if (dragSource.kind === 'column') {
                    return e.clientX < rect.left + rect.width / 2 ? 'before' : 'after';
                }
                return e.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
            }
            
            function applyReorder(src, dest, place) {
                if (!src || !dest) {
                    return;
                }
                if (src.kind === 'module' && (dest.kind === 'module' || dest.kind === 'column')) {
                    var srcCol = getColAt(src.si, src.ri, src.ci);
                    if (!srcCol || !srcCol.modules || !srcCol.modules[src.mi]) {
                        return;
                    }
                    var item = srcCol.modules.splice(src.mi, 1)[0];
                    var destCol = dest.kind === 'column'
                        ? getColAt(dest.si, dest.ri, dest.ci)
                        : getColAt(dest.si, dest.ri, dest.ci);
                    if (!destCol) {
                        srcCol.modules.splice(src.mi, 0, item);
                        return;
                    }
                    destCol.modules = destCol.modules || [];
                    var destMi;
                    if (dest.kind === 'column') {
                        destMi = destCol.modules.length;
                    } else {
                        destMi = dest.mi;
                        if (src.si === dest.si && src.ri === dest.ri && src.ci === dest.ci && src.mi < dest.mi) {
                            destMi -= 1;
                        }
                        if (place === 'after') {
                            destMi += 1;
                        }
                    }
                    destMi = Math.max(0, Math.min(destMi, destCol.modules.length));
                    destCol.modules.splice(destMi, 0, item);
                    selection = { kind: 'module', sectionIdx: dest.si, rowIdx: dest.ri, colIdx: dest.ci, modIdx: destMi };
                    return;
                }
                if (src.kind === 'column' && dest.kind === 'column') {
                    var srcRow = layout.sections[src.si] && layout.sections[src.si].rows && layout.sections[src.si].rows[src.ri];
                    var destRow = layout.sections[dest.si] && layout.sections[dest.si].rows && layout.sections[dest.si].rows[dest.ri];
                    if (!srcRow || !destRow || !srcRow.columns[src.ci]) {
                        return;
                    }
                    var colItem = srcRow.columns.splice(src.ci, 1)[0];
                    var destCi = dest.ci;
                    if (src.si === dest.si && src.ri === dest.ri && src.ci < dest.ci) {
                        destCi -= 1;
                    }
                    if (place === 'after') {
                        destCi += 1;
                    }
                    destCi = Math.max(0, Math.min(destCi, destRow.columns.length));
                    destRow.columns.splice(destCi, 0, colItem);
                    selection = { kind: 'column', sectionIdx: dest.si, rowIdx: dest.ri, colIdx: destCi, modIdx: -1 };
                    return;
                }
                if (src.kind === 'row' && dest.kind === 'row') {
                    var srcSec = layout.sections[src.si];
                    var destSec = layout.sections[dest.si];
                    if (!srcSec || !destSec || !srcSec.rows[src.ri]) {
                        return;
                    }
                    var rowItem = srcSec.rows.splice(src.ri, 1)[0];
                    var destRi = dest.ri;
                    if (src.si === dest.si && src.ri < dest.ri) {
                        destRi -= 1;
                    }
                    if (place === 'after') {
                        destRi += 1;
                    }
                    destRi = Math.max(0, Math.min(destRi, destSec.rows.length));
                    destSec.rows.splice(destRi, 0, rowItem);
                    selection = { kind: 'row', sectionIdx: dest.si, rowIdx: destRi, colIdx: -1, modIdx: -1 };
                    return;
                }
                if (src.kind === 'section' && dest.kind === 'section') {
                    if (!layout.sections[src.si]) {
                        return;
                    }
                    var secItem = layout.sections.splice(src.si, 1)[0];
                    var destSi = dest.si;
                    if (src.si < dest.si) {
                        destSi -= 1;
                    }
                    if (place === 'after') {
                        destSi += 1;
                    }
                    destSi = Math.max(0, Math.min(destSi, layout.sections.length));
                    layout.sections.splice(destSi, 0, secItem);
                    selection = { kind: 'section', sectionIdx: destSi, rowIdx: -1, colIdx: -1, modIdx: -1 };
                }
            }
            
            function bindDragAndDrop() {
                canvas.querySelectorAll('[data-drag-handle]').forEach(function (handle) {
                    var wrap = handle.closest('[data-kind]');
                    if (!wrap) {
                        return;
                    }
                    handle.setAttribute('draggable', 'true');
                    handle.addEventListener('mousedown', function (e) {
                        e.stopPropagation();
                        wrap.setAttribute('draggable', 'true');
                    });
                    handle.addEventListener('dragstart', function (e) {
                        e.stopPropagation();
                        dragSource = locFromEl(wrap);
                        try {
                            e.dataTransfer.setData('text/plain', JSON.stringify(dragSource));
                            e.dataTransfer.effectAllowed = 'move';
                            if (e.dataTransfer.setDragImage) {
                                e.dataTransfer.setDragImage(wrap, 16, 12);
                            }
                        } catch (err) { /* IE */ }
                        wrap.classList.add('cms-lb-dragging');
                    });
                    handle.addEventListener('dragend', function (e) {
                        e.stopPropagation();
                        wrap.removeAttribute('draggable');
                        wrap.classList.remove('cms-lb-dragging');
                        clearDropHint();
                        dragSource = null;
                    });
                });
                canvas.querySelectorAll('[data-kind]').forEach(function (el) {
                    el.addEventListener('dragstart', function (e) {
                        if (e.target && e.target.closest && e.target.closest('[data-drag-handle]')) {
                            return;
                        }
                        if (el.getAttribute('draggable') !== 'true') {
                            e.preventDefault();
                            return;
                        }
                        e.stopPropagation();
                        dragSource = locFromEl(el);
                        try {
                            e.dataTransfer.setData('text/plain', JSON.stringify(dragSource));
                            e.dataTransfer.effectAllowed = 'move';
                        } catch (err) { /* IE */ }
                        el.classList.add('cms-lb-dragging');
                    });
                    el.addEventListener('dragend', function () {
                        el.removeAttribute('draggable');
                        el.classList.remove('cms-lb-dragging');
                        clearDropHint();
                        dragSource = null;
                    });
                });
                if (canvasDndBound) {
                    return;
                }
                canvasDndBound = true;
                canvas.addEventListener('dragover', function (e) {
                    if (!dragSource) {
                        return;
                    }
                    var dest = findDropTarget(e);
                    if (!dest) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    var place = dropPlace(e, dest);
                    if (dropHint.el === dest.el && dropHint.place === place) {
                        return;
                    }
                    clearDropHint();
                    dest.el.classList.add(place === 'before' ? 'cms-lb-drop-before' : 'cms-lb-drop-after');
                    dropHint = { el: dest.el, place: place };
                });
                canvas.addEventListener('drop', function (e) {
                    if (!dragSource) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    var dest = findDropTarget(e);
                    var src = dragSource;
                    var place = dest ? dropPlace(e, dest) : '';
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
            
            function bindColumnResize() {
                canvas.querySelectorAll('[data-col-resize]').forEach(function (handle) {
                    handle.addEventListener('pointerdown', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var si = Number(handle.getAttribute('data-si'));
                        var ri = Number(handle.getAttribute('data-ri'));
                        var ci = Number(handle.getAttribute('data-ci'));
                        var row = layout.sections[si] && layout.sections[si].rows && layout.sections[si].rows[ri];
                        if (!row || !row.columns || !row.columns[ci] || !row.columns[ci + 1]) {
                            return;
                        }
                        var left = row.columns[ci];
                        var right = row.columns[ci + 1];
                        var pair = (Number(left.width) || 12) + (Number(right.width) || 12);
                        var startX = e.clientX;
                        var startLeft = Number(left.width) || 1;
                        var rowEl = handle.closest('.cms-lb-row');
                        var rowWidth = rowEl ? rowEl.getBoundingClientRect().width : 0;
                        handle.classList.add('is-active');
                        document.body.classList.add('is-resizing-col');
                        var app = document.querySelector('.cms-builder-app');
                        if (app) {
                            app.classList.add('is-resizing-col');
                        }
                        function onMove(ev) {
                            if (!rowWidth) {
                                return;
                            }
                            var deltaCols = Math.round((ev.clientX - startX) / (rowWidth / 12));
                            var nextLeft = startLeft + deltaCols;
                            var minL = 1;
                            var maxL = Math.max(1, pair - 1);
                            nextLeft = Math.max(minL, Math.min(maxL, nextLeft));
                            left.width = nextLeft;
                            right.width = pair - nextLeft;
                            var leftEl = canvas.querySelector('.cms-lb-col[data-si="' + si + '"][data-ri="' + ri + '"][data-ci="' + ci + '"]');
                            var rightEl = canvas.querySelector('.cms-lb-col[data-si="' + si + '"][data-ri="' + ri + '"][data-ci="' + (ci + 1) + '"]');
                            [leftEl, rightEl].forEach(function (colEl, idx) {
                                if (!colEl) {
                                    return;
                                }
                                colEl.className = colEl.className.replace(/\bcol-md-\d+\b/g, '').replace(/\s+/g, ' ').trim()
                                    + ' col-md-' + (idx === 0 ? left.width : right.width);
                                var label = colEl.querySelector('.cms-lb-chrome-label span:last-child');
                                if (label) {
                                    label.textContent = 'Col ' + (idx === 0 ? left.width : right.width) + '/12';
                                }
                            });
                        }
                        function onUp() {
                            document.removeEventListener('pointermove', onMove);
                            document.removeEventListener('pointerup', onUp);
                            handle.classList.remove('is-active');
                            document.body.classList.remove('is-resizing-col');
                            if (app) {
                                app.classList.remove('is-resizing-col');
                            }
                            selection = { kind: 'column', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: -1 };
                            openPanel();
                            noteLayoutChange();
                            render();
                            setStatus('Column widths ' + left.width + ' / ' + right.width);
                        }
                        document.addEventListener('pointermove', onMove);
                        document.addEventListener('pointerup', onUp);
                    });
                });
            }
            
            ctx.locFromEl = locFromEl;
            ctx.locEqual = locEqual;
            ctx.getColAt = getColAt;
            ctx.clearDropHint = clearDropHint;
            ctx.findDropTarget = findDropTarget;
            ctx.dropPlace = dropPlace;
            ctx.applyReorder = applyReorder;
            ctx.bindDragAndDrop = bindDragAndDrop;
            ctx.bindColumnResize = bindColumnResize;
        }
    });
})();
