$(function () {
    var cfg = window.blockedIpsConfig || {};
    var blocksApiUrl = cfg.blocksApiUrl || '/api/system/live-traffic/blocks';
    var blockApiUrl = cfg.blockApiUrl || '/api/system/live-traffic/block';
    var unblockApiUrl = cfg.unblockApiUrl || '/api/system/live-traffic/unblock';
    var canManage = !!cfg.canManage;
    var csrfToken = cfg.csrfToken || ($('meta[name="csrf-token"]').attr('content') || '');

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setCsrf(token) {
        if (!token) {
            return;
        }
        csrfToken = token;
        $('meta[name="csrf-token"]').attr('content', token);
        if (window.blockedIpsConfig) {
            window.blockedIpsConfig.csrfToken = token;
        }
    }

    function envelopeData(payload) {
        if (payload && typeof payload === 'object' && Object.prototype.hasOwnProperty.call(payload, 'success')) {
            return payload.data;
        }
        return payload;
    }

    function renderBlocks(blocks) {
        var $body = $('#blockedIpsBody');
        var $empty = $('#blockedIpsEmpty');
        var $wrap = $('#blockedIpsTableWrap');
        $('#blockedIpsTotal').text(String(Array.isArray(blocks) ? blocks.length : 0));
        if (!Array.isArray(blocks) || blocks.length === 0) {
            $body.empty();
            $wrap.addClass('d-none');
            $empty.removeClass('d-none');
            return;
        }
        var html = '';
        for (var i = 0; i < blocks.length; i += 1) {
            var b = blocks[i] || {};
            var ip = b.ip_address || '';
            var added = String(b.created_at || '');
            if (b.created_by_username) {
                added += (added ? ' · ' : '') + String(b.created_by_username);
            }
            html += '<tr data-ip="' + escapeHtml(ip) + '">'
                + '<td><code>' + escapeHtml(ip) + '</code></td>'
                + '<td><span class="badge text-bg-light border">' + escapeHtml(b.pattern_label || 'Exact IP') + '</span></td>'
                + '<td class="small">' + escapeHtml(b.reason || '') + '</td>'
                + '<td class="small text-muted">' + escapeHtml(added) + '</td>';
            if (canManage) {
                html += '<td class="text-end"><button type="button" class="btn btn-outline-success btn-sm js-bi-unblock" data-ip="'
                    + escapeHtml(ip) + '">Unblock</button></td>';
            }
            html += '</tr>';
        }
        $body.html(html);
        $wrap.removeClass('d-none');
        $empty.addClass('d-none');
    }

    function fetchBlocks() {
        $.ajax({
            url: blocksApiUrl,
            method: 'GET',
            dataType: 'json'
        }).done(function (payload) {
            var data = envelopeData(payload) || {};
            if (data.csrf_token) {
                setCsrf(data.csrf_token);
            }
            if (typeof data.can_manage !== 'undefined') {
                canManage = !!data.can_manage;
            }
            renderBlocks(data.blocks || []);
        });
    }

    function postAction(url, body) {
        body = body || {};
        body.csrf_token = csrfToken;
        return $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(body)
        });
    }

    function failMessage(xhr) {
        try {
            var body = xhr.responseJSON || {};
            if (body.error && body.error.message) {
                return body.error.message;
            }
        } catch (e) { /* ignore */ }
        return 'Request failed.';
    }

    $('#blockedIpsRefresh').on('click', function () {
        fetchBlocks();
    });

    $('#blockedIpsAdd').on('click', function () {
        if (!canManage) {
            return;
        }
        var ip = String($('#blockedIpsInput').val() || '').trim();
        var reason = String($('#blockedIpsReason').val() || '').trim() || 'Blocked from Live Traffic';
        if (!ip) {
            window.alert('Enter an IP, wildcard (124.123.4.*), or CIDR (124.123.4.0/24).');
            return;
        }
        if (!window.confirm('Block ' + ip + ' site-wide?')) {
            return;
        }
        postAction(blockApiUrl, { ip: ip, reason: reason }).done(function (payload) {
            var data = envelopeData(payload) || {};
            if (data.csrf_token) {
                setCsrf(data.csrf_token);
            }
            $('#blockedIpsInput').val('');
            if (Array.isArray(data.blocks)) {
                renderBlocks(data.blocks);
            } else {
                fetchBlocks();
            }
        }).fail(function (xhr) {
            window.alert(failMessage(xhr));
        });
    });

    $(document).on('click', '.js-bi-unblock', function () {
        var ip = String($(this).data('ip') || '');
        if (!ip || !canManage) {
            return;
        }
        if (!window.confirm('Unblock ' + ip + '?')) {
            return;
        }
        postAction(unblockApiUrl, { ip: ip }).done(function (payload) {
            var data = envelopeData(payload) || {};
            if (data.csrf_token) {
                setCsrf(data.csrf_token);
            }
            if (Array.isArray(data.blocks)) {
                renderBlocks(data.blocks);
            } else {
                fetchBlocks();
            }
        });
    });
});
