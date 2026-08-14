(function () {
    'use strict';

    document.querySelectorAll('form.js-media-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm('Delete this file?')) {
                e.preventDefault();
            }
        });
    });
})();
