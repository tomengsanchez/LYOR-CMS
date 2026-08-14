(function () {
    var ADMIN_HELP = '/admin/help';
    var ADMIN_HELP_FRAGMENT = '/admin/help/fragment';

    var modalEl = document.getElementById('helpModal');
    if (!modalEl) {
        return;
    }

    var bodyEl = document.getElementById('helpModalBody');
    var titleEl = document.getElementById('helpModalLabel');
    var fullPageEl = document.getElementById('helpModalFullPage');
    var modalInstance = null;

    function getModal() {
        if (typeof bootstrap === 'undefined') {
            return null;
        }
        if (!modalInstance) {
            modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return modalInstance;
    }

    function parseHelpFrom(href) {
        try {
            var url = new URL(href, window.location.origin);
            if (url.pathname !== ADMIN_HELP && url.pathname !== '/help') {
                return null;
            }
            return url.searchParams.get('from') || '';
        } catch (err) {
            return null;
        }
    }

    function buildHelpUrl(from, fragment) {
        var base = fragment ? ADMIN_HELP_FRAGMENT : ADMIN_HELP;
        return from ? (base + '?from=' + encodeURIComponent(from)) : base;
    }

    function setLoading() {
        if (!bodyEl) {
            return;
        }
        bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    }

    function applyFragment(html) {
        if (!bodyEl) {
            return;
        }
        // Help fragments are static PHP views (admin-authored only). Do not inject
        // user-controlled HTML here — that would be an XSS vector.
        bodyEl.innerHTML = html;
        var header = bodyEl.querySelector('.help-content-header');
        if (header) {
            var heading = header.querySelector('h2');
            if (heading && titleEl) {
                titleEl.textContent = heading.textContent.trim();
            }
            header.style.display = 'none';
        }
        bodyEl.scrollTop = 0;
    }

    function loadHelp(from) {
        var qs = from ? ('?from=' + encodeURIComponent(from)) : '';
        if (fullPageEl) {
            fullPageEl.href = ADMIN_HELP + qs;
        }
        setLoading();
        return fetch(ADMIN_HELP_FRAGMENT + qs, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) {
                throw new Error('load failed');
            }
            return res.text();
        }).then(applyFragment).catch(function () {
            if (bodyEl) {
                bodyEl.innerHTML = '<p class="text-danger mb-0">Unable to load help. <a href="' + buildHelpUrl(from, false) + '">Open full help page</a>.</p>';
            }
        });
    }

    function openHelp(from) {
        if (titleEl) {
            titleEl.textContent = 'Help';
        }
        loadHelp(from).then(function () {
            var modal = getModal();
            if (modal) {
                modal.show();
            }
        });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href^="/admin/help"], a[href^="/help"]');
        if (!link || link.classList.contains('js-help-full-page')) {
            return;
        }
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }
        var from = parseHelpFrom(link.getAttribute('href') || '');
        if (from === null) {
            return;
        }
        e.preventDefault();
        if (modalEl.classList.contains('show')) {
            loadHelp(from);
            return;
        }
        openHelp(from);
    });
})();
