(function () {
    'use strict';

    function bindCounter(inputId, countId) {
        var input = document.getElementById(inputId);
        var countEl = document.getElementById(countId);
        if (!input || !countEl) {
            return;
        }
        function sync() {
            countEl.textContent = String(input.value.length);
        }
        input.addEventListener('input', sync);
        sync();
    }

    bindCounter('seoDefaultDescription', 'seoDefaultDescriptionCount');
    bindCounter('llmSiteSummary', 'llmSiteSummaryCount');
})();
