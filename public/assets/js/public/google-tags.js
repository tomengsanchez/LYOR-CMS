(function () {
    var el = document.currentScript || document.getElementById('cmsGoogleTags');
    if (!el) {
        return;
    }
    var gaId = (el.getAttribute('data-ga-id') || '').trim();
    var adsId = (el.getAttribute('data-ads-id') || '').trim();
    if (!gaId && !adsId) {
        return;
    }
    window.dataLayer = window.dataLayer || [];
    function gtag() {
        window.dataLayer.push(arguments);
    }
    window.gtag = window.gtag || gtag;
    gtag('js', new Date());
    if (gaId) {
        gtag('config', gaId);
    }
    if (adsId) {
        gtag('config', adsId);
    }
})();
