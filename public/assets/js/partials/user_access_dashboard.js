$(function () {
    var cfg = window.userAccessDashboardConfig || {};
    var activityApiUrl = cfg.activityApiUrl || '';
    var activityPerPage = Number(cfg.perPage || 15);
    if (!activityPerPage || activityPerPage < 5) {
        activityPerPage = 15;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initTablePager($section) {
        var $body = $section.find('.js-access-page-body').first();
        var $pager = $section.find('.js-access-pager').first();
        if (!$body.length || !$pager.length) {
            return;
        }
        var $rows = $body.find('.js-access-page-row');
        var pageSize = Number($body.data('page-size') || cfg.tablePageSize || 10);
        if (!pageSize || pageSize < 1) {
            pageSize = 10;
        }
        var total = $rows.length;
        var totalPages = total > 0 ? Math.ceil(total / pageSize) : 1;
        var page = 1;

        function render() {
            var start = (page - 1) * pageSize;
            var end = start + pageSize;
            $rows.each(function (idx) {
                $(this).toggleClass('d-none', idx < start || idx >= end);
            });
            $pager.find('.js-access-page-info').text(
                total === 0
                    ? 'No rows'
                    : ('Page ' + page + ' of ' + totalPages + ' · ' + total + ' total')
            );
            $pager.find('.js-access-prev').prop('disabled', page <= 1);
            $pager.find('.js-access-next').prop('disabled', page >= totalPages);
        }

        $pager.find('.js-access-prev').on('click', function () {
            if (page > 1) {
                page -= 1;
                render();
            }
        });
        $pager.find('.js-access-next').on('click', function () {
            if (page < totalPages) {
                page += 1;
                render();
            }
        });
        render();
    }

    initTablePager($('#userAccessWebSessions'));
    initTablePager($('#userAccessApiTokens'));

    var $modal = $('#userAccessActivityModal');
    var modal = null;
    if ($modal.length && window.bootstrap && window.bootstrap.Modal) {
        modal = new bootstrap.Modal($modal[0]);
    }
    var currentModule = '';
    var currentLabel = '';
    var currentPage = 1;

    function showActivityState(state) {
        $('#userAccessActivityLoading').toggleClass('d-none', state !== 'loading');
        $('#userAccessActivityEmpty').toggleClass('d-none', state !== 'empty');
        $('#userAccessActivityError').toggleClass('d-none', state !== 'error');
        $('#userAccessActivityContent').toggleClass('d-none', state !== 'content');
    }

    function loadActivityPage(page) {
        if (!activityApiUrl || !currentModule) {
            return;
        }
        currentPage = page;
        showActivityState('loading');
        $('#userAccessActivityError').text('');
        $.ajax({
            url: activityApiUrl,
            method: 'GET',
            cache: false,
            data: {
                module: currentModule,
                page: currentPage,
                per_page: activityPerPage
            },
            dataType: 'json'
        }).done(function (resp) {
            var data = (resp && resp.success === true && resp.data) ? resp.data : null;
            if (!data) {
                showActivityState('error');
                $('#userAccessActivityError').removeClass('d-none').text('Could not load activity.');
                return;
            }
            var items = Array.isArray(data.items) ? data.items : [];
            var totalPages = Number(data.total_pages || 0);
            var total = Number(data.total || 0);
            var pageNum = Number(data.page || currentPage);
            currentPage = pageNum;
            $('#userAccessActivityModalLabel').text((data.label || currentLabel || currentModule) + ' activity');
            if (!items.length) {
                showActivityState('empty');
                return;
            }
            var html = '';
            for (var i = 0; i < items.length; i += 1) {
                var row = items[i] || {};
                html += '<tr>'
                    + '<td class="small"><code>' + escapeHtml(row.created_at || '') + '</code></td>'
                    + '<td class="small">' + escapeHtml(row.action || '') + '</td>'
                    + '<td class="small">' + escapeHtml((row.entity_type || '') + ' #' + (row.entity_id || '')) + '</td>'
                    + '</tr>';
            }
            $('#userAccessActivityBody').html(html);
            $('#userAccessActivityPageInfo').text(
                'Page ' + pageNum + ' of ' + Math.max(1, totalPages) + ' · ' + total + ' total'
            );
            $('#userAccessActivityPrev').prop('disabled', pageNum <= 1);
            $('#userAccessActivityNext').prop('disabled', totalPages <= 0 || pageNum >= totalPages);
            showActivityState('content');
        }).fail(function (xhr) {
            var msg = 'Could not load activity.';
            if (xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message) {
                msg = xhr.responseJSON.error.message;
            }
            showActivityState('error');
            $('#userAccessActivityError').removeClass('d-none').text(msg);
        });
    }

    $(document).on('click', '.js-access-activity-card', function () {
        currentModule = String($(this).data('module') || '');
        currentLabel = String($(this).data('label') || currentModule);
        if (!currentModule) {
            return;
        }
        $('#userAccessActivityModalLabel').text(currentLabel + ' activity');
        if (modal) {
            modal.show();
        } else {
            $modal.modal('show');
        }
        loadActivityPage(1);
    });

    $('#userAccessActivityPrev').on('click', function () {
        if (currentPage > 1) {
            loadActivityPage(currentPage - 1);
        }
    });
    $('#userAccessActivityNext').on('click', function () {
        loadActivityPage(currentPage + 1);
    });
});
