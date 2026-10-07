document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* =========================================================
       ADMIN SIDEBAR MENU
    ========================================================= */
    var menuToggle = document.getElementById('menuToggle');

    if (menuToggle) {
        var updateMenuToggle = function () {
            var collapsed = document.body.classList.contains('menu-collapsed');

            menuToggle.setAttribute(
                'aria-label',
                collapsed ? 'Expand menu' : 'Collapse menu'
            );

            menuToggle.setAttribute(
                'title',
                collapsed ? 'Expand menu' : 'Collapse menu'
            );

            menuToggle.setAttribute(
                'aria-expanded',
                collapsed ? 'false' : 'true'
            );

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


    /* =========================================================
       MOBILE NAVIGATION
    ========================================================= */
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var siteNav = document.getElementById('siteNav');

    function closeMobileMenu() {
        document.body.classList.remove('nav-open');

        if (mobileMenuBtn) {
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
            mobileMenuBtn.setAttribute('aria-label', 'Open menu');
            mobileMenuBtn.setAttribute('title', 'Open menu');
            mobileMenuBtn.textContent = '☰';
        }
    }

    if (mobileMenuBtn && siteNav) {
        mobileMenuBtn.setAttribute('aria-expanded', 'false');
        mobileMenuBtn.setAttribute('aria-label', 'Open menu');

        mobileMenuBtn.addEventListener('click', function () {
            var isOpen = document.body.classList.toggle('nav-open');

            mobileMenuBtn.setAttribute(
                'aria-expanded',
                isOpen ? 'true' : 'false'
            );

            mobileMenuBtn.setAttribute(
                'aria-label',
                isOpen ? 'Close menu' : 'Open menu'
            );

            mobileMenuBtn.setAttribute(
                'title',
                isOpen ? 'Close menu' : 'Open menu'
            );

            mobileMenuBtn.textContent = isOpen ? '✕' : '☰';
        });

        siteNav.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function () {
                closeMobileMenu();
            });
        });
    }


    /* =========================================================
       CART
    ========================================================= */
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

        requestAnimationFrame(function () {
            cartPanel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    }

    function closeCartPanel() {
        if (!cartPanel) {
            return;
        }

        cartPanel.hidden = true;
        cartPanel.classList.remove('is-visible');
    }

    function isCartLink(link) {
        if (!link) {
            return false;
        }

        var href = link.getAttribute('href') || '';

        return (
            href === '#cart' ||
            href.indexOf('#cart') !== -1 ||
            link.classList.contains('cart-open-link')
        );
    }

    var cartLinks = document.querySelectorAll(
        '.cart-open-link, a[href="#cart"], a[href*="index.php#cart"]'
    );

    cartLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!cartPanel) {
                return;
            }

            event.preventDefault();

            openCartPanel();
            closeMobileMenu();
        });
    });

    if (cartPanel && window.location.hash === '#cart') {
        openCartPanel();
    }

    document.querySelectorAll(
        'a[href="#products"], a[href="#contact"]'
    ).forEach(function (link) {
        link.addEventListener('click', function () {
            closeCartPanel();
        });
    });


    /* =========================================================
       ADD TO CART - QUANTITY REVEAL
    ========================================================= */
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


    /* =========================================================
       PRODUCT SEARCH
    ========================================================= */
    var searchInput = document.getElementById('productSearch');
    var emptySearch = document.getElementById('emptySearch');
    var productGrid = document.getElementById('productGrid');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var query = searchInput.value.trim().toLowerCase();

            var cards = document.querySelectorAll('.product-card');
            var visibleCount = 0;

            cards.forEach(function (card) {
                var text = (card.innerText || '').toLowerCase();

                var matches = query === '' || text.indexOf(query) !== -1;

                card.style.display = matches ? '' : 'none';

                if (matches) {
                    visibleCount++;
                }
            });

            if (emptySearch) {
                emptySearch.classList.toggle(
                    'visible',
                    query !== '' && visibleCount === 0
                );
            }

            if (productGrid) {
                productGrid.style.display =
                    query !== '' && visibleCount === 0
                        ? 'none'
                        : '';
            }
        });
    }


    /* =========================================================
       ORDER STATUS SELECT
    ========================================================= */
    document.querySelectorAll('.order-status-select').forEach(function (select) {
        var previousValue = select.value;

        select.addEventListener('focus', function () {
            previousValue = select.value;
        });

        select.addEventListener('change', function () {
            var form = select.closest('.order-status-form');

            if (!form) {
                return;
            }

            select.classList.add('is-updating');
            select.disabled = true;

            form.submit();
        });

        select.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                select.value = previousValue;
            }
        });
    });


    /* =========================================================
       LOGIN ERROR AUTO-FOCUS
    ========================================================= */
    var loginAlert = document.querySelector('.login-alert-error');

    if (loginAlert) {
        var emailField = document.getElementById('email');

        if (emailField) {
            requestAnimationFrame(function () {
                emailField.focus();
            });
        }
    }


    /* =========================================================
       PASSWORD SHOW / HIDE
    ========================================================= */
    var passwordInput = document.getElementById('password');
    var toggleButton = document.getElementById('togglePassword');
    var iconEyeOpen = document.getElementById('iconEyeOpen');
    var iconEyeClosed = document.getElementById('iconEyeClosed');

    if (passwordInput && toggleButton) {
        toggleButton.setAttribute('aria-label', 'Show password');
        toggleButton.setAttribute('title', 'Show password');

        toggleButton.addEventListener('click', function () {
            var isPassword =
                passwordInput.getAttribute('type') === 'password';

            passwordInput.setAttribute(
                'type',
                isPassword ? 'text' : 'password'
            );

            toggleButton.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );

            toggleButton.setAttribute(
                'title',
                isPassword ? 'Hide password' : 'Show password'
            );

            if (iconEyeOpen && iconEyeClosed) {
                iconEyeOpen.classList.toggle(
                    'icon-hidden',
                    isPassword
                );

                iconEyeClosed.classList.toggle(
                    'icon-hidden',
                    !isPassword
                );
            }
        });
    }


    /* =========================================================
       REPORT FILTER TOGGLES
    ========================================================= */
    document.querySelectorAll('.report-filter-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');

            if (!targetId) {
                return;
            }

            var panel = document.getElementById(targetId);

            if (!panel) {
                return;
            }

            var isHidden = panel.classList.toggle('is-hidden');

            button.setAttribute(
                'aria-expanded',
                isHidden ? 'false' : 'true'
            );

            if (!isHidden) {
                var firstInput = panel.querySelector(
                    'input[type="datetime-local"], input, select'
                );

                if (firstInput) {
                    requestAnimationFrame(function () {
                        firstInput.focus();
                    });
                }
            }
        });
    });


    /* =========================================================
       PROFILE TOGGLES
    ========================================================= */
    document.querySelectorAll('.profile-toggle-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');

            if (!targetId) {
                return;
            }

            var panel = document.getElementById(targetId);

            if (!panel) {
                return;
            }

            var isHidden = panel.classList.toggle('is-hidden');

            button.setAttribute(
                'aria-expanded',
                isHidden ? 'false' : 'true'
            );

            if (!isHidden) {
                var firstInput = panel.querySelector(
                    'input, textarea, select'
                );

                if (firstInput) {
                    requestAnimationFrame(function () {
                        firstInput.focus();
                    });
                }
            }
        });
    });


    /* =========================================================
       PRODUCT CARD REVEAL ANIMATION
    ========================================================= */
    var productCards = document.querySelectorAll('.product-card');

    if (productCards.length > 0) {
        productCards.forEach(function (card, index) {
            card.classList.add('product-reveal');

            card.style.transitionDelay =
                Math.min(index * 60, 360) + 'ms';
        });

        if (
            'IntersectionObserver' in window &&
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            var observer = new IntersectionObserver(
                function (entries, obs) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('in-view');
                            obs.unobserve(entry.target);
                        }
                    });
                },
                {
                    threshold: 0.15,
                    rootMargin: '0px 0px -30px 0px'
                }
            );

            productCards.forEach(function (card) {
                observer.observe(card);
            });
        } else {
            productCards.forEach(function (card) {
                card.classList.add('in-view');
            });
        }
    }


    /* =========================================================
       CLOSE MOBILE MENU WITH ESCAPE
    ========================================================= */
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileMenu();
        }
    });


    /* =========================================================
       CLOSE MOBILE MENU WHEN CLICKING OUTSIDE
    ========================================================= */
    document.addEventListener('click', function (event) {
        if (!document.body.classList.contains('nav-open')) {
            return;
        }

        if (!mobileMenuBtn || !siteNav) {
            return;
        }

        var clickedInsideNav = siteNav.contains(event.target);
        var clickedMenuButton = mobileMenuBtn.contains(event.target);

        if (!clickedInsideNav && !clickedMenuButton) {
            closeMobileMenu();
        }
    });


    /* =========================================================
       SMOOTH ANCHOR SCROLLING
    ========================================================= */
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var href = link.getAttribute('href');

            if (
                !href ||
                href === '#' ||
                href === '#cart' ||
                isCartLink(link)
            ) {
                return;
            }

            var target = document.querySelector(href);

            if (!target) {
                return;
            }

            event.preventDefault();

            closeCartPanel();
            closeMobileMenu();

            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            if (window.history && window.history.pushState) {
                window.history.pushState(null, '', href);
            }
        });
    });
});