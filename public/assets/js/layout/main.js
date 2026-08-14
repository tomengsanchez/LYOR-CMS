(function(){
    function formatNavDateTime() {
        var d = new Date();
        var day = d.getDate(), m = d.getMonth(), y = d.getFullYear();
        var mon = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][m];
        var h = d.getHours(), min = d.getMinutes(), sec = d.getSeconds();
        var hh = h < 10 ? '0' + h : h;
        var mm = min < 10 ? '0' + min : min;
        var ss = sec < 10 ? '0' + sec : sec;
        return day + ' ' + mon + ' ' + y + ', ' + hh + ':' + mm + ':' + ss;
    }
    function updateNavClocks() {
        var s = formatNavDateTime();
        document.querySelectorAll('.nav-live-datetime').forEach(function(el){ el.textContent = s; });
    }
    updateNavClocks();
    setInterval(updateNavClocks, 1000);
})();
$(function(){
    $('.nav-parent').on('click', function(e){
        e.stopPropagation();
        $(this).toggleClass('open');
    });
    var $body = $('body');
    var mobileMq = window.matchMedia('(max-width: 991.98px)');

    function closeSidebarDrawer() {
        $body.removeClass('body-sidebar-open');
    }

    $('#sidebar-toggle').on('click', function(){
        if (mobileMq.matches) {
            $body.toggleClass('body-sidebar-open');
            return;
        }
        closeSidebarDrawer();
    });

    $('#sidebar-overlay').on('click', closeSidebarDrawer);
    $('#main-sidebar').on('click', 'a', function(){
        if (mobileMq.matches) {
            closeSidebarDrawer();
        }
    });

    $(document).on('keydown', function(e){
        if (e.key === 'Escape') {
            closeSidebarDrawer();
        }
    });

    function syncDrawerOnResize() {
        if (!mobileMq.matches) {
            closeSidebarDrawer();
        }
    }
    if (typeof mobileMq.addEventListener === 'function') {
        mobileMq.addEventListener('change', syncDrawerOnResize);
    } else if (typeof mobileMq.addListener === 'function') {
        mobileMq.addListener(syncDrawerOnResize);
    }

    function buildTopnavMobileMenu() {
        var $menuBody = $('#topnav-mobile-menu-body');
        var $topnav = $('.topnav');
        if (!$menuBody.length || !$topnav.length) {
            return;
        }
        var html = '';
        $topnav.find('.topnav-primary-links > .nav-link, .topnav-primary-links > .dropdown').each(function(index){
            var $item = $(this);
            if ($item.hasClass('dropdown')) {
                var $toggle = $item.find('> .dropdown-toggle');
                var label = $.trim($toggle.text()) || 'Menu';
                var active = $toggle.hasClass('active') ? ' active' : '';
                var groupId = 'mobile-nav-group-' + index;
                html += '<div class="mobile-nav-group">';
                html += '<button class="mobile-nav-parent' + active + '" type="button" data-bs-toggle="collapse" data-bs-target="#' + groupId + '" aria-expanded="' + (active !== '' ? 'true' : 'false') + '">' + label + '</button>';
                html += '<div class="mobile-nav-children collapse' + (active !== '' ? ' show' : '') + '" id="' + groupId + '">';
                $item.find('.dropdown-menu > li').each(function(){
                    var $entry = $(this);
                    var $hr = $entry.find('> hr.dropdown-divider');
                    if ($hr.length) {
                        return;
                    }
                    var $header = $entry.find('> .dropdown-header');
                    if ($header.length) {
                        html += '<div class="mobile-nav-label">' + $header.text() + '</div>';
                        return;
                    }
                    var $a = $entry.find('> a.dropdown-item');
                    if ($a.length) {
                        var href = $a.attr('href') || '#';
                        var itemActive = $a.hasClass('active') ? ' active' : '';
                        html += '<a class="mobile-nav-link' + itemActive + '" href="' + href + '">' + $.trim($a.text()) + '</a>';
                    }
                });
                html += '</div></div>';
            } else {
                var href = $item.attr('href') || '#';
                var activeLink = $item.hasClass('active') ? ' active' : '';
                html += '<a class="mobile-nav-link' + activeLink + '" href="' + href + '">' + $.trim($item.text()) + '</a>';
            }
        });

        if (html === '') {
            html = '<p class="text-muted p-3 mb-0">No navigation items available.</p>';
        }
        $menuBody.html(html);
    }

    buildTopnavMobileMenu();

    $('#topnav-mobile-menu-body').on('click', 'a.mobile-nav-link', function(){
        var canvasEl = document.getElementById('topnavMobileMenu');
        if (!canvasEl || !window.bootstrap || !window.bootstrap.Offcanvas) {
            return;
        }
        var canvas = window.bootstrap.Offcanvas.getInstance(canvasEl);
        if (canvas) {
            canvas.hide();
        }
    });

    function updateNotificationBadge(count) {
        var $badge = $('#notification-count');
        if (!$badge.length) return;
        if (count > 0) { $badge.text(count > 99 ? '99+' : count); $badge.show(); } else { $badge.hide(); }
    }
    function unwrapApiNotifications(resp) {
        if (resp && typeof resp === 'object' && Object.prototype.hasOwnProperty.call(resp, 'success') && 'data' in resp) {
            var inner = resp.data;
            if (Array.isArray(inner)) {
                return inner;
            }
            if (inner && Array.isArray(inner.items)) {
                return inner.items;
            }
            return [];
        }
        return Array.isArray(resp) ? resp : [];
    }
    function loadNotificationsList() {
        var $list = $('#notification-list');
        $list.html('<span class="dropdown-item text-muted text-center py-2">Loading...</span>');
        $.getJSON('/api/notifications').done(function(resp){
            var data = unwrapApiNotifications(resp);
            if (!data.length) { $list.html('<span class="dropdown-item text-muted text-center py-3">No new notifications</span>'); updateNotificationBadge(0); return; }
            var html = '';
            data.forEach(function(n){
                var url = n.url || '/admin/notifications/click/' + n.id;
                html += '<a class="dropdown-item py-2 d-block" href="' + url + '">' + (n.message || 'Notification') + '<br><small class="text-muted">' + (n.created_at || '') + '</small></a>';
            });
            $list.html(html);
            updateNotificationBadge(data.length);
        }).fail(function(){ $list.html('<span class="dropdown-item text-muted text-center py-3">Unable to load notifications</span>'); });
    }
    function pollNotificationCount() {
        $.getJSON('/api/notifications').done(function(resp){
            var data = unwrapApiNotifications(resp);
            updateNotificationBadge(data.length ? data.length : 0);
        });
    }
    $('.notification-dropdown').on('show.bs.dropdown', function(){ loadNotificationsList(); });
    $(document).on('submit', 'form[data-confirm]', function(e){
        var msg = this.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(msg)) e.preventDefault();
    });
    $(document).on('click', 'button[data-confirm]', function(e){
        var msg = this.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(msg)) e.preventDefault();
    });
    $(document).on('change', '.js-page-jump', function(){
        if (this.value) window.location.href = this.value;
    });
    pollNotificationCount();
    setInterval(pollNotificationCount, 15000);
});
