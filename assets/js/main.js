(function () {
    var header = document.querySelector('.site-header');
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.querySelector('.nav');

    function onScroll() {
        if (!header) {
            return;
        }
        header.classList.toggle('is-scrolled', window.scrollY > 8);
    }

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 860) {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    var revealItems = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.16 });

        revealItems.forEach(function (item) {
            observer.observe(item);
        });
    } else {
        revealItems.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }

    var filters = document.querySelectorAll('[data-filter]');
    var cards = document.querySelectorAll('[data-cat]');

    function applyFilter(name) {
        filters.forEach(function (button) {
            var active = button.getAttribute('data-filter') === name;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        cards.forEach(function (card) {
            var show = name === 'toate' || card.getAttribute('data-cat') === name;
            card.hidden = !show;
        });
    }

    if (filters.length && cards.length) {
        filters.forEach(function (button) {
            button.addEventListener('click', function () {
                var name = button.getAttribute('data-filter') || 'toate';
                applyFilter(name);
                var next = name === 'toate' ? window.location.pathname : '#' + name;
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', next);
                }
            });
        });

        var initial = (window.location.hash || '').replace('#', '');
        var known = false;
        filters.forEach(function (button) {
            if (button.getAttribute('data-filter') === initial) {
                known = true;
            }
        });
        if (known && initial !== 'toate') {
            applyFilter(initial);
        }
    }

    document.querySelectorAll('.buy-form').forEach(function (form) {
        var price = form.querySelector('.buy-price');
        var options = form.querySelectorAll('input[name="optiune"]');
        if (!price || options.length < 2) {
            return;
        }
        function showPrice() {
            var selected = form.querySelector('input[name="optiune"]:checked');
            if (selected) {
                price.textContent = selected.getAttribute('data-price') || '';
            }
        }
        options.forEach(function (option) {
            option.addEventListener('change', showPrice);
        });
    });
})();
