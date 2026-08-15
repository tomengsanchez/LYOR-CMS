/**
 * Visual layout builder — save, device, templates.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            function save(fromAuto) {
                if (saveInFlight) {
                    if (!fromAuto) {
                        saveQueued = true;
                    }
                    return;
                }
                if (fromAuto && !isDirty()) {
                    return;
                }
                saveInFlight = true;
                setStatus(fromAuto ? 'Autosaving…' : 'Saving…');
                var fd = new FormData();
                fd.append('csrf_token', csrf);
                fd.append('layout_json', JSON.stringify(layout));
                fetch(saveUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                }).then(function (res) {
                    return res.json().then(function (json) {
                        if (!res.ok || !json || !json.success) {
                            throw new Error((json && json.error && json.error.message) || 'Save failed');
                        }
                        if (json.data && json.data.layout && !fromAuto) {
                            layout = json.data.layout;
                        }
                        setStatus(fromAuto ? 'Autosaved' : 'Saved');
                        savedSnapshot = layoutSnapshot();
                        if (!fromAuto) {
                            render();
                        }
                        updateDirtyUi();
                    });
                }).catch(function (err) {
                    setStatus(err.message || 'Save failed', true);
                }).then(function () {
                    saveInFlight = false;
                    if (saveQueued) {
                        saveQueued = false;
                        save(false);
                    }
                });
            }
            
            function setDevice(device) {
                currentDevice = device === 'tablet' || device === 'mobile' ? device : 'desktop';
                if (canvasWrap) {
                    canvasWrap.setAttribute('data-device', currentDevice);
                }
                document.querySelectorAll('.cms-builder-device-toggle [data-device]').forEach(function (btn) {
                    var on = btn.getAttribute('data-device') === currentDevice;
                    btn.classList.toggle('is-active', on);
                    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
            }
            
            function renderTemplatesMenu() {
                var menu = document.getElementById('cmsBuilderTemplatesMenu');
                var emptyEl = document.getElementById('cmsBuilderTemplatesEmpty');
                if (!menu) {
                    return;
                }
                menu.querySelectorAll('[data-template-id]').forEach(function (n) {
                    n.parentNode && n.parentNode.removeChild(n);
                });
                menu.querySelectorAll('[data-template-delete]').forEach(function (n) {
                    n.parentNode && n.parentNode.removeChild(n);
                });
                if (emptyEl) {
                    emptyEl.style.display = templatesList.length ? 'none' : '';
                }
                var insertBefore = menu.querySelector('.dropdown-divider');
                templatesList.forEach(function (tpl) {
                    var li = document.createElement('li');
                    var row = document.createElement('div');
                    row.className = 'dropdown-item d-flex align-items-center justify-content-between gap-2';
                    row.setAttribute('data-template-id', String(tpl.id));
                    var loadBtn = document.createElement('button');
                    loadBtn.type = 'button';
                    loadBtn.className = 'btn btn-link btn-sm p-0 text-start flex-grow-1 text-decoration-none';
                    loadBtn.textContent = tpl.name || ('Template #' + tpl.id);
                    loadBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        loadTemplate(Number(tpl.id));
                    });
                    var delBtn = document.createElement('button');
                    delBtn.type = 'button';
                    delBtn.className = 'btn btn-link btn-sm p-0 text-danger';
                    delBtn.setAttribute('data-template-delete', String(tpl.id));
                    delBtn.title = 'Delete template';
                    delBtn.textContent = '×';
                    delBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        deleteTemplate(Number(tpl.id), tpl.name || '');
                    });
                    row.appendChild(loadBtn);
                    row.appendChild(delBtn);
                    li.appendChild(row);
                    if (insertBefore) {
                        menu.insertBefore(li, insertBefore);
                    } else {
                        menu.appendChild(li);
                    }
                });
            }
            
            function loadTemplate(id) {
                if (!templatesUrl || !id) {
                    return;
                }
                setStatus('Loading template…');
                fetch(templatesUrl.replace(/\/$/, '') + '/' + id, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                }).then(function (res) {
                    return res.json().then(function (json) {
                        if (!res.ok || !json || !json.success || !json.data || !json.data.layout) {
                            throw new Error((json && json.error && json.error.message) || 'Load failed');
                        }
                        layout = json.data.layout;
                        clearSelection();
                        setStatus('Template applied (not saved yet)');
                        noteLayoutChange();
                        render();
                    });
                }).catch(function (err) {
                    setStatus(err.message || 'Load failed', true);
                });
            }
            
            function saveAsTemplate() {
                if (!templatesUrl) {
                    setStatus('Templates unavailable', true);
                    return;
                }
                var name = window.prompt('Template name');
                if (name == null) {
                    return;
                }
                name = String(name).trim();
                if (!name) {
                    setStatus('Name required', true);
                    return;
                }
                setStatus('Saving template…');
                fetch(templatesUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf_token: csrf,
                        name: name,
                        layout: layout
                    })
                }).then(function (res) {
                    return res.json().then(function (json) {
                        if (!res.ok || !json || !json.success) {
                            throw new Error((json && json.error && json.error.message) || 'Template save failed');
                        }
                        templatesList.push({
                            id: json.data.id,
                            name: json.data.name || name
                        });
                        templatesList.sort(function (a, b) {
                            return String(a.name).localeCompare(String(b.name));
                        });
                        renderTemplatesMenu();
                        setStatus('Template saved');
                    });
                }).catch(function (err) {
                    setStatus(err.message || 'Template save failed', true);
                });
            }
            
            function deleteTemplate(id, name) {
                if (!templatesUrl || !id) {
                    return;
                }
                if (!window.confirm('Delete template "' + (name || id) + '"?')) {
                    return;
                }
                fetch(templatesUrl.replace(/\/$/, '') + '/' + id + '/delete', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ csrf_token: csrf })
                }).then(function (res) {
                    return res.json().then(function (json) {
                        if (!res.ok || !json || !json.success) {
                            throw new Error((json && json.error && json.error.message) || 'Delete failed');
                        }
                        templatesList = templatesList.filter(function (t) {
                            return Number(t.id) !== Number(id);
                        });
                        renderTemplatesMenu();
                        setStatus('Template deleted');
                    });
                }).catch(function (err) {
                    setStatus(err.message || 'Delete failed', true);
                });
            }
            
            ctx.save = save;
            ctx.setDevice = setDevice;
            ctx.renderTemplatesMenu = renderTemplatesMenu;
            ctx.loadTemplate = loadTemplate;
            ctx.saveAsTemplate = saveAsTemplate;
            ctx.deleteTemplate = deleteTemplate;
        }
    });
})();
