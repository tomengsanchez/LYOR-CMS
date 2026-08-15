/**
 * Lightweight public carousel for Layout Builder carousel modules.
 * No Bootstrap JS dependency.
 */
(function () {
    'use strict';

    function initCarousel(root) {
        if (!root || root.getAttribute('data-carousel-ready') === '1') {
            return;
        }
        var slides = Array.prototype.slice.call(root.querySelectorAll('.cms-carousel-slide'));
        if (slides.length === 0) {
            return;
        }
        root.setAttribute('data-carousel-ready', '1');

        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-carousel-dot]'));
        var prevBtn = root.querySelector('[data-carousel-prev]');
        var nextBtn = root.querySelector('[data-carousel-next]');
        var autoplay = root.getAttribute('data-autoplay') === '1';
        var intervalMs = parseInt(root.getAttribute('data-interval') || '5000', 10);
        if (isNaN(intervalMs) || intervalMs < 2000) {
            intervalMs = 5000;
        }
        var index = 0;
        var timer = null;
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function goTo(next) {
            if (slides.length === 0) {
                return;
            }
            index = ((next % slides.length) + slides.length) % slides.length;
            slides.forEach(function (slide, i) {
                var active = i === index;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
            dots.forEach(function (dot, i) {
                var active = i === index;
                dot.classList.toggle('is-active', active);
                if (active) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });
        }

        function next() {
            goTo(index + 1);
        }

        function prev() {
            goTo(index - 1);
        }

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function start() {
            stop();
            if (!autoplay || reducedMotion || slides.length < 2) {
                return;
            }
            timer = setInterval(next, intervalMs);
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                prev();
                start();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                next();
                start();
            });
        }
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                goTo(Number(dot.getAttribute('data-carousel-dot')) || 0);
                start();
            });
        });

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', start);

        goTo(0);
        start();
    }

    function boot() {
        document.querySelectorAll('[data-cms-carousel]').forEach(initCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
