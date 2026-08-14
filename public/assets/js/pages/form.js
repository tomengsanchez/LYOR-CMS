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
    if (select && preview) {
        function renderPreview() {
            var option = select.options[select.selectedIndex];
            var url = option ? option.getAttribute('data-preview') : '';
            if (!url) {
                preview.innerHTML = '';
                preview.classList.add('d-none');
                return;
            }
            preview.innerHTML = '<img src="' + url + '" alt="" class="public-featured-img">';
            preview.classList.remove('d-none');
        }
        select.addEventListener('change', renderPreview);
        renderPreview();
    }
})();
