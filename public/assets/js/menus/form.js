(function () {
    'use strict';

    var table = document.querySelector('#menuItemsTable tbody');
    var template = document.getElementById('menuRowTemplate');
    var addBtn = document.getElementById('menuAddRow');
    if (!table || !template || !addBtn) {
        return;
    }

    function parseJson(id) {
        var el = document.getElementById(id);
        if (!el) {
            return [];
        }
        try {
            return JSON.parse(el.textContent || '[]');
        } catch (err) {
            return [];
        }
    }

    var pages = parseJson('menuPagesJson');
    var posts = parseJson('menuPostsJson');
    var categories = parseJson('menuCategoriesJson');

    function optionSelect(items, valueKey, labelKey, placeholder) {
        var html = '<select name="item_object_id[]" class="form-select form-select-sm">';
        html += '<option value="">' + placeholder + '</option>';
        items.forEach(function (item) {
            html += '<option value="' + item[valueKey] + '">' + escapeHtml(item[labelKey]) + '</option>';
        });
        html += '</select>';
        html += '<input type="hidden" name="item_custom_url[]" value="">';
        return html;
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function targetCellForType(type) {
        if (type === 'page') {
            return optionSelect(pages, 'id', 'title', '— Page —');
        }
        if (type === 'post') {
            return optionSelect(posts, 'id', 'title', '— Post —');
        }
        if (type === 'category') {
            return optionSelect(categories, 'id', 'name', '— Category —');
        }
        if (type === 'home' || type === 'blog') {
            return '<span class="text-muted small">Auto URL</span><input type="hidden" name="item_object_id[]" value=""><input type="hidden" name="item_custom_url[]" value="">';
        }
        return '<input type="text" name="item_custom_url[]" class="form-control form-control-sm" placeholder="https://… or /path"><input type="hidden" name="item_object_id[]" value="">';
    }

    function bindRow(row) {
        var typeSelect = row.querySelector('.menu-item-type');
        var removeBtn = row.querySelector('.menu-remove-row');
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                var cell = row.querySelector('.menu-item-target');
                if (cell) {
                    cell.innerHTML = targetCellForType(typeSelect.value);
                }
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (table.querySelectorAll('.menu-item-row').length > 1) {
                    row.remove();
                }
            });
        }
    }

    table.querySelectorAll('.menu-item-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
        var clone = template.content.cloneNode(true);
        var row = clone.querySelector('tr');
        table.appendChild(clone);
        if (row) {
            bindRow(row);
        } else {
            var added = table.lastElementChild;
            if (added) {
                bindRow(added);
            }
        }
    });
})();
