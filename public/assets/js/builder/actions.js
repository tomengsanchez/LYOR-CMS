/**
 * Visual layout builder — select, chrome actions, copy/paste.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function selectFromEl(el) {
                var kind = el.getAttribute('data-kind');
                var prevKind = selection.kind;
                if (kind !== 'column') {
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
                    modIdx: el.hasAttribute('data-mi') ? Number(el.getAttribute('data-mi')) : -1
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
                }
            }
            
            function insertModule(type, si, ri, ci, afterMi) {
                if (!moduleTypes[type]) {
                    setStatus('Unknown module type', true);
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
                var html = '<div class="cms-lb-mod-picker" data-si="' + si + '" data-ri="' + ri + '" data-ci="' + ci + '" data-after="' + afterMi + '">';
                html += '<p class="cms-lb-field-group">Add module</p>';
                if (afterMi >= 0) {
                    html += '<p class="cms-lb-panel-lead">Insert after the selected module.</p>';
                } else {
                    html += '<p class="cms-lb-panel-lead">Choose a module to insert in this column. You can change its settings next.</p>';
                }
                html += '<input type="search" class="form-control form-control-sm mb-2" data-mod-filter placeholder="Filter modules" aria-label="Filter modules">';
                html += '<div class="cms-lb-mod-picker-grid">';
                Object.keys(moduleTypes).forEach(function (key) {
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
                var map = { section: 'dup-section', row: 'dup-row', column: 'dup-col', module: 'dup-mod' };
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
                var map = { section: 'del-section', row: 'del-row', column: 'del-col', module: 'del-mod' };
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
                    return selection.kind === 'module';
                }
                return selection.kind === 'section' || selection.kind === 'row' || selection.kind === 'column';
            }

            function copyStyle() {
                var node = getSelectedNode();
                if (!node || !selection.kind) {
                    return false;
                }
                if (selection.kind === 'module') {
                    styleClip = {
                        target: 'module',
                        design: cloneBag(node.design) || {},
                        design_tablet: cloneBag(node.design_tablet),
                        design_mobile: cloneBag(node.design_mobile),
                        design_hover: cloneBag(node.design_hover)
                    };
                } else {
                    var settings = JSON.parse(JSON.stringify(node.settings || {}));
                    delete settings.css_class;
                    styleClip = {
                        target: 'box',
                        settings: settings,
                        settings_tablet: cloneBag(node.settings_tablet),
                        settings_mobile: cloneBag(node.settings_mobile)
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
                if (selection.kind === 'column') {
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
                var map = { section: 'del-section', row: 'del-row', column: 'del-col', module: 'del-mod' };
                var action = map[selection.kind];
                if (action === 'del-mod' || action === 'del-col') {
                    var si = selection.sectionIdx;
                    var ri = selection.rowIdx;
                    var ci = selection.colIdx;
                    var mi = selection.modIdx;
                    if (action === 'del-mod') {
                        var col = getColAt(si, ri, ci);
                        if (col && col.modules) {
                            col.modules.splice(mi, 1);
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
                if (clip.kind === 'module') {
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
                if (selection.kind === 'module') {
                    handleAction(dir < 0 ? 'mod-up' : 'mod-down', fake);
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
