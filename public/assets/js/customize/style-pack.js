/**
 * Customize: import / clear theme style packs (multipart).
 */
(function () {
  'use strict';

  var section = document.getElementById('cmsStylePackSection');
  if (!section) return;

  var cfg = document.getElementById('cmsCustomizerConfig');
  var csrf = cfg ? cfg.getAttribute('data-csrf') || '' : '';
  var importUrl = section.getAttribute('data-import-url') || '';
  var clearUrl = section.getAttribute('data-clear-url') || '';
  var fileInput = document.getElementById('cmsStylePackFile');
  var importBtn = document.getElementById('cmsStylePackImport');
  var clearBtn = document.getElementById('cmsStylePackClear');
  var msg = document.getElementById('cmsStylePackMsg');
  var statusEl = document.getElementById('cmsStylePackStatus');
  var statusTarget = document.getElementById('cmsCustomizerStatus');

  function setMsg(text, isError) {
    if (!msg) return;
    msg.textContent = text || '';
    msg.classList.toggle('text-danger', !!isError);
    msg.classList.toggle('text-success', !isError && !!text);
  }

  function applyBridge(config) {
    if (!config || !window.CmsCustomizerApplyBridge) return;
    try {
      window.CmsCustomizerApplyBridge(config);
    } catch (e) {
      /* optional hook */
    }
    // Reload iframe so extra.css and published settings show
    var frame = document.getElementById('cmsCustomizerFrame');
    if (frame && frame.src) {
      var url = frame.src.split('#')[0];
      frame.src = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_pack=' + Date.now();
    }
  }

  if (importBtn && fileInput && importUrl) {
    importBtn.addEventListener('click', function () {
      if (!fileInput.files || !fileInput.files[0]) {
        setMsg('Choose a .zip file first.', true);
        return;
      }
      var fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('ajax', '1');
      fd.append('style_pack', fileInput.files[0]);
      importBtn.disabled = true;
      setMsg('Importing…');
      if (statusTarget) statusTarget.textContent = 'Importing style pack…';

      fetch(importUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
        .then(function (r) {
          return r.json().then(function (j) {
            return { ok: r.ok, json: j };
          });
        })
        .then(function (res) {
          importBtn.disabled = false;
          if (!res.ok || !res.json.success) {
            var err =
              (res.json && res.json.error && res.json.error.message) ||
              'Import failed.';
            setMsg(err, true);
            if (statusTarget) statusTarget.textContent = err;
            return;
          }
          var data = res.json.data || {};
          setMsg(data.message || 'Imported.', false);
          if (statusTarget) statusTarget.textContent = data.message || 'Style pack imported.';
          if (statusEl && data.name) {
            statusEl.innerHTML =
              'Active: <strong></strong>' +
              (data.source
                ? ' <span class="text-muted">(' +
                  String(data.source).replace(/</g, '') +
                  ')</span>'
                : '');
            var strong = statusEl.querySelector('strong');
            if (strong) strong.textContent = data.name;
          }
          if (data.config) applyBridge(data.config);
          else {
            var frame = document.getElementById('cmsCustomizerFrame');
            if (frame) frame.contentWindow.location.reload();
          }
          // Sync customizer form fields from server by soft reload of sidebar values
          window.location.reload();
        })
        .catch(function () {
          importBtn.disabled = false;
          setMsg('Network error during import.', true);
        });
    });
  }

  if (clearBtn && clearUrl) {
    clearBtn.addEventListener('click', function () {
      if (!window.confirm('Clear style pack CSS and metadata? Theme colors/settings stay as-is.')) {
        return;
      }
      var fd = new FormData();
      fd.append('csrf_token', csrf);
      clearBtn.disabled = true;
      fetch(clearUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      })
        .then(function () {
          window.location.reload();
        })
        .catch(function () {
          clearBtn.disabled = false;
          setMsg('Clear failed.', true);
        });
    });
  }
})();
