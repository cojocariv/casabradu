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

    if (window.location.hash === '#aparate' && /\/produse\.php$/.test(window.location.pathname)) {
        window.location.replace(window.location.pathname.replace(/produse\.php$/, 'aparate.php'));
        return;
    }

    var filters = document.querySelectorAll('[data-filter]');
    var cards = document.querySelectorAll('[data-cat]');

    function applyFilter(name) {
        filters.forEach(function (button) {
            var active = button.getAttribute('data-filter') === name;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        cards.forEach(function (card) {
            var show = name === 'toate' || card.getAttribute('data-cat') === name;
            card.hidden = !show;
            card.classList.toggle('is-filtered-out', !show);
        });
    }

    if (filters.length && cards.length) {
        filters.forEach(function (button) {
            button.setAttribute('aria-pressed', button.classList.contains('is-active') ? 'true' : 'false');
            button.addEventListener('click', function () {
                var name = button.getAttribute('data-filter') || 'toate';
                applyFilter(name);
                var next = name === 'toate'
                    ? (window.location.pathname + window.location.search)
                    : ('#' + name);
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
        if (known && initial !== '' && initial !== 'toate') {
            applyFilter(initial);
        }
    }

    document.querySelectorAll('.buy-form').forEach(function (form) {
        var price = form.querySelector('.buy-price');
        if (!price) {
            return;
        }

        if (form.classList.contains('buy-form-split')) {
            var hidden = form.querySelector('[data-optiune]');
            var map = {};
            form.querySelectorAll('.buy-price-entry').forEach(function (entry) {
                var key = entry.getAttribute('data-key');
                if (key) {
                    map[key] = entry.getAttribute('data-price') || '';
                }
            });
            var blurb = form.querySelector('[data-buy-blurb]');
            function syncSplit() {
                var flavor = form.querySelector('input[name="sortiment"]:checked');
                var pack = form.querySelector('input[name="ambalare"]:checked');
                if (!flavor || !pack || !hidden) {
                    return;
                }
                var key = String(flavor.value) + String(pack.value);
                hidden.value = key;
                if (map[key]) {
                    price.textContent = map[key];
                }
                if (blurb) {
                    var text = flavor.getAttribute('data-blurb') || '';
                    blurb.textContent = text;
                    blurb.hidden = text === '';
                }
            }
            form.querySelectorAll('input[name="sortiment"], input[name="ambalare"]').forEach(function (option) {
                option.addEventListener('change', syncSplit);
                option.addEventListener('click', syncSplit);
            });
            syncSplit();
            return;
        }

        var options = form.querySelectorAll('input[name="optiune"]');
        if (options.length < 2) {
            return;
        }
        var blurb = form.querySelector('[data-buy-blurb]');
        function syncChoice() {
            var selected = form.querySelector('input[name="optiune"]:checked');
            if (!selected) {
                return;
            }
            price.textContent = selected.getAttribute('data-price') || '';
            if (blurb) {
                var text = selected.getAttribute('data-blurb') || '';
                blurb.textContent = text;
                blurb.hidden = text === '';
            }
            var imgPath = selected.getAttribute('data-image');
            if (!imgPath) {
                return;
            }
            var card = form.closest('.shop-item, .product-card, .catalog-row');
            if (!card) {
                return;
            }
            var photo = card.querySelector('.shop-item-photo img, .product-card-photo img, .catalog-photo img');
            if (photo) {
                photo.src = imgPath;
            }
        }
        options.forEach(function (option) {
            option.addEventListener('change', syncChoice);
        });
        syncChoice();
    });

    (function initCartLive() {
        var root = document.getElementById('cart-live');
        if (!root) {
            return;
        }

        var confirmLabel = root.getAttribute('data-confirm') || 'la confirmare';
        var leiLabel = root.getAttribute('data-lei') || 'lei';
        var totalEl = document.querySelector('.cart-total-value');
        var tvaLine = document.querySelector('.cart-tva-line');
        var tvaEl = document.querySelector('.cart-tva-value');
        var confirmNote = document.querySelector('.cart-confirm-note');
        var qtyInputs = root.querySelectorAll('.cart-qty');

        function formatLei(bani) {
            var neg = bani < 0;
            bani = Math.abs(Math.round(bani));
            var whole = Math.floor(bani / 100);
            var frac = String(bani % 100);
            if (frac.length < 2) {
                frac = '0' + frac;
            }
            var wholeStr = String(whole).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return (neg ? '-' : '') + wholeStr + ',' + frac;
        }

        function recalc() {
            var total = 0;
            var complete = true;
            var hasPricedQty = false;

            root.querySelectorAll('.cart-row').forEach(function (row) {
                var input = row.querySelector('.cart-qty');
                var sumCell = row.querySelector('.cart-line-sum');
                if (!input || !sumCell) {
                    return;
                }

                var qty = parseInt(input.value, 10);
                if (isNaN(qty) || qty < 0) {
                    qty = 0;
                }
                if (qty > 999) {
                    qty = 999;
                }

                var priceAttr = row.getAttribute('data-price-bani');
                if (priceAttr === null || priceAttr === '') {
                    if (qty > 0) {
                        complete = false;
                    }
                    sumCell.textContent = confirmLabel;
                    return;
                }

                var price = parseInt(priceAttr, 10);
                if (isNaN(price) || price < 0) {
                    if (qty > 0) {
                        complete = false;
                    }
                    sumCell.textContent = confirmLabel;
                    return;
                }

                var sum = price * qty;
                sumCell.textContent = formatLei(sum);
                if (qty > 0) {
                    hasPricedQty = true;
                    total += sum;
                }
            });

            if (!totalEl) {
                return;
            }

            if (!complete || !hasPricedQty) {
                totalEl.textContent = confirmLabel;
                if (tvaLine) {
                    tvaLine.hidden = true;
                }
                if (confirmNote) {
                    confirmNote.hidden = false;
                }
                return;
            }

            totalEl.textContent = formatLei(total) + ' ' + leiLabel;
            if (tvaEl) {
                tvaEl.textContent = formatLei(Math.round(total * 20 / 120));
            }
            if (tvaLine) {
                tvaLine.hidden = false;
            }
            if (confirmNote) {
                confirmNote.hidden = true;
            }
        }

        qtyInputs.forEach(function (input) {
            input.addEventListener('input', recalc);
            input.addEventListener('change', recalc);
        });
    })();

    if (!document.body.classList.contains('page-admin')) {
        function blockCopy(event) {
            var field = event.target && event.target.closest
                ? event.target.closest('input, textarea, select')
                : null;
            if (field) {
                return;
            }
            event.preventDefault();
        }
        document.addEventListener('copy', blockCopy);
        document.addEventListener('cut', blockCopy);
    }

    (function initWhatsAppChat() {
        var root = document.getElementById('wa-chat');
        if (!root) {
            return;
        }
        var panel = document.getElementById('wa-chat-panel');
        var toggle = document.getElementById('wa-chat-toggle');
        var closeBtn = document.getElementById('wa-chat-close');
        var message = document.getElementById('wa-chat-message');
        var send = document.getElementById('wa-chat-send');
        var base = root.getAttribute('data-wa-base') || '';
        if (!panel || !toggle || !send || !base) {
            return;
        }

        function syncLink() {
            var text = message ? String(message.value || '').trim() : '';
            if (!text) {
                text = root.getAttribute('data-wa-default') || '';
            }
            send.href = base + (text ? ('?text=' + encodeURIComponent(text)) : '');
        }

        function setOpen(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && message) {
                message.focus();
            }
        }

        toggle.addEventListener('click', function () {
            setOpen(panel.hidden);
        });
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setOpen(false);
            });
        }
        if (message) {
            message.addEventListener('input', syncLink);
        }
        syncLink();
    })();

    document.querySelectorAll('.product-card-machine').forEach(function (card) {
        var main = card.querySelector('[data-machine-main]');
        var thumbs = card.querySelectorAll('[data-machine-thumb]');
        if (!main || thumbs.length < 2) {
            return;
        }
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                var src = thumb.getAttribute('data-machine-thumb');
                if (!src) {
                    return;
                }
                main.src = src;
                thumbs.forEach(function (item) {
                    item.classList.toggle('is-active', item === thumb);
                });
            });
        });
    });
})();
