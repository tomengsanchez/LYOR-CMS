(function () {
    'use strict';

    var topBtn = document.querySelector('[data-back-to-top]');
    if (topBtn) {
        var onScroll = function () {
            if (window.scrollY > 480) {
                topBtn.hidden = false;
            } else {
                topBtn.hidden = true;
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
        topBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.getElementById('public-content');
            if (target && typeof target.focus === 'function') {
                target.focus();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    var copyBtns = document.querySelectorAll('[data-copy-url]');
    copyBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-copy-url') || window.location.href;
            var label = btn.getAttribute('data-copied-label') || 'Copied';
            var original = btn.textContent;
            var done = function () {
                btn.textContent = label;
                window.setTimeout(function () {
                    btn.textContent = original;
                }, 1600);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done).catch(function () {
                    fallbackCopy(url, done);
                });
                return;
            }
            fallbackCopy(url, done);
        });
    });

    var shareBtns = document.querySelectorAll('[data-native-share]');
    shareBtns.forEach(function (btn) {
        if (!navigator.share) {
            btn.hidden = true;
            return;
        }
        btn.hidden = false;
        btn.addEventListener('click', function () {
            navigator.share({
                title: btn.getAttribute('data-share-title') || document.title,
                url: btn.getAttribute('data-share-url') || window.location.href
            }).catch(function () {});
        });
    });

    function fallbackCopy(url, done) {
        var input = document.createElement('input');
        input.value = url;
        input.setAttribute('readonly', 'readonly');
        input.style.position = 'absolute';
        input.style.left = '-9999px';
        document.body.appendChild(input);
        input.select();
        try {
            document.execCommand('copy');
            done();
        } catch (err) {
            /* ignore */
        }
        document.body.removeChild(input);
    }
})();
