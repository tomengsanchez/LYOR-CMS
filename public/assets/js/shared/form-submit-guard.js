(function () {
    'use strict';

    function disableSubmit(form) {
        var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        buttons.forEach(function (btn) {
            if (btn.disabled && btn.dataset.guardLocked === '1') {
                return;
            }
            if (!btn.dataset.guardOriginalText) {
                btn.dataset.guardOriginalText = btn.tagName === 'INPUT' ? btn.value : btn.textContent;
            }
            btn.dataset.guardLocked = '1';
            btn.disabled = true;
            if (btn.tagName === 'INPUT') {
                btn.value = 'Saving…';
            } else {
                btn.textContent = 'Saving…';
            }
        });
    }

    function restoreSubmit(form) {
        var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        buttons.forEach(function (btn) {
            if (btn.dataset.guardLocked !== '1') {
                return;
            }
            btn.disabled = false;
            delete btn.dataset.guardLocked;
            if (btn.dataset.guardOriginalText) {
                if (btn.tagName === 'INPUT') {
                    btn.value = btn.dataset.guardOriginalText;
                } else {
                    btn.textContent = btn.dataset.guardOriginalText;
                }
            }
        });
    }

    function bindGuardForms() {
        document.querySelectorAll('form[data-guard-submit]').forEach(function (form) {
            if (form.dataset.guardSubmitBound === '1') {
                return;
            }
            form.dataset.guardSubmitBound = '1';
            form.addEventListener('submit', function (e) {
                disableSubmit(form);
                // Other handlers (jQuery validation, etc.) may still preventDefault in this tick.
                setTimeout(function () {
                    if (e.defaultPrevented) {
                        restoreSubmit(form);
                    }
                }, 0);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindGuardForms);
    } else {
        bindGuardForms();
    }
})();
