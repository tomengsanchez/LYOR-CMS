/**
 * Visual layout builder — undo, dirty flag, autosave.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function layoutSnapshot() {
                return JSON.stringify(layout);
            }
            
            function isDirty() {
                return layoutSnapshot() !== savedSnapshot;
            }

            function cloneSelection() {
                return {
                    kind: selection.kind,
                    sectionIdx: selection.sectionIdx,
                    rowIdx: selection.rowIdx,
                    colIdx: selection.colIdx,
                    modIdx: selection.modIdx
                };
            }
            
            function noteLayoutChange() {
                historyPending = true;
                scheduleAutosave();
            }
            
            function commitHistoryNow() {
                if (historyLock) {
                    return;
                }
                var json = layoutSnapshot();
                var prev = historyIndex >= 0 ? historyStack[historyIndex] : null;
                if (prev && prev.json === json) {
                    updateUndoButtons();
                    return;
                }
                historyStack = historyStack.slice(0, historyIndex + 1);
                historyStack.push({ json: json, selection: cloneSelection(), tab: activeTab });
                if (historyStack.length > HISTORY_MAX) {
                    historyStack.shift();
                }
                historyIndex = historyStack.length - 1;
                updateUndoButtons();
            }
            
            function updateUndoButtons() {
                var undoBtn = document.getElementById('cmsBuilderUndo');
                var redoBtn = document.getElementById('cmsBuilderRedo');
                if (undoBtn) {
                    undoBtn.disabled = historyIndex <= 0;
                }
                if (redoBtn) {
                    redoBtn.disabled = historyIndex < 0 || historyIndex >= historyStack.length - 1;
                }
            }
            
            function updateDirtyUi() {
                var saveBtn = document.getElementById('cmsBuilderSave');
                var dirty = isDirty();
                if (saveBtn) {
                    saveBtn.classList.toggle('is-dirty', dirty);
                    saveBtn.textContent = dirty ? 'Save •' : 'Save';
                }
                if (dirty) {
                    scheduleAutosave();
                }
                updateClipboardButtons();
            }
            
            function updateClipboardButtons() {
                var copyBtn = document.getElementById('cmsBuilderCopy');
                var pasteBtn = document.getElementById('cmsBuilderPaste');
                if (copyBtn) {
                    copyBtn.disabled = !selection.kind;
                }
                if (pasteBtn) {
                    pasteBtn.disabled = !(clip && clip.payload);
                }
            }
            
            function scheduleAutosave() {
                if (autoSaveTimer) {
                    clearTimeout(autoSaveTimer);
                }
                autoSaveTimer = setTimeout(function () {
                    autoSaveTimer = null;
                    if (!isDirty()) {
                        return;
                    }
                    if (isTypingTarget(document.activeElement)) {
                        scheduleAutosave();
                        return;
                    }
                    save(true);
                }, AUTOSAVE_MS);
            }
            
            function restoreHistory() {
                var entry = historyStack[historyIndex];
                if (!entry) {
                    return;
                }
                historyLock = true;
                try {
                    layout = JSON.parse(entry.json);
                    if (!layout.sections) {
                        layout.sections = [];
                    }
                    selection = entry.selection || { kind: null, sectionIdx: -1, rowIdx: -1, colIdx: -1, modIdx: -1 };
                    modulePickTarget = null;
                    setPanelTab(entry.tab || 'content');
                    if (selection.kind) {
                        openPanel();
                    } else if (panel) {
                        panel.hidden = true;
                    }
                    render();
                } catch (err) {
                    setStatus('Could not restore layout', true);
                }
                historyLock = false;
                updateUndoButtons();
                updateDirtyUi();
            }
            
            function undo() {
                commitHistoryNow();
                if (historyIndex <= 0) {
                    return;
                }
                historyIndex -= 1;
                restoreHistory();
                setStatus('Undo');
            }
            
            function redo() {
                if (historyIndex >= historyStack.length - 1) {
                    return;
                }
                historyIndex += 1;
                restoreHistory();
                setStatus('Redo');
            }
            
            function resetHistory() {
                historyStack = [];
                historyIndex = -1;
                historyPending = false;
                commitHistoryNow();
            }
            
            function isTypingTarget(el) {
                if (!el || el.nodeType !== 1) {
                    return false;
                }
                var tag = (el.tagName || '').toLowerCase();
                return tag === 'input' || tag === 'textarea' || tag === 'select' || !!el.isContentEditable;
            }
            
            function scheduleCanvasRender(immediate) {
                if (renderTimer) {
                    clearTimeout(renderTimer);
                    renderTimer = null;
                }
                if (immediate) {
                    render();
                    return;
                }
                renderTimer = setTimeout(function () {
                    renderTimer = null;
                    render();
                }, 160);
            }
            
            function setPanelTab(tab) {
                activeTab = tab || 'content';
                document.querySelectorAll('[data-panel-tab]').forEach(function (t) {
                    var on = t.getAttribute('data-panel-tab') === activeTab;
                    t.classList.toggle('active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                var body = document.getElementById('cmsBuilderPanelBody');
                var activeBtn = document.querySelector('[data-panel-tab="' + activeTab + '"]');
                if (body && activeBtn && activeBtn.id) {
                    body.setAttribute('aria-labelledby', activeBtn.id);
                }
            }
            
            ctx.layoutSnapshot = layoutSnapshot;
            ctx.isDirty = isDirty;
            ctx.cloneSelection = cloneSelection;
            ctx.noteLayoutChange = noteLayoutChange;
            ctx.commitHistoryNow = commitHistoryNow;
            ctx.updateUndoButtons = updateUndoButtons;
            ctx.updateDirtyUi = updateDirtyUi;
            ctx.updateClipboardButtons = updateClipboardButtons;
            ctx.scheduleAutosave = scheduleAutosave;
            ctx.restoreHistory = restoreHistory;
            ctx.undo = undo;
            ctx.redo = redo;
            ctx.resetHistory = resetHistory;
            ctx.isTypingTarget = isTypingTarget;
            ctx.scheduleCanvasRender = scheduleCanvasRender;
            ctx.setPanelTab = setPanelTab;
        }
    });
})();
