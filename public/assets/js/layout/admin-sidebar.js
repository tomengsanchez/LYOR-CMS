(function () {
    var toggle = document.getElementById('adminSidebarToggle');
    var overlay = document.getElementById('adminSidebarOverlay');
    if (!toggle) {
        return;
    }

    function setOpen(open) {
        document.body.classList.toggle('admin-sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function () {
        setOpen(!document.body.classList.contains('admin-sidebar-open'));
    });

    if (overlay) {
        overlay.addEventListener('click', function () {
            setOpen(false);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            setOpen(false);
        }
    });

    var sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a') && window.matchMedia('(max-width: 991.98px)').matches) {
                setOpen(false);
            }
        });
    }
})();
