document.addEventListener('DOMContentLoaded', function () {
    var menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        var updateMenuToggle = function () {
            var collapsed = document.body.classList.contains('menu-collapsed');
            menuToggle.setAttribute('aria-label', collapsed ? 'Expand menu' : 'Collapse menu');
            menuToggle.setAttribute('title', collapsed ? 'Expand menu' : 'Collapse menu');
            menuToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            menuToggle.textContent = collapsed ? '»' : '☰';
        };

        if (localStorage.getItem('adminMenuCollapsed') === '1') {
            document.body.classList.add('menu-collapsed');
        }
        updateMenuToggle();

        menuToggle.addEventListener('click', function () {
            document.body.classList.toggle('menu-collapsed');
            localStorage.setItem(
                'adminMenuCollapsed',
                document.body.classList.contains('menu-collapsed') ? '1' : '0'
            );
            updateMenuToggle();
        });
    }

    var cartPanel = document.getElementById('cart');

    function openCartPanel() {
        if (!cartPanel) {
            return;
        }
        cartPanel.hidden = false;
        cartPanel.classList.add('is-visible');
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#cart');
        }
        cartPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeCartPanel() {
        if (!cartPanel) {
            return;
        }
        cartPanel.hidden = true;
        cartPanel.classList.remove('is-visible');
    }

    function isCartLink(link) {
        var href = link.getAttribute('href') || '';
        return href === '#cart' || href.indexOf('#cart') !== -1 || link.classList.contains('cart-open-link');
    }

    document.querySelectorAll('.cart-open-link, a[href="#cart"], a[href*="index.php#cart"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!cartPanel) {
                return;
            }
            event.preventDefault();
            openCartPanel();
            document.body.classList.remove('nav-open');
            var mobileMenuBtn = document.getElementById('mobileMenuBtn');
            if (mobileMenuBtn) {
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
                mobileMenuBtn.textContent = '☰';
            }
        });
    });

    if (cartPanel && window.location.hash === '#cart') {
        openCartPanel();
    }

    document.querySelectorAll('a[href="#products"], a[href="#contact"]').forEach(function (link) {
        link.addEventListener('click', function () {
            closeCartPanel();
        });
    });

    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var siteNav = document.getElementById('siteNav');
    if (mobileMenuBtn && siteNav) {
        mobileMenuBtn.addEventListener('click', function () {
            var isOpen = document.body.classList.toggle('nav-open');
            mobileMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            mobileMenuBtn.textContent = isOpen ? '✕' : '☰';
        });

        siteNav.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (!isCartLink(link)) {
                    closeCartPanel();
                }
                document.body.classList.remove('nav-open');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
                mobileMenuBtn.textContent = '☰';
            });
        });
    }

    document.querySelectorAll('.add-to-cart-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = button.closest('.add-to-cart-form');
            if (!form) {
                return;
            }
            form.classList.add('is-qty-open');
            var qtyInput = form.querySelector('.qty-input');
            if (qtyInput) {
                qtyInput.focus();
                qtyInput.select();
            }
        });
    });

    var searchInput = document.getElementById('productSearch');
    var emptySearch = document.getElementById('emptySearch');
    var productGrid = document.getElementById('productGrid');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var query = searchInput.value.trim().toLowerCase();
            var cards = document.querySelectorAll('.product-card');
            var visibleCount = 0;
            cards.forEach(function (card) {
                var text = card.innerText.toLowerCase();
                var match = text.includes(query);
                card.style.display = match ? '' : 'none';
                if (match) {
                    visibleCount++;
                }
            });
            if (emptySearch) {
                emptySearch.classList.toggle('visible', query !== '' && visibleCount === 0);
            }
            if (productGrid) {
                productGrid.style.display = visibleCount === 0 && query !== '' ? 'none' : '';
            }
        });
    }

    var orderStatusSelects = document.querySelectorAll('.order-status-select');
    orderStatusSelects.forEach(function (select) {
        var previousValue = select.value;
        select.addEventListener('change', function () {
            var form = select.closest('.order-status-form');
            if (!form) {
                return;
            }
            select.classList.add('is-updating');
            select.disabled = true;
            form.submit();
        });
        select.addEventListener('focus', function () {
            previousValue = select.value;
        });
        select.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                select.value = previousValue;
            }
        });
    });

    var loginAlert = document.querySelector('.login-alert-error');
    if (loginAlert) {
        var emailField = document.getElementById('email');
        if (emailField) {
            emailField.focus();
        }
    }

    var passwordInput = document.getElementById('password');
    var toggleButton = document.getElementById('togglePassword');
    var iconEyeOpen = document.getElementById('iconEyeOpen');
    var iconEyeClosed = document.getElementById('iconEyeClosed');
    if (passwordInput && toggleButton) {
        toggleButton.addEventListener('click', function () {
            var isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleButton.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            toggleButton.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
            if (iconEyeOpen && iconEyeClosed) {
                iconEyeOpen.classList.toggle('icon-hidden', isPassword);
                iconEyeClosed.classList.toggle('icon-hidden', !isPassword);
            }
        });
    }

    var reportToggles = document.querySelectorAll('.report-filter-toggle');
    reportToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            if (!targetId) {
                return;
            }
            var panel = document.getElementById(targetId);
            if (!panel) {
                return;
            }
            panel.classList.toggle('is-hidden');
            if (!panel.classList.contains('is-hidden')) {
                var firstInput = panel.querySelector('input[type="datetime-local"]');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        });
    });

    var profileToggles = document.querySelectorAll('.profile-toggle-btn');
    profileToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            if (!targetId) {
                return;
            }
            var panel = document.getElementById(targetId);
            if (!panel) {
                return;
            }
            panel.classList.toggle('is-hidden');
            if (!panel.classList.contains('is-hidden')) {
                var firstInput = panel.querySelector('input');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        });
    });

    var productCards = document.querySelectorAll('.product-card');
    if (productCards.length > 0) {
        productCards.forEach(function (card, index) {
            card.classList.add('product-reveal');
            card.style.transitionDelay = Math.min(index * 60, 360) + 'ms';
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        obs.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15,
                rootMargin: '0px 0px -30px 0px'
            });
            productCards.forEach(function (card) {
                observer.observe(card);
            });
        } else {
            productCards.forEach(function (card) {
                card.classList.add('in-view');
            });
        }
    }
});
