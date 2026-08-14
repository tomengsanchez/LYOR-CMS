(function () {
    'use strict';

    function bindCounter(inputId, countId, max) {
        var input = document.getElementById(inputId);
        var countEl = document.getElementById(countId);
        if (!input || !countEl) {
            return;
        }
        function sync() {
            var len = input.value.length;
            countEl.textContent = String(len);
            countEl.classList.toggle('text-danger', len > max);
        }
        input.addEventListener('input', sync);
        sync();
    }

    bindCounter('metaTitle', 'metaTitleCount', 60);
    bindCounter('metaDescription', 'metaDescriptionCount', 160);
    bindCounter('llmSummary', 'llmSummaryCount', 2000);

    var select = document.getElementById('featuredImageSelect');
    var preview = document.getElementById('featuredImagePreview');
    if (!select || !preview) {
        return;
    }

    function renderPreview() {
        var option = select.options[select.selectedIndex];
        var url = option ? option.getAttribute('data-preview') : '';
        var width = option ? parseInt(option.getAttribute('data-width') || '0', 10) : 0;
        var height = option ? parseInt(option.getAttribute('data-height') || '0', 10) : 0;

        if (!url) {
            preview.innerHTML = '';
            preview.classList.add('d-none');
            return;
        }

        var sizeHint = '';
        if (width > 0 && height > 0) {
            sizeHint = '<p class="text-muted small mb-0 mt-1">' + width + '×' + height + ' px';
            if (width < 200 || height < 200) {
                sizeHint += ' — below 200×200 minimum for some social platforms';
            } else if (width < 1200 || height < 630) {
                sizeHint += ' — OK; 1200×630 recommended for Facebook link previews';
            } else {
                sizeHint += ' — good size for social sharing';
            }
            sizeHint += '</p>';
        }

        preview.innerHTML =
            '<img src="' + url + '" alt="" class="public-featured-img">' + sizeHint;
        preview.classList.remove('d-none');
    }

    select.addEventListener('change', renderPreview);
    renderPreview();

    // When featured upload selects a new image, preview refreshes via change event.
})();
