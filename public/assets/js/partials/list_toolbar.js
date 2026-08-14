(function() {
    if (window.__listToolbarInitDone) return;
    window.__listToolbarInitDone = true;
    function bindForConfig(cfg) {
        if (!cfg || !cfg.modalId) return;
        var mid = cfg.modalId;
        document.getElementById('columnsSelectAll_' + mid)?.addEventListener('click', function() {
            document.querySelectorAll('#' + mid + ' .columns-col-cb').forEach(function(cb) { cb.checked = true; });
        });
        document.getElementById('columnsDeselectAll_' + mid)?.addEventListener('click', function() {
            document.querySelectorAll('#' + mid + ' .columns-col-cb').forEach(function(cb) { cb.checked = false; });
        });
        if (cfg.exportModalId) {
            var emid = cfg.exportModalId;
            document.getElementById('exportSelectAll_' + emid)?.addEventListener('click', function() {
                document.querySelectorAll('#' + emid + ' .export-col-cb').forEach(function(cb) { cb.checked = true; });
            });
            document.getElementById('exportDeselectAll_' + emid)?.addEventListener('click', function() {
                document.querySelectorAll('#' + emid + ' .export-col-cb').forEach(function(cb) { cb.checked = false; });
            });
        }
    }
    var arr = Array.isArray(window.listToolbarConfigs) ? window.listToolbarConfigs : [];
    arr.forEach(bindForConfig);
})();
