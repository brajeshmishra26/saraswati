document.addEventListener('DOMContentLoaded', function () {
    var persistableOverlays = document.querySelectorAll('[data-persist-key]');
    persistableOverlays.forEach(function (overlay) {
        var key = overlay.getAttribute('data-persist-key');
        if (!key) {
            return;
        }

        try {
            if (localStorage.getItem(key) === '1') {
                overlay.style.display = 'none';
            }
        } catch (e) {
            // If storage is blocked, keep default behavior.
        }
    });

    var fadeEls = document.querySelectorAll('.fade-up');
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.12 });

    fadeEls.forEach(function (el) { observer.observe(el); });

    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var val = btn.getAttribute('data-copy') || '';
            navigator.clipboard.writeText(val).then(function () {
                var original = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = original; }, 1200);
            });
        });
    });

    document.querySelectorAll('[data-video-id]').forEach(function (card) {
        card.addEventListener('click', function () {
            if (card.getAttribute('data-loaded') === '1') {
                return;
            }

            var id = card.getAttribute('data-video-id');
            if (!id) {
                return;
            }

            var iframe = document.createElement('iframe');
            iframe.width = '100%';
            iframe.height = '315';
            iframe.loading = 'lazy';
            iframe.src = 'https://www.youtube.com/embed/' + id + '?rel=0';
            iframe.title = 'YouTube video player';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            card.innerHTML = '';
            card.appendChild(iframe);
            card.setAttribute('data-loaded', '1');
        });
    });

    document.querySelectorAll('[data-dismiss-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var selector = btn.getAttribute('data-dismiss-target');
            if (!selector) {
                return;
            }

            var target = document.querySelector(selector);
            if (target) {
                target.style.display = 'none';

                var persistKey = target.getAttribute('data-persist-key');
                if (persistKey) {
                    try {
                        localStorage.setItem(persistKey, '1');
                    } catch (e) {
                        // Ignore storage write failures.
                    }
                }
            }
        });
    });

    document.querySelectorAll('[data-upi-pay]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var href = link.getAttribute('href') || '';
            if (href.indexOf('upi://') !== 0) {
                return;
            }

            var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent || '');
            if (isMobile) {
                return;
            }

            event.preventDefault();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(href);
            }
            alert('UPI apps usually open on mobile devices. Link copied, please use it on your mobile app or scan the QR.');
        });
    });
});
