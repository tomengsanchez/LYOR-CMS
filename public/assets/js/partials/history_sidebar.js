(function(){
    function shouldLoadMore(container) {
        if (!container) return false;
        var hasMore = container.getAttribute('data-has-more') === '1';
        var loading = container.getAttribute('data-loading') === '1';
        if (!hasMore || loading) return false;
        return container.scrollTop + container.clientHeight >= container.scrollHeight - 40;
    }
    function attachScroll(container) {
        if (!container) return;
        container.addEventListener('scroll', function () {
            if (!shouldLoadMore(container)) return;
            loadNextPage(container);
        });
    }
    function loadNextPage(container) {
        var entityType = container.getAttribute('data-entity-type') || '';
        var entityId = parseInt(container.getAttribute('data-entity-id') || '0', 10);
        var page = parseInt(container.getAttribute('data-page') || '1', 10);
        var pageSize = parseInt(container.getAttribute('data-page-size') || '20', 10);
        if (!entityType || !entityId) return;
        container.setAttribute('data-loading', '1');
        var loadingEl = container.querySelector('.history-loading');
        if (loadingEl) loadingEl.style.display = 'block';
        var nextPage = page + 1;
        var url = '/api/history?entity_type=' + encodeURIComponent(entityType) + '&entity_id=' + encodeURIComponent(entityId) + '&page=' + encodeURIComponent(nextPage) + '&per_page=' + encodeURIComponent(pageSize);
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (data) {
                var list = container.querySelector('.history-list');
                if (!list) {
                    list = document.createElement('ul');
                    list.className = 'list-unstyled mb-0 small history-list';
                    var emptyMsg = container.querySelector('.history-empty-message');
                    if (emptyMsg) emptyMsg.remove();
                    container.insertBefore(list, loadingEl || null);
                }
                (data.items || []).forEach(function (entry) {
                    var li = document.createElement('li');
                    li.className = 'mb-2';
                    var title = document.createElement('div');
                    var action = (entry.action || '').replace(/_/g, ' ');
                    action = action.charAt(0).toUpperCase() + action.slice(1);
                    title.innerHTML = '<strong>' + escapeHtml(action) + '</strong>';
                    var meta = document.createElement('div');
                    var metaText = entry.created_at || '';
                    if (entry.created_by_name) metaText += (metaText ? ' · ' : '') + entry.created_by_name;
                    meta.className = 'text-muted';
                    meta.textContent = metaText;
                    li.appendChild(title);
                    li.appendChild(meta);
                    if (entry.changes && typeof entry.changes === 'object') {
                        var changesList = document.createElement('ul');
                        changesList.className = 'mb-0 mt-1 ps-3';
                        Object.keys(entry.changes).forEach(function (field) {
                            var change = entry.changes[field] || {};
                            var cli = document.createElement('li');
                            if (change && typeof change === 'object' && (Object.prototype.hasOwnProperty.call(change, 'from') || Object.prototype.hasOwnProperty.call(change, 'to'))) {
                                var fromVal = String(change.from !== undefined ? change.from : '');
                                var toVal = String(change.to !== undefined ? change.to : '');
                                cli.innerHTML = escapeHtml(field) + ': <span class="text-muted">' + escapeHtml(fromVal) + '</span> -> <span class="text-success">' + escapeHtml(toVal) + '</span>';
                            } else {
                                var rawVal = '';
                                if (Array.isArray(change) || (change && typeof change === 'object')) {
                                    try { rawVal = JSON.stringify(change); } catch (e) { rawVal = String(change); }
                                } else {
                                    rawVal = String(change);
                                }
                                cli.innerHTML = escapeHtml(field) + ': <span class="text-success">' + escapeHtml(rawVal) + '</span>';
                            }
                            changesList.appendChild(cli);
                        });
                        li.appendChild(changesList);
                    }
                    list.appendChild(li);
                });
                container.setAttribute('data-page', String(nextPage));
                container.setAttribute('data-has-more', data.has_more ? '1' : '0');
            })
            .catch(function () {})
            .finally(function () {
                container.removeAttribute('data-loading');
                if (loadingEl) loadingEl.style.display = 'none';
            });
    }
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    document.addEventListener('DOMContentLoaded', function () {
        var containers = document.querySelectorAll('.history-scroll');
        containers.forEach(attachScroll);
    });
})();
