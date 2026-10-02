(function () {
    var el = document.currentScript || document.getElementById('cmsAdcashTags');
    if (!el) {
        return;
    }
    var METHODS = {
        'autotag': 'runAutoTag',
        'pop': 'runPop',
        'interstitial': 'runInterstitial',
        'inpage-push': 'runInPagePush',
        'banner': 'runBanner',
        'video-slider': 'runVideoSlider'
    };
    var zones = {};
    var hasAny = false;
    for (var key in METHODS) {
        var zoneId = (el.getAttribute('data-zone-' + key) || '').trim();
        if (zoneId) {
            zones[key] = zoneId;
            hasAny = true;
        }
    }
    if (!hasAny) {
        return;
    }
    var attempts = 0;
    function init() {
        var lib = window.aclib;
        if (lib && typeof lib === 'object') {
            for (var prop in zones) {
                var fn = lib[METHODS[prop]];
                if (typeof fn === 'function') {
                    try {
                        fn.call(lib, { zoneId: zones[prop] });
                    } catch (e) {
                        /* keep other zones running if one fails */
                    }
                }
            }
            return;
        }
        attempts++;
        if (attempts < 50) {
            setTimeout(init, 100);
        }
    }
    init();
})();
