/**
 * Admin Customizer shell — sync session preview + postMessage to iframe.
 */
(function () {
    'use strict';

    var cfgEl = document.getElementById('cmsCustomizerConfig');
    var form = document.getElementById('cmsCustomizerForm');
    var frame = document.getElementById('cmsCustomizerFrame');
    var statusEl = document.getElementById('cmsCustomizerStatus');
    if (!cfgEl || !form || !frame) {
        return;
    }

    var syncUrl = cfgEl.getAttribute('data-sync-url') || '';
    var publishUrl = cfgEl.getAttribute('data-publish-url') || '';
    var csrf = cfgEl.getAttribute('data-csrf') || '';
    var customColors = document.getElementById('cmsCustomizerCustomColors');
    var syncTimer = null;
    var lastBridge = null;

    try {
        lastBridge = JSON.parse(cfgEl.getAttribute('data-bridge') || 'null');
    } catch (e) {
        lastBridge = null;
    }

    function setStatus(msg, isError) {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = msg || '';
        statusEl.style.color = isError ? '#f5a9a9' : '#9ad29a';
    }

    function formPayload() {
        var fd = new FormData(form);
        fd.set('csrf_token', csrf);
        return fd;
    }

    function selectedPreset() {
        var checked = form.querySelector('input[name="pub_theme_preset"]:checked');
        return checked ? checked.value : 'default';
    }

    function toggleCustomColors() {
        if (!customColors) {
            return;
        }
        if (selectedPreset() === 'custom') {
            customColors.classList.remove('d-none');
        } else {
            customColors.classList.add('d-none');
        }
    }

    function postToFrame(config) {
        if (!frame.contentWindow || !config) {
            return;
        }
        try {
            frame.contentWindow.postMessage({
                type: 'cms-theme-preview',
                config: config
            }, window.location.origin);
        } catch (err) {
            /* ignore */
        }
    }

    function frameUrlForPath(path) {
        path = path || '/';
        var q = 'theme_preview=1&customize_frame=1';
        if (path.indexOf('?') >= 0) {
            return path + '&' + q;
        }
        return path + '?' + q;
    }

    function syncPreview(options) {
        options = options || {};
        setStatus(options.quiet ? '' : 'Updating…');
        return fetch(syncUrl, {
            method: 'POST',
            body: formPayload(),
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json || !json.success) {
                    throw new Error((json && json.error && json.error.message) || 'Preview sync failed');
                }
                lastBridge = json.data && json.data.config ? json.data.config : lastBridge;
                postToFrame(lastBridge);
                setStatus('Preview');
                return json;
            });
        }).catch(function (err) {
            setStatus(err.message || 'Preview sync failed', true);
        });
    }

    function scheduleSync() {
        toggleCustomColors();
        if (syncTimer) {
            clearTimeout(syncTimer);
        }
        syncTimer = setTimeout(function () {
            syncPreview();
        }, 180);
    }

    form.addEventListener('input', scheduleSync);
    form.addEventListener('change', scheduleSync);

    document.querySelectorAll('[data-preview-path]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-preview-path]').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
            });
            var path = btn.getAttribute('data-preview-path') || '/';
            syncPreview({ quiet: true }).then(function () {
                frame.src = frameUrlForPath(path);
            });
        });
    });

    frame.addEventListener('load', function () {
        if (lastBridge) {
            postToFrame(lastBridge);
        }
    });

    document.getElementById('cmsCustomizerPublish') && document.getElementById('cmsCustomizerPublish').addEventListener('click', function () {
        setStatus('Publishing…');
        fetch(publishUrl, {
            method: 'POST',
            body: formPayload(),
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json || !json.success) {
                    throw new Error((json && json.error && json.error.message) || 'Publish failed');
                }
                lastBridge = json.data && json.data.config ? json.data.config : lastBridge;
                postToFrame(lastBridge);
                setStatus('Published');
            });
        }).catch(function (err) {
            setStatus(err.message || 'Publish failed', true);
        });
    });

    toggleCustomColors();
    setStatus('Ready');
})();
