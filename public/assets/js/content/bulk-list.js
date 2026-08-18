(function () {
    'use strict';

    function selectedCount(scope) {
        return scope.querySelectorAll('.js-bulk-row:checked').length;
    }

    function sync(scope, form) {
        var count = selectedCount(scope);
        var label = form.querySelector('[data-bulk-count]');
        if (label) {
            label.textContent = count === 0 ? 'None selected' : (count === 1 ? '1 selected' : count + ' selected');
        }
        var apply = form.querySelector('[data-bulk-apply]');
        if (apply) {
            apply.disabled = count === 0;
        }
        var master = scope.querySelector('.js-bulk-all');
        var rows = scope.querySelectorAll('.js-bulk-row');
        if (master && rows.length) {
            master.checked = count === rows.length;
            master.indeterminate = count > 0 && count < rows.length;
        }
    }

    document.querySelectorAll('[data-bulk-scope]').forEach(function (scope) {
        var form = scope.querySelector('[data-bulk-form]');
        if (!form) {
            return;
        }
        scope.addEventListener('change', function (e) {
            var t = e.target;
            if (t && t.classList.contains('js-bulk-all')) {
                scope.querySelectorAll('.js-bulk-row').forEach(function (cb) {
                    cb.checked = t.checked;
                });
            }
            sync(scope, form);
        });
        form.addEventListener('submit', function (e) {
            if (selectedCount(scope) < 1) {
                e.preventDefault();
                return;
            }
            var action = form.querySelector('[name="bulk_action"]');
            var val = action ? String(action.value || '') : '';
            if (val === 'delete') {
                if (!window.confirm('Delete the selected items? This can be restored only from backup.')) {
                    e.preventDefault();
                }
            }
        });
        sync(scope, form);
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var msg = form.getAttribute('data-confirm') || 'Continue?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });
})();
