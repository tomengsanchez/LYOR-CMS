$(function() {
    var $list = $('#linkedProjectsList');
    var $select = $('#projectSelect');
    var addedIds = {};
    $list.find('input[name="project_ids[]"]').each(function() { addedIds[$(this).val()] = true; });
    $select.select2({
        placeholder: 'Search project to add',
        allowClear: true,
        ajax: {
            url: '/api/projects',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term || '' }; },
            processResults: function(data) {
                var rows = (data && typeof data === 'object' && Object.prototype.hasOwnProperty.call(data, 'success') && 'data' in data) ? data.data : data;
                if (!Array.isArray(rows)) rows = [];
                var items = rows.map(function(p) {
                    return { id: p.id, text: p.name || ('#' + p.id) };
                }).filter(function(p) { return !addedIds[p.id]; });
                return { results: items };
            }
        },
        minimumInputLength: 0
    });
    $select.on('select2:select', function(e) {
        var d = e.params.data;
        if (addedIds[d.id]) return;
        addedIds[d.id] = true;
        var $badge = $('<span class="badge bg-primary d-inline-flex align-items-center gap-1 py-2 px-2">' +
            escapeHtml(d.text) +
            '<input type="hidden" name="project_ids[]" value="' + d.id + '">' +
            '<button type="button" class="btn-remove-project border-0 bg-transparent text-white p-0 ms-1" style="font-size: 1em; line-height: 1; opacity: 0.9;" data-id="' + d.id + '" aria-label="Remove">x</button></span>');
        $list.append($badge);
        $badge.find('.btn-remove-project').on('click', removeProject);
        $select.val(null).trigger('change');
    });
    function removeProject() {
        var id = $(this).data('id');
        delete addedIds[id];
        $(this).closest('.badge').remove();
    }
    $list.on('click', '.btn-remove-project', removeProject);
    function escapeHtml(t) { return $('<div>').text(t).html(); }
});
