$(function () {
    var cfg = window.realtimeSecurityConfig || {};
    var apiUrl = cfg.summaryApiUrl || '/api/system/realtime-security';
    var pollIntervalMs = Number(cfg.pollIntervalMs || 10000);
    if (!pollIntervalMs || pollIntervalMs < 3000) {
        pollIntervalMs = 10000;
    }

    var $eventsBody = $('#realtimeSecurityAuthEventsBody');
    var $eventsEmpty = $('#realtimeSecurityAuthEventsEmpty');
    var $eventsTableWrap = $('#realtimeSecurityAuthEventsTableWrap');

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setMetric(key, value) {
        var selectorMap = {
            riskLevel: '[data-metric="risk-level"]',
            loginAttempts: '[data-metric="login-attempts"]',
            failedLogins: '[data-metric="failed-logins"]',
            blockedAttempts: '[data-metric="blocked-attempts"]'
        };
        var selector = selectorMap[key];
        if (!selector) {
            return;
        }
        $(selector).text(value);
    }

    function renderEvents(events) {
        if (!Array.isArray(events) || events.length === 0) {
            $eventsBody.empty();
            $eventsTableWrap.addClass('d-none');
            $eventsEmpty.removeClass('d-none');
            return;
        }

        var html = '';
        for (var i = 0; i < events.length; i += 1) {
            var item = events[i] || {};
            html += '<tr>'
                + '<td><code>' + escapeHtml(item.time || '') + '</code></td>'
                + '<td>' + escapeHtml(item.event || '') + '</td>'
                + '</tr>';
        }
        $eventsBody.html(html);
        $eventsTableWrap.removeClass('d-none');
        $eventsEmpty.addClass('d-none');
    }

    function refreshLatestAuthEvents() {
        $.ajax({
            url: apiUrl,
            method: 'GET',
            cache: false
        }).done(function (resp) {
            if (!resp || resp.success !== true || !resp.data) {
                return;
            }
            var metrics = resp.data.metrics || {};
            setMetric('riskLevel', metrics.risk_level || 'Low');
            setMetric('loginAttempts', Number(metrics.login_attempts || 0));
            setMetric('failedLogins', Number(metrics.failed_logins || 0));
            setMetric('blockedAttempts', Number(metrics.blocked_attempts || 0));
            renderEvents(metrics.latest_events || []);
        });
    }

    refreshLatestAuthEvents();
    window.setInterval(refreshLatestAuthEvents, pollIntervalMs);
});
