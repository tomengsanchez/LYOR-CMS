$(function () {
    var $logModal = $('#logModal');
    if ($logModal.length) {
        $logModal.appendTo('body');
    }
    var logModal = null;
    if (window.bootstrap && window.bootstrap.Modal && $logModal.length) {
        logModal = bootstrap.Modal.getOrCreateInstance($logModal[0]);
    }
    var apiUrl = window.DEBUG_LOG_API || '/api/system/log';

    function unwrapLogPayload(resp) {
        if (resp && resp.success === true && resp.data !== null && typeof resp.data === 'object') {
            return resp.data;
        }
        return null;
    }

    $('.js-view-log').on('click', function () {
        var name = $(this).data('log-name');
        if (!name) return;
        $('#logModalLabel').text('Log: ' + name);
        $('#log-modal-loading').removeClass('d-none');
        $('#log-modal-content').addClass('d-none');
        if (logModal) {
            logModal.show();
        }
        $.getJSON(apiUrl, { name: name })
            .done(function (resp) {
                var data = unwrapLogPayload(resp);
                if (!data) {
                    var msg =
                        resp && resp.error && resp.error.message
                            ? resp.error.message
                            : 'Unable to load log file.';
                    $('#log-modal-meta').text('');
                    $('#log-modal-text').text(msg);
                    $('#log-modal-loading').addClass('d-none');
                    $('#log-modal-content').removeClass('d-none');
                    return;
                }
                var meta = [];
                if (data.size !== undefined) meta.push('Size: ' + data.size + ' bytes');
                if (data.modified) {
                    var d = new Date(data.modified * 1000);
                    meta.push('Last modified: ' + d.toISOString().replace('T', ' ').substring(0, 19));
                }
                if (data.truncated) meta.push('Showing last portion of the file (latest entries).');
                $('#log-modal-meta').text(meta.join(' • '));
                $('#log-modal-text').text(data.content || '');
                $('#log-modal-loading').addClass('d-none');
                $('#log-modal-content').removeClass('d-none');
            })
            .fail(function () {
                $('#log-modal-meta').text('');
                $('#log-modal-text').text(
                    'Unable to load log file. Please check that the log exists and try again.'
                );
                $('#log-modal-loading').addClass('d-none');
                $('#log-modal-content').removeClass('d-none');
            });
    });
});
