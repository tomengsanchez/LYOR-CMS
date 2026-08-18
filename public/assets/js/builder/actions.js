/**
 * Visual layout builder — select, chrome actions, copy/paste.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function isModSel(kind) {
                kind = kind == null ? selection.kind : kind;
                return kind === 'module' || kind === 'inner_module';
            }

            function isColSel(kind) {
                kind = kind == null ? selection.kind : kind;
                return kind === 'column' || kind === 'inner_column';
            }

            function selIci() {
                var v = selection.innerColIdx;
                return v == null || isNaN(Number(v)) ? -1 : Number(v);
            }

            function selImi() {
                var v = selection.innerModIdx;
                return v == null || isNaN(Number(v)) ? -1 : Number(v);
            }

            function getInnerRowAt(si, ri, ci, mi) {
                var col = getColAt(si, ri, ci);
                var mod = col && col.modules ? col.modules[mi] : null;
                return mod && mod.type === 'inner_row' ? mod : null;
            }

            function getInnerColAt(si, ri, ci, mi, ici) {
                var row = getInnerRowAt(si, ri, ci, mi);
                return row && row.columns ? row.columns[ici] : null;
            }

            function selectFromEl(el) {
                var kind = el.getAttribute('data-kind');
                var prevKind = selection.kind;
                if (kind !== 'column' && kind !== 'inner_column') {
                    modulePickTarget = null;
                }
                if (kind !== prevKind) {
                    setPanelTab('content');
                }
                selection = {
                    kind: kind,
                    sectionIdx: Number(el.getAttribute('data-si')),
                    rowIdx: el.hasAttribute('data-ri') ? Number(el.getAttribute('data-ri')) : -1,
                    colIdx: el.hasAttribute('data-ci') ? Number(el.getAttribute('data-ci')) : -1,
                    modIdx: el.hasAttribute('data-mi') ? Number(el.getAttribute('data-mi')) : -1,
                    innerColIdx: el.hasAttribute('data-ici') ? Number(el.getAttribute('data-ici')) : -1,
                    innerModIdx: el.hasAttribute('data-imi') ? Number(el.getAttribute('data-imi')) : -1
                };
                openPanel();
                render();
            }
            
            function selectAncestor(kind) {
                if (!selection.kind || !kind) {
                    return;
                }
                if (kind === 'section') {
                    selection = { kind: 'section', sectionIdx: selection.sectionIdx, rowIdx: -1, colIdx: -1, modIdx: -1 };
                } else if (kind === 'row' && selection.rowIdx >= 0) {
                    selection = { kind: 'row', sectionIdx: selection.sectionIdx, rowIdx: selection.rowIdx, colIdx: -1, modIdx: -1 };
                } else if (kind === 'column' && selection.colIdx >= 0) {
                    selection = { kind: 'column', sectionIdx: selection.sectionIdx, rowIdx: selection.rowIdx, colIdx: selection.colIdx, modIdx: -1 };
                } else if (kind === 'module' && selection.modIdx >= 0) {
                    selection = { kind: 'module', sectionIdx: selection.sectionIdx, rowIdx: selection.rowIdx, colIdx: selection.colIdx, modIdx: selection.modIdx };
                } else if (kind === 'inner_column' && selIci() >= 0) {
                    selection = {
                        kind: 'inner_column',
                        sectionIdx: selection.sectionIdx,
                        rowIdx: selection.rowIdx,
                        colIdx: selection.colIdx,
                        modIdx: selection.modIdx,
                        innerColIdx: selIci(),
                        innerModIdx: -1
                    };
                } else {
                    return;
                }
                modulePickTarget = null;
                setPanelTab('content');
                openPanel();
                render();
            }
            
            function handleAction(action, btn) {
                var si = Number(btn.getAttribute('data-si'));
                var ri = Number(btn.getAttribute('data-ri'));
                var ci = Number(btn.getAttribute('data-ci'));
                var mi = Number(btn.getAttribute('data-mi'));
                var ici = btn.hasAttribute('data-ici') ? Number(btn.getAttribute('data-ici')) : -1;
                var imi = btn.hasAttribute('data-imi') ? Number(btn.getAttribute('data-imi')) : -1;
            
                if (action === 'add-section') {
                    layout.sections.push(emptySection());
                    selection = { kind: 'section', sectionIdx: layout.sections.length - 1, rowIdx: -1, colIdx: -1, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'del-section' && layout.sections[si]) {
                    if (!window.confirm('Delete this section and everything inside it?')) {
                        return;
                    }
                    layout.sections.splice(si, 1);
                    clearSelection();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'dup-section' && layout.sections[si]) {
                    var copy = reindexCopy(layout.sections[si]);
                    layout.sections.splice(si + 1, 0, copy);
                    selection = { kind: 'section', sectionIdx: si + 1, rowIdx: -1, colIdx: -1, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'add-row' && layout.sections[si]) {
                    layout.sections[si].rows = layout.sections[si].rows || [];
                    layout.sections[si].rows.push(emptyRow());
                    selection = { kind: 'row', sectionIdx: si, rowIdx: layout.sections[si].rows.length - 1, colIdx: -1, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'del-row' && layout.sections[si] && layout.sections[si].rows) {
                    if (!window.confirm('Delete this row?')) {
                        return;
                    }
                    layout.sections[si].rows.splice(ri, 1);
                    if (!layout.sections[si].rows.length) {
                        layout.sections[si].rows.push(emptyRow());
                    }
                    clearSelection();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'dup-row' && layout.sections[si] && layout.sections[si].rows && layout.sections[si].rows[ri]) {
                    var copyRow = reindexCopy(layout.sections[si].rows[ri]);
                    layout.sections[si].rows.splice(ri + 1, 0, copyRow);
                    selection = { kind: 'row', sectionIdx: si, rowIdx: ri + 1, colIdx: -1, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'pick-cols' && layout.sections[si] && layout.sections[si].rows[ri]) {
                    selection = { kind: 'row', sectionIdx: si, rowIdx: ri, colIdx: -1, modIdx: -1 };
                    setPanelTab('content');
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
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'del-col' && layout.sections[si] && layout.sections[si].rows[ri]) {
                    if (!window.confirm('Delete this column? Modules in it will be removed.')) {
                        return;
                    }
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
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'dup-col' && layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns) {
                    var srcCols = layout.sections[si].rows[ri].columns;
                    if (!srcCols[ci]) {
                        return;
                    }
                    if (srcCols.length >= 6) {
                        setStatus('Maximum 6 columns in a row', true);
                        return;
                    }
                    var copyCol = reindexCopy(srcCols[ci]);
                    srcCols.splice(ci + 1, 0, copyCol);
                    selection = { kind: 'column', sectionIdx: si, rowIdx: ri, colIdx: ci + 1, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'add-mod-after' && layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci]) {
                    modulePickTarget = { si: si, ri: ri, ci: ci, after: mi };
                    selection = { kind: 'column', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: -1 };
                    setPanelTab('content');
                    openPanel();
                    render();
                    return;
                }
                if (action === 'add-mod' && layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci]) {
                    modulePickTarget = { si: si, ri: ri, ci: ci };
                    selection = { kind: 'column', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: -1 };
                    setPanelTab('content');
                    openPanel();
                    render();
                    return;
                }
                if (action === 'del-mod') {
                    if (!window.confirm('Delete this module?')) {
                        return;
                    }
                    var col3 = layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci];
                    if (col3 && col3.modules) {
                        col3.modules.splice(mi, 1);
                    }
                    modulePickTarget = null;
                    clearSelection();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'dup-mod') {
                    var colDup = layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci];
                    if (colDup && colDup.modules && colDup.modules[mi]) {
                        var copyMod = reindexCopy(colDup.modules[mi]);
                        colDup.modules.splice(mi + 1, 0, copyMod);
                        selection = { kind: 'module', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: mi + 1 };
                        openPanel();
                        noteLayoutChange();
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
                    noteLayoutChange();
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
                    noteLayoutChange();
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
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'add-inner-mod' || action === 'add-inner-mod-after') {
                    var innerAddCol = getInnerColAt(si, ri, ci, mi, ici);
                    if (!innerAddCol) {
                        return;
                    }
                    modulePickTarget = {
                        si: si,
                        ri: ri,
                        ci: ci,
                        mi: mi,
                        ici: ici,
                        afterImi: action === 'add-inner-mod-after' ? imi : -1
                    };
                    selection = {
                        kind: 'inner_column',
                        sectionIdx: si,
                        rowIdx: ri,
                        colIdx: ci,
                        modIdx: mi,
                        innerColIdx: ici,
                        innerModIdx: -1
                    };
                    setPanelTab('content');
                    openPanel();
                    render();
                    return;
                }
                if (action === 'del-inner-col') {
                    var innerRowDel = getInnerRowAt(si, ri, ci, mi);
                    if (!innerRowDel || !innerRowDel.columns || !innerRowDel.columns[ici]) {
                        return;
                    }
                    if (!window.confirm('Delete this inner column? Modules in it will be removed.')) {
                        return;
                    }
                    if (innerRowDel.columns.length <= 1) {
                        innerRowDel.columns[0].modules = [];
                    } else {
                        innerRowDel.columns.splice(ici, 1);
                    }
                    modulePickTarget = null;
                    selection = { kind: 'module', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: mi };
                    openPanel();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'del-inner-mod') {
                    if (!window.confirm('Delete this module?')) {
                        return;
                    }
                    var innerDelCol = getInnerColAt(si, ri, ci, mi, ici);
                    if (innerDelCol && innerDelCol.modules) {
                        innerDelCol.modules.splice(imi, 1);
                    }
                    modulePickTarget = null;
                    selection = {
                        kind: 'inner_column',
                        sectionIdx: si,
                        rowIdx: ri,
                        colIdx: ci,
                        modIdx: mi,
                        innerColIdx: ici,
                        innerModIdx: -1
                    };
                    openPanel();
                    noteLayoutChange();
                    render();
                    return;
                }
                if (action === 'dup-inner-mod') {
                    var innerDupCol = getInnerColAt(si, ri, ci, mi, ici);
                    if (innerDupCol && innerDupCol.modules && innerDupCol.modules[imi]) {
                        var copyInner = reindexCopy(innerDupCol.modules[imi]);
                        if (copyInner.type === 'inner_row') {
                            setStatus('Only one nested row is allowed', true);
                            return;
                        }
                        innerDupCol.modules.splice(imi + 1, 0, copyInner);
                        selection = {
                            kind: 'inner_module',
                            sectionIdx: si,
                            rowIdx: ri,
                            colIdx: ci,
                            modIdx: mi,
                            innerColIdx: ici,
                            innerModIdx: imi + 1
                        };
                        openPanel();
                        noteLayoutChange();
                        render();
                    }
                    return;
                }
                if (action === 'inner-mod-up' || action === 'inner-mod-down') {
                    var innerMoveCol = getInnerColAt(si, ri, ci, mi, ici);
                    if (!innerMoveCol || !innerMoveCol.modules) {
                        return;
                    }
                    var innerTo = action === 'inner-mod-up' ? imi - 1 : imi + 1;
                    if (innerTo < 0 || innerTo >= innerMoveCol.modules.length) {
                        return;
                    }
                    var tmpInner = innerMoveCol.modules[imi];
                    innerMoveCol.modules[imi] = innerMoveCol.modules[innerTo];
                    innerMoveCol.modules[innerTo] = tmpInner;
                    selection = {
                        kind: 'inner_module',
                        sectionIdx: si,
                        rowIdx: ri,
                        colIdx: ci,
                        modIdx: mi,
                        innerColIdx: ici,
                        innerModIdx: innerTo
                    };
                    openPanel();
                    noteLayoutChange();
                    render();
                }
            }
            
            function insertModule(type, si, ri, ci, afterMi) {
                if (!moduleTypes[type]) {
                    setStatus('Unknown module type', true);
                    return;
                }
                var pick = modulePickTarget;
                var ici = pick && pick.ici != null ? Number(pick.ici) : -1;
                var outerMi = pick && pick.mi != null ? Number(pick.mi) : -1;
                var afterImi = pick && pick.afterImi != null ? Number(pick.afterImi) : -1;
                if (ici >= 0) {
                    if (type === 'inner_row') {
                        setStatus('Only one nested row is allowed', true);
                        return;
                    }
                    var innerCol = getInnerColAt(si, ri, ci, outerMi, ici);
                    if (!innerCol) {
                        return;
                    }
                    innerCol.modules = innerCol.modules || [];
                    var innerMod = defaultModule(type);
                    var innerIdx;
                    if (afterImi == null || isNaN(afterImi) || afterImi < 0) {
                        innerCol.modules.push(innerMod);
                        innerIdx = innerCol.modules.length - 1;
                    } else {
                        innerIdx = Math.min(innerCol.modules.length, afterImi + 1);
                        innerCol.modules.splice(innerIdx, 0, innerMod);
                    }
                    modulePickTarget = null;
                    selection = {
                        kind: 'inner_module',
                        sectionIdx: si,
                        rowIdx: ri,
                        colIdx: ci,
                        modIdx: outerMi,
                        innerColIdx: ici,
                        innerModIdx: innerIdx
                    };
                    setPanelTab('content');
                    openPanel();
                    noteLayoutChange();
                    render();
                    setStatus('Added ' + (moduleTypes[type] || type));
                    return;
                }
                var col = layout.sections[si] && layout.sections[si].rows[ri] && layout.sections[si].rows[ri].columns[ci];
                if (!col) {
                    return;
                }
                col.modules = col.modules || [];
                var mod = defaultModule(type);
                var idx;
                if (afterMi == null || isNaN(afterMi) || afterMi < 0) {
                    col.modules.push(mod);
                    idx = col.modules.length - 1;
                } else {
                    idx = Math.min(col.modules.length, afterMi + 1);
                    col.modules.splice(idx, 0, mod);
                }
                modulePickTarget = null;
                selection = { kind: 'module', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: idx };
                setPanelTab('content');
                openPanel();
                noteLayoutChange();
                render();
                setStatus('Added ' + (moduleTypes[type] || type));
            }
            
            function renderModuleTypePicker(si, ri, ci) {
                var afterMi = modulePickTarget && modulePickTarget.after != null ? Number(modulePickTarget.after) : -1;
                var ici = modulePickTarget && modulePickTarget.ici != null ? Number(modulePickTarget.ici) : -1;
                var outerMi = modulePickTarget && modulePickTarget.mi != null ? Number(modulePickTarget.mi) : -1;
                var afterImi = modulePickTarget && modulePickTarget.afterImi != null ? Number(modulePickTarget.afterImi) : -1;
                var hideInner = ici >= 0;
                var html = '<div class="cms-lb-mod-picker" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci
                    + '" data-after="' + afterMi + '" data-mi="' + outerMi + '" data-ici="' + ici + '" data-after-imi="' + afterImi + '">';
                html += '<p class="cms-lb-field-group">Add module</p>';
                if (afterMi >= 0 || afterImi >= 0) {
                    html += '<p class="cms-lb-panel-lead">Insert after the selected module.</p>';
                } else if (hideInner) {
                    html += '<p class="cms-lb-panel-lead">Choose a module for this inner column. Inner row cannot be nested again.</p>';
                } else {
                    html += '<p class="cms-lb-panel-lead">Choose a module to insert in this column. You can change its settings next.</p>';
                }
                html += '<input type="search" class="form-control form-control-sm mb-2" data-mod-filter placeholder="Filter modules" aria-label="Filter modules">';
                html += '<div class="cms-lb-mod-picker-grid">';
                Object.keys(moduleTypes).forEach(function (key) {
                    if (hideInner && key === 'inner_row') {
                        return;
                    }
                    var meta = MODULE_META[key] || {};
                    html += '<button type="button" class="cms-lb-mod-picker-btn" data-insert-mod="' + esc(key) + '">'
                        + '<span class="cms-lb-mod-picker-name">' + esc(moduleTypes[key]) + '</span>'
                        + (meta.hint ? '<span class="cms-lb-mod-picker-hint">' + esc(meta.hint) + '</span>' : '')
                        + '</button>';
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
                var innerCol = c && c.modules && c.modules[selection.modIdx]
                    && c.modules[selection.modIdx].type === 'inner_row'
                    && c.modules[selection.modIdx].columns
                    ? c.modules[selection.modIdx].columns[selIci()]
                    : null;
                if (selection.kind === 'inner_column') {
                    return innerCol || null;
                }
                if (selection.kind === 'inner_module') {
                    return innerCol && innerCol.modules && innerCol.modules[selImi()] ? innerCol.modules[selImi()] : null;
                }
                return null;
            }
            
            function duplicateSelected() {
                if (!selection.kind) {
                    return;
                }
                var fake = document.createElement('button');
                fake.setAttribute('data-si', String(selection.sectionIdx));
                fake.setAttribute('data-ri', String(selection.rowIdx));
                fake.setAttribute('data-ci', String(selection.colIdx));
                fake.setAttribute('data-mi', String(selection.modIdx));
                fake.setAttribute('data-ici', String(selIci()));
                fake.setAttribute('data-imi', String(selImi()));
                var map = {
                    section: 'dup-section',
                    row: 'dup-row',
                    column: 'dup-col',
                    module: 'dup-mod',
                    inner_module: 'dup-inner-mod'
                };
                handleAction(map[selection.kind], fake);
            }
            
            function deleteSelected() {
                if (!selection.kind) {
                    return;
                }
                var fake = document.createElement('button');
                fake.setAttribute('data-si', String(selection.sectionIdx));
                fake.setAttribute('data-ri', String(selection.rowIdx));
                fake.setAttribute('data-ci', String(selection.colIdx));
                fake.setAttribute('data-mi', String(selection.modIdx));
                fake.setAttribute('data-ici', String(selIci()));
                fake.setAttribute('data-imi', String(selImi()));
                var map = {
                    section: 'del-section',
                    row: 'del-row',
                    column: 'del-col',
                    module: 'del-mod',
                    inner_column: 'del-inner-col',
                    inner_module: 'del-inner-mod'
                };
                handleAction(map[selection.kind], fake);
            }
            
            function copySelected() {
                var node = getSelectedNode();
                if (!node || !selection.kind) {
                    return false;
                }
                clip = { kind: selection.kind, payload: JSON.parse(JSON.stringify(node)) };
                setStatus('Copied ' + selection.kind);
                updateClipboardButtons();
                return true;
            }

            function cloneBag(obj) {
                if (!obj || typeof obj !== 'object') {
                    return null;
                }
                var out = JSON.parse(JSON.stringify(obj));
                return Object.keys(out).length ? out : null;
            }

            function canPasteStyle() {
                if (!styleClip || !selection.kind) {
                    return false;
                }
                if (styleClip.target === 'module') {
                    return isModSel();
                }
                return selection.kind === 'section' || selection.kind === 'row' || isColSel();
            }

            function omitPositionKeys(obj) {
                if (!obj || typeof obj !== 'object') {
                    return obj;
                }
                delete obj.position;
                delete obj.z_index;
                delete obj.sticky_offset;
                return Object.keys(obj).length ? obj : null;
            }

            function copyStyle() {
                var node = getSelectedNode();
                if (!node || !selection.kind) {
                    return false;
                }
                if (isModSel()) {
                    styleClip = {
                        target: 'module',
                        design: omitPositionKeys(cloneBag(node.design) || {}) || {},
                        design_tablet: omitPositionKeys(cloneBag(node.design_tablet)),
                        design_mobile: omitPositionKeys(cloneBag(node.design_mobile)),
                        design_hover: cloneBag(node.design_hover)
                    };
                } else {
                    var settings = JSON.parse(JSON.stringify(node.settings || {}));
                    delete settings.css_class;
                    delete settings.col_reverse_mobile;
                    omitPositionKeys(settings);
                    styleClip = {
                        target: 'box',
                        settings: settings,
                        settings_tablet: omitPositionKeys(cloneBag(node.settings_tablet)),
                        settings_mobile: omitPositionKeys(cloneBag(node.settings_mobile))
                    };
                }
                setStatus('Copied style');
                updateClipboardButtons();
                return true;
            }

            function pasteStyle() {
                var node = getSelectedNode();
                if (!node || !canPasteStyle()) {
                    setStatus('Select a matching block to paste style', true);
                    return;
                }
                if (styleClip.target === 'module') {
                    ['design', 'design_tablet', 'design_mobile', 'design_hover'].forEach(function (key) {
                        if (styleClip[key] && Object.keys(styleClip[key]).length) {
                            node[key] = JSON.parse(JSON.stringify(styleClip[key]));
                        } else {
                            delete node[key];
                        }
                    });
                    if (!node.design) {
                        node.design = {};
                    }
                } else {
                    var keepClass = node.settings && node.settings.css_class ? node.settings.css_class : '';
                    node.settings = JSON.parse(JSON.stringify(styleClip.settings || {}));
                    if (keepClass) {
                        node.settings.css_class = keepClass;
                    } else {
                        delete node.settings.css_class;
                    }
                    ['settings_tablet', 'settings_mobile'].forEach(function (key) {
                        if (styleClip[key] && Object.keys(styleClip[key]).length) {
                            node[key] = JSON.parse(JSON.stringify(styleClip[key]));
                        } else {
                            delete node[key];
                        }
                    });
                }
                noteLayoutChange();
                updateDirtyUi();
                if (typeof refreshSelectedLiveCss === 'function') {
                    refreshSelectedLiveCss(node);
                } else {
                    refreshLiveCss();
                }
                if (isColSel()) {
                    patchLiveChrome('valign', styleFieldValue(node, 'settings', 'valign'));
                    patchLiveChrome('width', node.width);
                }
                scheduleHistoryCommit(true);
                renderPanel();
                setStatus('Pasted style');
            }
            
            function cutSelected() {
                if (!copySelected()) {
                    return;
                }
                var fake = document.createElement('button');
                fake.setAttribute('data-si', String(selection.sectionIdx));
                fake.setAttribute('data-ri', String(selection.rowIdx));
                fake.setAttribute('data-ci', String(selection.colIdx));
                fake.setAttribute('data-mi', String(selection.modIdx));
                fake.setAttribute('data-ici', String(selIci()));
                fake.setAttribute('data-imi', String(selImi()));
                var map = {
                    section: 'del-section',
                    row: 'del-row',
                    column: 'del-col',
                    module: 'del-mod',
                    inner_column: 'del-inner-col',
                    inner_module: 'del-inner-mod'
                };
                var action = map[selection.kind];
                if (action === 'del-mod' || action === 'del-col' || action === 'del-inner-mod' || action === 'del-inner-col') {
                    var si = selection.sectionIdx;
                    var ri = selection.rowIdx;
                    var ci = selection.colIdx;
                    var mi = selection.modIdx;
                    if (action === 'del-mod') {
                        var col = getColAt(si, ri, ci);
                        if (col && col.modules) {
                            col.modules.splice(mi, 1);
                        }
                    } else if (action === 'del-inner-mod') {
                        var innerCut = getInnerColAt(si, ri, ci, mi, selIci());
                        if (innerCut && innerCut.modules) {
                            innerCut.modules.splice(selImi(), 1);
                        }
                    } else if (action === 'del-inner-col') {
                        var innerRowCut = getInnerRowAt(si, ri, ci, mi);
                        if (innerRowCut && innerRowCut.columns && innerRowCut.columns.length > 1) {
                            innerRowCut.columns.splice(selIci(), 1);
                        } else if (innerRowCut && innerRowCut.columns && innerRowCut.columns[0]) {
                            innerRowCut.columns[0].modules = [];
                        }
                    } else {
                        var row = layout.sections[si] && layout.sections[si].rows && layout.sections[si].rows[ri];
                        if (row && row.columns && row.columns.length > 1) {
                            row.columns.splice(ci, 1);
                        } else if (row && row.columns && row.columns[0]) {
                            row.columns[0].modules = [];
                        }
                    }
                    clearSelection();
                    noteLayoutChange();
                    render();
                    setStatus('Cut');
                    return;
                }
                handleAction(action, fake);
            }
            
            function pasteClipboard() {
                if (!clip || !clip.payload) {
                    setStatus('Nothing to paste', true);
                    return;
                }
                var payload = reindexCopy(clip.payload);
                if (clip.kind === 'module' || clip.kind === 'inner_module') {
                    if (payload.type === 'inner_row' && (selection.kind === 'inner_column' || selection.kind === 'inner_module')) {
                        setStatus('Only one nested row is allowed', true);
                        return;
                    }
                    if (selection.kind === 'inner_column' || selection.kind === 'inner_module') {
                        var innerPaste = getInnerColAt(selection.sectionIdx, selection.rowIdx, selection.colIdx, selection.modIdx, selIci());
                        if (!innerPaste) {
                            setStatus('Select an inner column to paste into', true);
                            return;
                        }
                        innerPaste.modules = innerPaste.modules || [];
                        var innerAt = selection.kind === 'inner_module' ? selImi() + 1 : innerPaste.modules.length;
                        innerPaste.modules.splice(innerAt, 0, payload);
                        selection = {
                            kind: 'inner_module',
                            sectionIdx: selection.sectionIdx,
                            rowIdx: selection.rowIdx,
                            colIdx: selection.colIdx,
                            modIdx: selection.modIdx,
                            innerColIdx: selIci(),
                            innerModIdx: innerAt
                        };
                        openPanel();
                        noteLayoutChange();
                        render();
                        setStatus('Pasted module');
                        return;
                    }
                    var si = selection.sectionIdx >= 0 ? selection.sectionIdx : 0;
                    var ri = selection.rowIdx >= 0 ? selection.rowIdx : 0;
                    var ci = selection.colIdx >= 0 ? selection.colIdx : 0;
                    var col = getColAt(si, ri, ci);
                    if (!col) {
                        setStatus('Select a column to paste into', true);
                        return;
                    }
                    col.modules = col.modules || [];
                    var at = selection.kind === 'module' ? selection.modIdx + 1 : col.modules.length;
                    col.modules.splice(at, 0, payload);
                    selection = { kind: 'module', sectionIdx: si, rowIdx: ri, colIdx: ci, modIdx: at };
                    openPanel();
                    noteLayoutChange();
                    render();
                    setStatus('Pasted module');
                    return;
                }
                if (clip.kind === 'column') {
                    var sec = layout.sections[selection.sectionIdx];
                    var row2 = sec && sec.rows && sec.rows[selection.rowIdx >= 0 ? selection.rowIdx : 0];
                    if (!row2) {
                        setStatus('Select a row to paste the column', true);
                        return;
                    }
                    row2.columns = row2.columns || [];
                    if (row2.columns.length >= 6) {
                        setStatus('Maximum 6 columns in a row', true);
                        return;
                    }
                    var cAt = selection.colIdx >= 0 ? selection.colIdx + 1 : row2.columns.length;
                    row2.columns.splice(cAt, 0, payload);
                    selection = { kind: 'column', sectionIdx: selection.sectionIdx, rowIdx: selection.rowIdx >= 0 ? selection.rowIdx : 0, colIdx: cAt, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    setStatus('Pasted column');
                    return;
                }
                if (clip.kind === 'inner_column') {
                    var innerRowPaste = getInnerRowAt(
                        selection.sectionIdx,
                        selection.rowIdx,
                        selection.colIdx,
                        selection.kind === 'module' ? selection.modIdx : selection.modIdx
                    );
                    if (!innerRowPaste && selection.kind === 'module') {
                        var maybe = getSelectedNode();
                        innerRowPaste = maybe && maybe.type === 'inner_row' ? maybe : null;
                    }
                    if (!innerRowPaste) {
                        setStatus('Select an inner row to paste the column', true);
                        return;
                    }
                    innerRowPaste.columns = innerRowPaste.columns || [];
                    if (innerRowPaste.columns.length >= 4) {
                        setStatus('Maximum 4 columns in an inner row', true);
                        return;
                    }
                    var icAt = selIci() >= 0 ? selIci() + 1 : innerRowPaste.columns.length;
                    innerRowPaste.columns.splice(icAt, 0, payload);
                    selection = {
                        kind: 'inner_column',
                        sectionIdx: selection.sectionIdx,
                        rowIdx: selection.rowIdx,
                        colIdx: selection.colIdx,
                        modIdx: selection.modIdx,
                        innerColIdx: icAt,
                        innerModIdx: -1
                    };
                    openPanel();
                    noteLayoutChange();
                    render();
                    setStatus('Pasted column');
                    return;
                }
                if (clip.kind === 'row') {
                    var sec2 = layout.sections[selection.sectionIdx >= 0 ? selection.sectionIdx : 0];
                    if (!sec2) {
                        setStatus('Select a section to paste the row', true);
                        return;
                    }
                    sec2.rows = sec2.rows || [];
                    var rAt = selection.rowIdx >= 0 ? selection.rowIdx + 1 : sec2.rows.length;
                    sec2.rows.splice(rAt, 0, payload);
                    selection = { kind: 'row', sectionIdx: selection.sectionIdx >= 0 ? selection.sectionIdx : 0, rowIdx: rAt, colIdx: -1, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    setStatus('Pasted row');
                    return;
                }
                if (clip.kind === 'section') {
                    var sAt = selection.sectionIdx >= 0 ? selection.sectionIdx + 1 : layout.sections.length;
                    layout.sections.splice(sAt, 0, payload);
                    selection = { kind: 'section', sectionIdx: sAt, rowIdx: -1, colIdx: -1, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                    setStatus('Pasted section');
                }
            }
            
            function nudgeSelected(dir) {
                if (!selection.kind || !dir) {
                    return;
                }
                var fake = document.createElement('button');
                fake.setAttribute('data-si', String(selection.sectionIdx));
                fake.setAttribute('data-ri', String(selection.rowIdx));
                fake.setAttribute('data-ci', String(selection.colIdx));
                fake.setAttribute('data-mi', String(selection.modIdx));
                fake.setAttribute('data-ici', String(selIci()));
                fake.setAttribute('data-imi', String(selImi()));
                if (selection.kind === 'module') {
                    handleAction(dir < 0 ? 'mod-up' : 'mod-down', fake);
                } else if (selection.kind === 'inner_module') {
                    handleAction(dir < 0 ? 'inner-mod-up' : 'inner-mod-down', fake);
                } else if (selection.kind === 'row') {
                    handleAction(dir < 0 ? 'row-up' : 'row-down', fake);
                } else if (selection.kind === 'section') {
                    handleAction(dir < 0 ? 'sec-up' : 'sec-down', fake);
                } else if (selection.kind === 'column') {
                    var row = layout.sections[selection.sectionIdx] && layout.sections[selection.sectionIdx].rows
                        && layout.sections[selection.sectionIdx].rows[selection.rowIdx];
                    if (!row || !row.columns) {
                        return;
                    }
                    var ci = selection.colIdx;
                    var to = ci + dir;
                    if (to < 0 || to >= row.columns.length) {
                        return;
                    }
                    var tmp = row.columns[ci];
                    row.columns[ci] = row.columns[to];
                    row.columns[to] = tmp;
                    selection = { kind: 'column', sectionIdx: selection.sectionIdx, rowIdx: selection.rowIdx, colIdx: to, modIdx: -1 };
                    openPanel();
                    noteLayoutChange();
                    render();
                } else if (selection.kind === 'inner_column') {
                    var innerRowNudge = getInnerRowAt(selection.sectionIdx, selection.rowIdx, selection.colIdx, selection.modIdx);
                    if (!innerRowNudge || !innerRowNudge.columns) {
                        return;
                    }
                    var iciN = selIci();
                    var innerToCol = iciN + dir;
                    if (innerToCol < 0 || innerToCol >= innerRowNudge.columns.length) {
                        return;
                    }
                    var tmpIc = innerRowNudge.columns[iciN];
                    innerRowNudge.columns[iciN] = innerRowNudge.columns[innerToCol];
                    innerRowNudge.columns[innerToCol] = tmpIc;
                    selection = {
                        kind: 'inner_column',
                        sectionIdx: selection.sectionIdx,
                        rowIdx: selection.rowIdx,
                        colIdx: selection.colIdx,
                        modIdx: selection.modIdx,
                        innerColIdx: innerToCol,
                        innerModIdx: -1
                    };
                    openPanel();
                    noteLayoutChange();
                    render();
                }
            }
            
            ctx.selectFromEl = selectFromEl;
            ctx.selectAncestor = selectAncestor;
            ctx.handleAction = handleAction;
            ctx.insertModule = insertModule;
            ctx.renderModuleTypePicker = renderModuleTypePicker;
            ctx.clearSelection = clearSelection;
            ctx.openPanel = openPanel;
            ctx.getSelectedNode = getSelectedNode;
            ctx.isModSel = isModSel;
            ctx.isColSel = isColSel;
            ctx.selIci = selIci;
            ctx.selImi = selImi;
            ctx.getInnerRowAt = getInnerRowAt;
            ctx.getInnerColAt = getInnerColAt;
            ctx.duplicateSelected = duplicateSelected;
            ctx.deleteSelected = deleteSelected;
            ctx.copySelected = copySelected;
            ctx.cutSelected = cutSelected;
            ctx.pasteClipboard = pasteClipboard;
            ctx.copyStyle = copyStyle;
            ctx.pasteStyle = pasteStyle;
            ctx.canPasteStyle = canPasteStyle;
            ctx.nudgeSelected = nudgeSelected;
        }
    });
})();
