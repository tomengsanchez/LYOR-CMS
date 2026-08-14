/**
 * Shared media upload helper for page/post featured image + block builder.
 * Expects window.CmsMediaConfig = { uploadUrl, csrfToken, canUpload }
 */
(function (global) {
    'use strict';

    function config() {
        var form = document.getElementById('pageForm') || document.getElementById('postForm');
        var fromForm = form ? {
            uploadUrl: form.getAttribute('data-media-upload-url') || '',
            canUpload: form.getAttribute('data-can-upload-media') === '1'
        } : {};
        var cfg = global.CmsMediaConfig || {};
        return {
            uploadUrl: cfg.uploadUrl || fromForm.uploadUrl || '/admin/media/upload-json',
            csrfToken: cfg.csrfToken || '',
            canUpload: cfg.canUpload !== undefined ? !!cfg.canUpload : !!fromForm.canUpload
        };
    }

    function csrfToken() {
        var cfg = config();
        if (cfg.csrfToken) {
            return cfg.csrfToken;
        }
        var field = document.querySelector('input[name="csrf_token"]');
        return field ? field.value : '';
    }

    function canUpload() {
        var cfg = config();
        return cfg.canUpload !== false;
    }

    function uploadUrl() {
        return (config().uploadUrl) || '/admin/media/upload-json';
    }

    function escHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    /**
     * @param {File} file
     * @param {string} [altText]
     * @returns {Promise<object>}
     */
    function uploadFile(file, altText) {
        if (!canUpload()) {
            return Promise.reject(new Error('Upload not allowed.'));
        }
        var fd = new FormData();
        fd.append('file', file);
        fd.append('csrf_token', csrfToken());
        if (altText) {
            fd.append('alt_text', altText);
        }
        return fetch(uploadUrl(), {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json || !json.success) {
                    var msg = (json && json.error && json.error.message) ? json.error.message : 'Upload failed.';
                    throw new Error(msg);
                }
                return json.data;
            });
        });
    }

    /** Add/select an item on a featured-image <select>. */
    function addToFeaturedSelect(selectEl, item, selectIt) {
        if (!selectEl || !item || !item.id) {
            return;
        }
        var existing = selectEl.querySelector('option[value="' + String(item.id) + '"]');
        if (!existing) {
            var opt = document.createElement('option');
            opt.value = String(item.id);
            opt.setAttribute('data-preview', item.preview || item.url || '');
            opt.setAttribute('data-width', item.width != null ? String(item.width) : '0');
            opt.setAttribute('data-height', item.height != null ? String(item.height) : '0');
            var label = item.name || ('Media #' + item.id);
            if (item.width && item.height) {
                label += ' (' + item.width + '×' + item.height + ')';
            }
            opt.textContent = label;
            selectEl.appendChild(opt);
            existing = opt;
        }
        if (selectIt !== false) {
            selectEl.value = String(item.id);
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    /** Notify block builder / others that a new image is available. */
    function notifyMediaAdded(item) {
        document.dispatchEvent(new CustomEvent('cms:media-uploaded', { detail: item }));
    }

    function bindFeaturedUpload(root) {
        root = root || document;
        var wrap = root.querySelector('[data-featured-media]');
        if (!wrap) {
            return;
        }
        var select = wrap.querySelector('#featuredImageSelect');
        var input = wrap.querySelector('[data-featured-upload]');
        var status = wrap.querySelector('[data-featured-upload-status]');
        var btn = wrap.querySelector('[data-featured-upload-btn]');
        if (!select || !input) {
            return;
        }

        function setStatus(msg, isError) {
            if (!status) {
                return;
            }
            status.textContent = msg || '';
            status.classList.toggle('text-danger', !!isError);
            status.classList.toggle('text-success', !!msg && !isError);
        }

        function startUpload(file) {
            if (!file) {
                return;
            }
            setStatus('Uploading…');
            if (btn) {
                btn.disabled = true;
            }
            uploadFile(file).then(function (item) {
                if (!item.is_image && item.is_image !== undefined) {
                    setStatus('Uploaded, but only images can be featured.', true);
                    return;
                }
                addToFeaturedSelect(select, item, true);
                notifyMediaAdded(item);
                setStatus('Uploaded and selected.');
            }).catch(function (err) {
                setStatus(err.message || 'Upload failed.', true);
            }).finally(function () {
                input.value = '';
                if (btn) {
                    btn.disabled = false;
                }
            });
        }

        if (btn) {
            btn.addEventListener('click', function () {
                input.click();
            });
        }
        input.addEventListener('change', function () {
            startUpload(input.files && input.files[0]);
        });
    }

    function bindCategoryQuickAdd(root) {
        root = root || document;
        var wrap = root.querySelector('[data-category-quick]');
        if (!wrap) {
            return;
        }
        var select = wrap.querySelector('select[name="category_id"]');
        var nameInput = wrap.querySelector('[data-category-name]');
        var btn = wrap.querySelector('[data-category-add-btn]');
        var status = wrap.querySelector('[data-category-status]');
        var url = wrap.getAttribute('data-quick-url') || '/admin/categories/quick-store';
        if (!select || !nameInput || !btn) {
            return;
        }

        function setStatus(msg, isError) {
            if (!status) {
                return;
            }
            status.textContent = msg || '';
            status.classList.toggle('text-danger', !!isError);
            status.classList.toggle('text-muted', !msg);
        }

        btn.addEventListener('click', function () {
            var name = (nameInput.value || '').trim();
            if (!name) {
                setStatus('Enter a category name.', true);
                return;
            }
            btn.disabled = true;
            setStatus('Saving…');
            var fd = new FormData();
            fd.append('name', name);
            fd.append('csrf_token', csrfToken());
            fetch(url, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }).then(function (res) {
                return res.json().then(function (json) {
                    if (!res.ok || !json || !json.success) {
                        throw new Error((json && json.error && json.error.message) || 'Could not create category.');
                    }
                    return json.data;
                });
            }).then(function (cat) {
                var opt = document.createElement('option');
                opt.value = String(cat.id);
                opt.textContent = cat.name;
                opt.selected = true;
                select.appendChild(opt);
                nameInput.value = '';
                setStatus('Category added.');
            }).catch(function (err) {
                setStatus(err.message || 'Failed.', true);
            }).finally(function () {
                btn.disabled = false;
            });
        });
    }

    function bindTagChips(root) {
        root = root || document;
        var wrap = root.querySelector('[data-tag-chips]');
        if (!wrap) {
            return;
        }
        var input = document.getElementById(wrap.getAttribute('data-target') || 'postTagsInput');
        if (!input) {
            return;
        }
        wrap.querySelectorAll('[data-tag-name]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var name = chip.getAttribute('data-tag-name') || '';
                if (!name) {
                    return;
                }
                var parts = (input.value || '').split(',').map(function (p) { return p.trim(); }).filter(Boolean);
                var lower = parts.map(function (p) { return p.toLowerCase(); });
                if (lower.indexOf(name.toLowerCase()) === -1) {
                    parts.push(name);
                    input.value = parts.join(', ');
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        });
    }

    function init(root) {
        bindFeaturedUpload(root);
        bindCategoryQuickAdd(root);
        bindTagChips(root);
    }

    global.CmsMediaPicker = {
        uploadFile: uploadFile,
        addToFeaturedSelect: addToFeaturedSelect,
        notifyMediaAdded: notifyMediaAdded,
        canUpload: canUpload,
        init: init,
        escHtml: escHtml
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }
})(window);
