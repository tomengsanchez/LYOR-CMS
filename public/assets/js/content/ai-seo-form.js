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
            if (typeof max === 'number') {
                countEl.classList.toggle('text-danger', len > max);
            }
        }
        input.addEventListener('input', sync);
        sync();
    }

    bindCounter('citationSnippet', 'citationSnippetCount', 500);

    var list = document.getElementById('faqEditor');
    var hidden = document.getElementById('faqJsonInput');
    var addBtn = document.getElementById('faqAddRow');
    if (!list || !hidden || !addBtn) {
        return;
    }

    function parseInitial() {
        try {
            var data = JSON.parse(hidden.value || '[]');
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    }

    function syncHidden() {
        var rows = list.querySelectorAll('.faq-row');
        var items = [];
        rows.forEach(function (row) {
            var q = (row.querySelector('.faq-q') || {}).value || '';
            var a = (row.querySelector('.faq-a') || {}).value || '';
            q = String(q).trim();
            a = String(a).trim();
            if (q && a) {
                items.push({ question: q, answer: a });
            }
        });
        hidden.value = items.length ? JSON.stringify(items) : '';
    }

    function addRow(question, answer) {
        if (list.querySelectorAll('.faq-row').length >= 20) {
            return;
        }
        var wrap = document.createElement('div');
        wrap.className = 'faq-row border rounded p-2 mb-2';
        wrap.innerHTML =
            '<div class="mb-2">' +
            '<label class="form-label small mb-1">Question</label>' +
            '<input type="text" class="form-control form-control-sm faq-q" maxlength="300">' +
            '</div>' +
            '<div class="mb-2">' +
            '<label class="form-label small mb-1">Answer</label>' +
            '<textarea class="form-control form-control-sm faq-a" rows="2" maxlength="2000"></textarea>' +
            '</div>' +
            '<button type="button" class="btn btn-link btn-sm text-danger p-0 faq-remove">Remove</button>';
        wrap.querySelector('.faq-q').value = question || '';
        wrap.querySelector('.faq-a').value = answer || '';
        wrap.querySelector('.faq-q').addEventListener('input', syncHidden);
        wrap.querySelector('.faq-a').addEventListener('input', syncHidden);
        wrap.querySelector('.faq-remove').addEventListener('click', function () {
            wrap.remove();
            syncHidden();
        });
        list.appendChild(wrap);
        syncHidden();
    }

    parseInitial().forEach(function (item) {
        addRow(item.question || item.q || '', item.answer || item.a || '');
    });
    if (!list.querySelector('.faq-row')) {
        addRow('', '');
    }

    addBtn.addEventListener('click', function () {
        addRow('', '');
    });

    var form = list.closest('form');
    if (form) {
        form.addEventListener('submit', syncHidden);
    }
})();
