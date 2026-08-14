(function () {
    'use strict';

    var preview = document.getElementById('pubThemePreview');
    if (!preview) {
        return;
    }

    var form = preview.closest('form');
    var presetInputs = document.querySelectorAll('input[name="pub_theme_preset"]');
    var accentInput = document.querySelector('input[name="public_accent_color"]');
    var fontSelect = document.querySelector('select[name="pub_theme_font"]');
    var radiusSelect = document.querySelector('select[name="pub_theme_radius"]');
    var headerSelect = document.querySelector('select[name="pub_theme_header"]');
    var customWrap = document.getElementById('pubThemeCustomColors');
    var customBg = document.querySelector('input[name="pub_theme_custom_bg"]');
    var customSurface = document.querySelector('input[name="pub_theme_custom_surface"]');
    var customText = document.querySelector('input[name="pub_theme_custom_text"]');
    var previewSiteBtn = document.getElementById('pubThemePreviewSite');

    function selectedPreset() {
        var checked = document.querySelector('input[name="pub_theme_preset"]:checked');
        return checked ? checked.value : 'default';
    }

    function syncPreview() {
        var preset = selectedPreset();
        preview.className = 'pub-theme-preview-inner pub-theme-' + preset +
            ' pub-font-' + (fontSelect ? fontSelect.value : 'system') +
            ' pub-radius-' + (radiusSelect ? radiusSelect.value : 'md') +
            ' pub-header-' + (headerSelect ? headerSelect.value : 'solid');

        if (accentInput && accentInput.value) {
            preview.style.setProperty('--pub-accent', accentInput.value);
        } else {
            preview.style.removeProperty('--pub-accent');
        }

        if (preset === 'custom') {
            if (customWrap) {
                customWrap.classList.remove('d-none');
            }
            if (customBg) {
                preview.style.setProperty('--pub-bg', customBg.value);
            }
            if (customSurface) {
                preview.style.setProperty('--pub-surface', customSurface.value);
            }
            if (customText) {
                preview.style.setProperty('--pub-text', customText.value);
            }
        } else {
            if (customWrap) {
                customWrap.classList.add('d-none');
            }
            preview.style.removeProperty('--pub-bg');
            preview.style.removeProperty('--pub-surface');
            preview.style.removeProperty('--pub-text');
        }
    }

    presetInputs.forEach(function (input) {
        input.addEventListener('change', syncPreview);
    });
    [accentInput, fontSelect, radiusSelect, headerSelect, customBg, customSurface, customText].forEach(function (el) {
        if (el) {
            el.addEventListener('input', syncPreview);
            el.addEventListener('change', syncPreview);
        }
    });

    if (previewSiteBtn && form) {
        previewSiteBtn.addEventListener('click', function () {
            var fd = new FormData(form);
            var action = form.getAttribute('action') || '';
            var previewUrl = action.replace(/\/save\/?$/, '/theme-preview');
            previewSiteBtn.disabled = true;
            fetch(previewUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && data.success && data.url) {
                        window.open(data.url, '_blank', 'noopener');
                    } else {
                        window.alert((data && data.error) ? data.error : 'Could not start theme preview.');
                    }
                })
                .catch(function () {
                    window.alert('Could not start theme preview.');
                })
                .finally(function () {
                    previewSiteBtn.disabled = false;
                });
        });
    }

    syncPreview();
})();
