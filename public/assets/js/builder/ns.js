/**
 * Shared namespace for visual builder modules (no bundler).
 * Other files call CmsBuilder.bind(fn). Boot calls CmsBuilder.start(ctx).
 */
(function (global) {
    var queue = [];
    var ctx = null;

    global.CmsBuilder = {
        bind: function (fn) {
            if (typeof fn !== 'function') {
                return;
            }
            queue.push(fn);
        },
        context: function () {
            return ctx;
        },
        start: function (next) {
            ctx = next;
            queue.forEach(function (fn) {
                fn(ctx);
            });
            queue = [];
            return ctx;
        }
    };
})(window);
