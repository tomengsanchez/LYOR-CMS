(function () {
    var cfg = window.presenceHeartbeatConfig || {};
    var url = cfg.url || '/api/presence/heartbeat';
    var pageKey = cfg.pageKey || '';
    var path = cfg.path || (window.location.pathname || '');
    var intervalMs = Number(cfg.intervalMs || 45000);
    if (!intervalMs || intervalMs < 15000) {
        intervalMs = 45000;
    }

    function csrfToken() {
        if (cfg.csrfToken) {
            return cfg.csrfToken;
        }
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function sendHeartbeat() {
        try {
            var body = new URLSearchParams();
            body.set('page_key', pageKey);
            body.set('path', path);
            body.set('csrf_token', csrfToken());

            if (window.fetch) {
                window.fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString(),
                    credentials: 'same-origin',
                    cache: 'no-store'
                }).catch(function () { /* best-effort */ });
                return;
            }

            if (window.jQuery) {
                window.jQuery.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        page_key: pageKey,
                        path: path,
                        csrf_token: csrfToken()
                    }
                });
            }
        } catch (e) {
            // Presence is best-effort.
        }
    }

    function onVisibility() {
        if (document.visibilityState === 'visible') {
            sendHeartbeat();
        }
    }

    sendHeartbeat();
    window.setInterval(sendHeartbeat, intervalMs);
    document.addEventListener('visibilitychange', onVisibility);
})();
