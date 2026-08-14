(function () {
    'use strict';

    var builder = document.getElementById('cmsBlockBuilder');
    var listEl = document.getElementById('cmsBlockList');
    var inputEl = document.getElementById('blocksJsonInput');
    var form = document.getElementById('pageForm') || document.getElementById('postForm');
    if (!builder || !listEl || !inputEl) {
        return;
    }

    var blockTypes = {};
    var mediaImages = [];
    var canUpload = builder.getAttribute('data-can-upload') === '1';
    try {
        blockTypes = JSON.parse(builder.getAttribute('data-types') || '{}');
    } catch (e) {
        blockTypes = {};
    }
    try {
        mediaImages = JSON.parse(builder.getAttribute('data-media') || '[]');
    } catch (e2) {
        mediaImages = [];
    }

    var blocks = [];
    try {
        blocks = JSON.parse(builder.getAttribute('data-initial') || '[]');
    } catch (e3) {
        blocks = [];
    }
    if (!Array.isArray(blocks)) {
        blocks = [];
    }

    function escHtml(s) {
        if (window.CmsMediaPicker && window.CmsMediaPicker.escHtml) {
            return window.CmsMediaPicker.escHtml(s);
        }
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    function addMediaItem(item) {
        if (!item || !item.id) {
            return;
        }
        var exists = mediaImages.some(function (m) { return String(m.id) === String(item.id); });
        if (!exists) {
            mediaImages.unshift(item);
        }
        listEl.querySelectorAll('.block-field[data-key="media_id"]').forEach(function (sel) {
            if (sel.querySelector('option[value="' + String(item.id) + '"]')) {
                return;
            }
            var opt = document.createElement('option');
            opt.value = String(item.id);
            opt.setAttribute('data-url', item.share_url || item.url || '');
            opt.textContent = item.name || ('Media #' + item.id);
            sel.appendChild(opt);
        });
    }

    function mediaOptions(selectedId, selectedUrl) {
        var html = '<option value="">— Select image —</option>';
        mediaImages.forEach(function (m) {
            var id = String(m.id || '');
            var url = m.share_url || m.url || '';
            var sel = '';
            if (selectedId && String(selectedId) === id) {
                sel = ' selected';
            } else if (!selectedId && selectedUrl && (url === selectedUrl || (m.url && m.url === selectedUrl))) {
                sel = ' selected';
            }
            html += '<option value="' + escHtml(id) + '" data-url="' + escHtml(url) + '"' + sel + '>' + escHtml(m.name || url) + '</option>';
        });
        return html;
    }

    function previewHtml(data) {
        var url = '';
        var id = data.media_id ? String(data.media_id) : '';
        if (id) {
            var found = mediaImages.find(function (m) { return String(m.id) === id; });
            url = found ? (found.preview || found.url || '') : ('/serve/media/' + id + '/medium');
        } else if (data.url) {
            url = data.url;
        }
        if (!url) {
            return '<div class="cms-block-image-preview d-none" data-block-preview></div>';
        }
        return '<div class="cms-block-image-preview" data-block-preview><img src="' + escHtml(url) + '" alt=""></div>';
    }

    function blockFields(type, data) {
        data = data || {};
        switch (type) {
            case 'heading':
                return '<label class="form-label small">Text</label>' +
                    '<input type="text" class="form-control form-control-sm block-field" data-key="text" value="' + escHtml(data.text) + '">' +
                    '<label class="form-label small mt-2">Level</label>' +
                    '<select class="form-select form-select-sm block-field" data-key="level">' +
                    ['2', '3', '4'].map(function (l) {
                        return '<option value="' + l + '"' + (String(data.level || 2) === l ? ' selected' : '') + '>H' + l + '</option>';
                    }).join('') + '</select>';
            case 'paragraph':
                return '<label class="form-label small">Text</label>' +
                    '<textarea class="form-control form-control-sm block-field" data-key="text" rows="3">' + escHtml(data.text) + '</textarea>';
            case 'image':
                return '<label class="form-label small">Image</label>' +
                    '<select class="form-select form-select-sm block-field" data-key="media_id">' + mediaOptions(data.media_id, data.url) + '</select>' +
                    '<input type="hidden" class="block-field" data-key="url" value="' + escHtml(data.url || '') + '">' +
                    (canUpload
                        ? '<div class="cms-block-image-tools">' +
                          '<button type="button" class="btn btn-outline-primary btn-sm" data-block-upload-btn>Upload image</button>' +
                          '<input type="file" class="d-none" data-block-upload accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp">' +
                          '<span class="small text-muted" data-block-upload-status></span>' +
                          '</div>'
                        : '') +
                    previewHtml(data) +
                    '<label class="form-label small mt-2">Alt text</label>' +
                    '<input type="text" class="form-control form-control-sm block-field" data-key="alt" value="' + escHtml(data.alt) + '">';
            case 'columns':
                return '<label class="form-label small">Left column</label>' +
                    '<textarea class="form-control form-control-sm block-field" data-key="left" rows="3">' + escHtml(data.left) + '</textarea>' +
                    '<label class="form-label small mt-2">Right column</label>' +
                    '<textarea class="form-control form-control-sm block-field" data-key="right" rows="3">' + escHtml(data.right) + '</textarea>';
            case 'cta':
                return '<label class="form-label small">Text</label>' +
                    '<textarea class="form-control form-control-sm block-field" data-key="text" rows="2">' + escHtml(data.text) + '</textarea>' +
                    '<label class="form-label small mt-2">Button label</label>' +
                    '<input type="text" class="form-control form-control-sm block-field" data-key="label" value="' + escHtml(data.label || 'Learn more') + '">' +
                    '<label class="form-label small mt-2">Button URL</label>' +
                    '<input type="text" class="form-control form-control-sm block-field" data-key="url" value="' + escHtml(data.url || '#') + '">';
            case 'spacer':
                return '<label class="form-label small">Size</label>' +
                    '<select class="form-select form-select-sm block-field" data-key="size">' +
                    ['sm', 'md', 'lg'].map(function (s) {
                        return '<option value="' + s + '"' + ((data.size || 'md') === s ? ' selected' : '') + '>' + s.toUpperCase() + '</option>';
                    }).join('') + '</select>';
            case 'html':
                return '<label class="form-label small">Custom HTML</label>' +
                    '<textarea class="form-control form-control-sm block-field" data-key="html" rows="4">' + escHtml(data.html) + '</textarea>';
            default:
                return '';
        }
    }

    function updateRowPreview(row) {
        var sel = row.querySelector('.block-field[data-key="media_id"]');
        var preview = row.querySelector('[data-block-preview]');
        if (!preview || !sel) {
            return;
        }
        var opt = sel.options[sel.selectedIndex];
        var id = sel.value;
        var url = '';
        if (id) {
            var found = mediaImages.find(function (m) { return String(m.id) === String(id); });
            url = found ? (found.preview || found.url || '') : ('/serve/media/' + id + '/medium');
        }
        if (!url && opt) {
            url = opt.getAttribute('data-url') || '';
        }
        if (!url) {
            preview.classList.add('d-none');
            preview.innerHTML = '';
            return;
        }
        preview.classList.remove('d-none');
        preview.innerHTML = '<img src="' + escHtml(url) + '" alt="">';
    }

    function readBlockFromRow(row) {
        var type = row.getAttribute('data-type');
        var data = {};
        row.querySelectorAll('.block-field').forEach(function (field) {
            data[field.getAttribute('data-key')] = field.value;
        });
        return { type: type, data: data };
    }

    function syncInput() {
        var out = [];
        listEl.querySelectorAll('.cms-block-row').forEach(function (row) {
            out.push(readBlockFromRow(row));
        });
        inputEl.value = out.length ? JSON.stringify(out) : '';
    }

    function render() {
        listEl.innerHTML = '';
        blocks.forEach(function (block, index) {
            listEl.appendChild(createRow(block.type, block.data || {}, index));
        });
        syncInput();
    }

    function bindImageUpload(row) {
        var btn = row.querySelector('[data-block-upload-btn]');
        var input = row.querySelector('[data-block-upload]');
        var status = row.querySelector('[data-block-upload-status]');
        var select = row.querySelector('.block-field[data-key="media_id"]');
        if (!btn || !input || !select || !window.CmsMediaPicker) {
            return;
        }
        function setStatus(msg, isError) {
            if (!status) {
                return;
            }
            status.textContent = msg || '';
            status.classList.toggle('text-danger', !!isError);
        }
        btn.addEventListener('click', function () {
            input.click();
        });
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) {
                return;
            }
            setStatus('Uploading…');
            btn.disabled = true;
            window.CmsMediaPicker.uploadFile(file).then(function (item) {
                if (item.is_image === false) {
                    setStatus('Only images can be used in blocks.', true);
                    return;
                }
                addMediaItem(item);
                select.value = String(item.id);
                var urlField = row.querySelector('.block-field[data-key="url"]');
                if (urlField) {
                    urlField.value = item.share_url || item.url || '';
                }
                var altField = row.querySelector('.block-field[data-key="alt"]');
                if (altField && !altField.value && item.alt_text) {
                    altField.value = item.alt_text;
                }
                updateRowPreview(row);
                syncInput();
                if (window.CmsMediaPicker.notifyMediaAdded) {
                    window.CmsMediaPicker.notifyMediaAdded(item);
                }
                setStatus('Uploaded.');
            }).catch(function (err) {
                setStatus(err.message || 'Upload failed.', true);
            }).finally(function () {
                input.value = '';
                btn.disabled = false;
            });
        });
    }

    function createRow(type, data, index) {
        var row = document.createElement('div');
        row.className = 'cms-block-row border rounded p-3 mb-2';
        row.setAttribute('data-type', type);
        row.innerHTML =
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
            '<strong>' + escHtml(blockTypes[type] || type) + '</strong>' +
            '<div class="btn-group btn-group-sm">' +
            '<button type="button" class="btn btn-outline-secondary block-move-up" aria-label="Move up">↑</button>' +
            '<button type="button" class="btn btn-outline-secondary block-move-down" aria-label="Move down">↓</button>' +
            '<button type="button" class="btn btn-outline-danger block-remove" aria-label="Remove">×</button>' +
            '</div></div>' +
            blockFields(type, data);

        row.querySelectorAll('.block-field').forEach(function (field) {
            field.addEventListener('input', syncInput);
            field.addEventListener('change', function () {
                if (field.getAttribute('data-key') === 'media_id') {
                    var opt = field.options[field.selectedIndex];
                    var urlField = row.querySelector('.block-field[data-key="url"]');
                    if (urlField && opt) {
                        urlField.value = opt.getAttribute('data-url') || '';
                    }
                    updateRowPreview(row);
                }
                syncInput();
            });
        });

        var mediaSelect = row.querySelector('.block-field[data-key="media_id"]');
        if (mediaSelect) {
            var selected = mediaSelect.options[mediaSelect.selectedIndex];
            var urlHidden = row.querySelector('.block-field[data-key="url"]');
            if (selected && urlHidden && !urlHidden.value) {
                urlHidden.value = selected.getAttribute('data-url') || '';
            }
            bindImageUpload(row);
            updateRowPreview(row);
        }

        row.querySelector('.block-remove').addEventListener('click', function () {
            row.remove();
            syncInput();
        });
        row.querySelector('.block-move-up').addEventListener('click', function () {
            var prev = row.previousElementSibling;
            if (prev) {
                listEl.insertBefore(row, prev);
                syncInput();
            }
        });
        row.querySelector('.block-move-down').addEventListener('click', function () {
            var next = row.nextElementSibling;
            if (next) {
                listEl.insertBefore(next, row);
                syncInput();
            }
        });

        return row;
    }

    builder.querySelectorAll('[data-add-block]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type = btn.getAttribute('data-add-block');
            listEl.appendChild(createRow(type, {}, listEl.children.length));
            syncInput();
        });
    });

    document.addEventListener('cms:media-uploaded', function (ev) {
        if (ev.detail) {
            addMediaItem(ev.detail);
            var featured = document.getElementById('featuredImageSelect');
            if (featured && window.CmsMediaPicker) {
                window.CmsMediaPicker.addToFeaturedSelect(featured, ev.detail, false);
            }
        }
    });

    if (form) {
        form.addEventListener('submit', syncInput);
    }

    render();
})();
