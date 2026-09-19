(function () {
    var form = document.getElementById('postListFilters');
    if (!form) {
        return;
    }
    form.querySelectorAll('[data-filter-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }
            form.submit();
        });
    });
})();
