(function () {
    'use strict';

    var rowsEl = document.getElementById('widgetRows');
    var addBtn = document.getElementById('widgetAddRow');
    var form = document.getElementById('widgetsForm');
    var typesEl = document.getElementById('widgetTypesJson');
    if (!rowsEl || !form) {
        return;
    }

    var widgetTypes = {};
    if (typesEl && typesEl.textContent) {
        try {
            widgetTypes = JSON.parse(typesEl.textContent);
        } catch (e) {
            widgetTypes = {};
        }
    }

    var dragSrc = null;

    function buildTypeOptions(selected) {
        var html = '';
        Object.keys(widgetTypes).forEach(function (key) {
            var sel = key === selected ? ' selected' : '';
            html += '<option value="' + key + '"' + sel + '>' + widgetTypes[key] + '</option>';
        });
        return html;
    }

    function escAttr(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function configFieldsHtml(type, config) {
        config = config || {};
        if (type === 'recent_posts' || type === 'featured_posts') {
            var max = type === 'featured_posts' ? 6 : 10;
            var fallback = type === 'featured_posts' ? 3 : 5;
            return '<label class="form-label small">Posts to show</label>' +
                '<input type="number" class="form-control form-control-sm widget-field-count" min="1" max="' + max + '" value="' +
                (config.count || fallback) + '">';
        }
        if (type === 'archives') {
            return '<label class="form-label small">Months to show</label>' +
                '<input type="number" class="form-control form-control-sm widget-field-count" min="1" max="24" value="' +
                (config.count || 12) + '">';
        }
        if (type === 'custom_html') {
            return '<label class="form-label small">HTML</label>' +
                '<textarea class="form-control form-control-sm widget-field-html" rows="2">' + escAttr(config.html) + '</textarea>';
        }
        if (type === 'cta') {
            return '<label class="form-label small">Headline</label>' +
                '<input type="text" class="form-control form-control-sm widget-field-headline mb-1" value="' + escAttr(config.headline) + '">' +
                '<label class="form-label small">Text</label>' +
                '<textarea class="form-control form-control-sm widget-field-cta-text mb-1" rows="2">' + escAttr(config.text) + '</textarea>' +
                '<label class="form-label small">Button label</label>' +
                '<input type="text" class="form-control form-control-sm widget-field-label mb-1" value="' + escAttr(config.label) + '">' +
                '<label class="form-label small">Button URL</label>' +
                '<input type="text" class="form-control form-control-sm widget-field-url" placeholder="/blog or https://" value="' + escAttr(config.url) + '">';
        }
        if (type === 'social') {
            return '<label class="form-label small">Links (Label|URL per line)</label>' +
                '<textarea class="form-control form-control-sm widget-field-social" rows="4">' + escAttr(config.social_lines) + '</textarea>';
        }
        if (type === 'newsletter') {
            return '<label class="form-label small">Intro</label>' +
                '<input type="text" class="form-control form-control-sm widget-field-nl-intro mb-1" value="' + escAttr(config.intro) + '">' +
                '<label class="form-label small">Placeholder</label>' +
                '<input type="text" class="form-control form-control-sm widget-field-nl-placeholder mb-1" value="' + escAttr(config.placeholder) + '">' +
                '<label class="form-label small">Button</label>' +
                '<input type="text" class="form-control form-control-sm widget-field-nl-button mb-1" value="' + escAttr(config.button) + '">' +
                '<label class="form-label small">Consent label</label>' +
                '<input type="text" class="form-control form-control-sm widget-field-nl-consent" value="' + escAttr(config.consent_label) + '">';
        }
        return '<span class="text-muted small">No extra options for this widget type.</span>';
    }

    function syncRowConfig(row) {
        var type = row.querySelector('.widget-type-select').value;
        var hidden = row.querySelector('.widget-config-json');
        var config = {};
        if (type === 'recent_posts' || type === 'featured_posts' || type === 'archives') {
            var countEl = row.querySelector('.widget-field-count');
            var fallback = type === 'featured_posts' ? 3 : (type === 'archives' ? 12 : 5);
            config.count = countEl ? parseInt(countEl.value, 10) || fallback : fallback;
        } else if (type === 'custom_html') {
            var htmlEl = row.querySelector('.widget-field-html');
            config.html = htmlEl ? htmlEl.value : '';
        } else if (type === 'cta') {
            var h = row.querySelector('.widget-field-headline');
            var t = row.querySelector('.widget-field-cta-text');
            var l = row.querySelector('.widget-field-label');
            var u = row.querySelector('.widget-field-url');
            config.headline = h ? h.value : '';
            config.text = t ? t.value : '';
            config.label = l ? l.value : '';
            config.url = u ? u.value : '';
        } else if (type === 'social') {
            var s = row.querySelector('.widget-field-social');
            config.links = parseSocialLines(s ? s.value : '');
        } else if (type === 'newsletter') {
            var ni = row.querySelector('.widget-field-nl-intro');
            var np = row.querySelector('.widget-field-nl-placeholder');
            var nb = row.querySelector('.widget-field-nl-button');
            var nc = row.querySelector('.widget-field-nl-consent');
            config.intro = ni ? ni.value : '';
            config.placeholder = np ? np.value : '';
            config.button = nb ? nb.value : '';
            config.consent_label = nc ? nc.value : '';
        }
        hidden.value = JSON.stringify(config);
    }

    function parseSocialLines(raw) {
        var out = [];
        String(raw || '').split(/\r?\n/).forEach(function (line) {
            line = line.trim();
            if (!line) {
                return;
            }
            var label = '';
            var url = line;
            var bar = line.indexOf('|');
            if (bar !== -1) {
                label = line.slice(0, bar).trim();
                url = line.slice(bar + 1).trim();
            }
            out.push({ label: label, url: url });
        });
        return out;
    }

    function bindDragRow(row) {
        row.setAttribute('draggable', 'true');
        row.addEventListener('dragstart', function (e) {
            dragSrc = row;
            row.classList.add('widget-row--dragging');
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', '');
            }
        });
        row.addEventListener('dragend', function () {
            row.classList.remove('widget-row--dragging');
            rowsEl.querySelectorAll('.widget-row--over').forEach(function (el) {
                el.classList.remove('widget-row--over');
            });
            dragSrc = null;
        });
        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'move';
            }
            if (dragSrc && dragSrc !== row) {
                row.classList.add('widget-row--over');
            }
        });
        row.addEventListener('dragleave', function () {
            row.classList.remove('widget-row--over');
        });
        row.addEventListener('drop', function (e) {
            e.preventDefault();
            row.classList.remove('widget-row--over');
            if (!dragSrc || dragSrc === row) {
                return;
            }
            var rows = Array.prototype.slice.call(rowsEl.querySelectorAll('.widget-row'));
            var srcIdx = rows.indexOf(dragSrc);
            var dstIdx = rows.indexOf(row);
            if (srcIdx < 0 || dstIdx < 0) {
                return;
            }
            if (srcIdx < dstIdx) {
                rowsEl.insertBefore(dragSrc, row.nextElementSibling);
            } else {
                rowsEl.insertBefore(dragSrc, row);
            }
            reindexRows();
        });
        var handle = row.querySelector('.widget-drag-handle');
        if (handle) {
            handle.addEventListener('mousedown', function () {
                row.setAttribute('draggable', 'true');
            });
        }
    }

    function bindRow(row, index) {
        var typeSelect = row.querySelector('.widget-type-select');
        var configWrap = row.querySelector('.widget-config-fields');
        var removeBtn = row.querySelector('.widget-remove');
        var enabled = row.querySelector('.form-check-input');

        if (enabled) {
            enabled.name = 'widget_enabled[' + index + ']';
            enabled.id = 'we' + index;
            var label = row.querySelector('label[for^="we"]');
            if (label) {
                label.setAttribute('for', enabled.id);
            }
        }

        function onTypeChange() {
            var cfg = {};
            try {
                cfg = JSON.parse(row.querySelector('.widget-config-json').value || '{}');
            } catch (err) {
                cfg = {};
            }
            configWrap.innerHTML = configFieldsHtml(typeSelect.value, cfg);
            bindConfigInputs(row);
            syncRowConfig(row);
        }

        typeSelect.addEventListener('change', onTypeChange);
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                row.remove();
                reindexRows();
            });
        }
        bindConfigInputs(row);
        bindDragRow(row);
    }

    function bindConfigInputs(row) {
        row.querySelectorAll('.widget-field-count, .widget-field-html, .widget-field-headline, .widget-field-cta-text, .widget-field-label, .widget-field-url, .widget-field-social, .widget-field-nl-intro, .widget-field-nl-placeholder, .widget-field-nl-button, .widget-field-nl-consent').forEach(function (el) {
            el.addEventListener('input', function () {
                syncRowConfig(row);
            });
        });
    }

    function reindexRows() {
        var rows = rowsEl.querySelectorAll('.widget-row');
        rows.forEach(function (row, i) {
            row.setAttribute('data-index', String(i));
            var enabled = row.querySelector('.form-check-input');
            if (enabled) {
                enabled.name = 'widget_enabled[' + i + ']';
                enabled.id = 'we' + i;
                var label = row.querySelector('label[for^="we"]');
                if (label) {
                    label.setAttribute('for', enabled.id);
                }
            }
        });
    }

    function rowInnerHtml(index, type) {
        type = type || 'recent_posts';
        return '<div class="row g-2 align-items-end">' +
            '<div class="col-auto pt-4"><span class="widget-drag-handle" title="Drag to reorder" aria-hidden="true">⋮⋮</span></div>' +
            '<div class="col-md-3"><label class="form-label small">Type</label>' +
            '<select name="widget_type[]" class="form-select form-select-sm widget-type-select">' +
            buildTypeOptions(type) + '</select></div>' +
            '<div class="col-md-3"><label class="form-label small">Title (optional)</label>' +
            '<input type="text" name="widget_title[]" class="form-control form-control-sm"></div>' +
            '<div class="col-md-3 widget-config-fields">' + configFieldsHtml(type, { count: 5 }) + '</div>' +
            '<div class="col-md-1"><div class="form-check">' +
            '<input type="checkbox" class="form-check-input" name="widget_enabled[' + index + ']" value="1" checked id="we' + index + '">' +
            '<label class="form-check-label small" for="we' + index + '">On</label></div></div>' +
            '<div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger widget-remove" aria-label="Remove">&times;</button></div>' +
            '</div><input type="hidden" name="widget_config[]" class="widget-config-json" value=\'{"count":5}\'>';
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var index = rowsEl.querySelectorAll('.widget-row').length;
            var div = document.createElement('div');
            div.className = 'widget-row border rounded p-3 mb-3';
            div.setAttribute('data-index', String(index));
            div.innerHTML = rowInnerHtml(index, 'recent_posts');
            rowsEl.appendChild(div);
            bindRow(div, index);
        });
    }

    rowsEl.querySelectorAll('.widget-row').forEach(function (row, i) {
        if (!row.querySelector('.widget-drag-handle')) {
            var firstRow = row.querySelector('.row');
            if (firstRow) {
                var handleCol = document.createElement('div');
                handleCol.className = 'col-auto pt-4';
                handleCol.innerHTML = '<span class="widget-drag-handle" title="Drag to reorder" aria-hidden="true">⋮⋮</span>';
                firstRow.insertBefore(handleCol, firstRow.firstChild);
            }
        }
        bindRow(row, i);
    });

    form.addEventListener('submit', function () {
        rowsEl.querySelectorAll('.widget-row').forEach(function (row) {
            syncRowConfig(row);
        });
    });
})();
