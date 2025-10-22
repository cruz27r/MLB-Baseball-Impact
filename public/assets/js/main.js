/**
 * Fenway Modern - Main JavaScript
 * 
 * Handles:
 * - Mobile menu toggle
 * - Smooth scrolling (respects prefers-reduced-motion)
 * - Diamond base hover/focus effects
 * - Header scroll behavior
 * - Keyboard navigation
 */

(function() {
    'use strict';

    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ==========================================================================
       Mobile Menu Toggle
       ========================================================================== */
    function initMobileMenu() {
        const toggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.main-nav');
        
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                nav.classList.toggle('active');
                const isExpanded = nav.classList.contains('active');
                toggle.setAttribute('aria-expanded', isExpanded);
                toggle.textContent = isExpanded ? '✕ Close' : '☰ Menu';
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!toggle.contains(e.target) && !nav.contains(e.target)) {
                    nav.classList.remove('active');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.textContent = '☰ Menu';
                }
            });

            // Close menu on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && nav.classList.contains('active')) {
                    nav.classList.remove('active');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.textContent = '☰ Menu';
                    toggle.focus();
                }
            });
        }
    }

    /* ==========================================================================
       Smooth Scroll for Anchor Links
       ========================================================================== */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#!') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                        block: 'start'
                    });
                    // Set focus for accessibility
                    if (!target.hasAttribute('tabindex')) {
                        target.setAttribute('tabindex', '-1');
                    }
                    target.focus();
                }
            });
        });
    }

    /* ==========================================================================
       Header Scroll Behavior
       ========================================================================== */
    function initHeaderScroll() {
        const header = document.querySelector('.site-header');
        if (!header) return;

        let lastScroll = 0;
        let ticking = false;

        function updateHeader() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
            ticking = false;
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(updateHeader);
                ticking = true;
            }
        });
    }

    /* ==========================================================================
       Diamond Base Interactions
       ========================================================================== */
    function initDiamondBases() {
        const bases = document.querySelectorAll('.base');
        
        bases.forEach(base => {
            // Ensure proper ARIA labels
            if (!base.hasAttribute('aria-label')) {
                const title = base.querySelector('.base-title');
                if (title) {
                    base.setAttribute('aria-label', title.textContent);
                }
            }

            // Keyboard navigation between bases
            base.addEventListener('keydown', function(e) {
                const allBases = Array.from(document.querySelectorAll('.base'));
                const currentIndex = allBases.indexOf(this);
                let targetBase = null;

                switch(e.key) {
                    case 'ArrowRight':
                    case 'ArrowDown':
                        e.preventDefault();
                        targetBase = allBases[currentIndex + 1] || allBases[0];
                        break;
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        e.preventDefault();
                        targetBase = allBases[currentIndex - 1] || allBases[allBases.length - 1];
                        break;
                    case 'Home':
                        e.preventDefault();
                        targetBase = allBases[0];
                        break;
                    case 'End':
                        e.preventDefault();
                        targetBase = allBases[allBases.length - 1];
                        break;
                }

                if (targetBase) {
                    targetBase.focus();
                }
            });
        });
    }

    /* ==========================================================================
       Set Active Navigation Link
       ========================================================================== */
    function setActiveNavLink() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.main-nav a');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            // Match exact path or index.php for home
            if (currentPath.endsWith(href) || 
                (currentPath === '/' && href === '/index.php') ||
                (currentPath.endsWith('/') && href === '/index.php')) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            }
        });
    }

    /* ==========================================================================
       Empty State Interactions
       ========================================================================== */
    function initEmptyStates() {
        const emptyStates = document.querySelectorAll('.empty-state');
        
        emptyStates.forEach(state => {
            // Add role and aria-label for accessibility
            if (!state.hasAttribute('role')) {
                state.setAttribute('role', 'status');
            }
        });
    }

    /* ==========================================================================
       Card Hover Effects
       ========================================================================== */
    function initCardEffects() {
        const cards = document.querySelectorAll('.card, .outfield-card');
        
        if (prefersReducedMotion) {
            // Disable hover animations for reduced motion
            cards.forEach(card => {
                card.style.transition = 'none';
            });
        }
    }

    /* ==========================================================================
       Lazy Load Images (if implemented)
       ========================================================================== */
    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /* ==========================================================================
       Focus Visible Polyfill (for older browsers)
       ========================================================================== */
    function initFocusVisible() {
        // Add focus-visible class to body when using keyboard
        let hadKeyboardEvent = false;

        document.addEventListener('keydown', function() {
            hadKeyboardEvent = true;
        });

        document.addEventListener('mousedown', function() {
            hadKeyboardEvent = false;
        });

        document.addEventListener('focusin', function(e) {
            if (hadKeyboardEvent) {
                e.target.classList.add('focus-visible');
            }
        });

        document.addEventListener('focusout', function(e) {
            e.target.classList.remove('focus-visible');
        });
    }

    /* ==========================================================================
       Table Enhancements
       ========================================================================== */
    function initTableEnhancements() {
        const tables = document.querySelectorAll('table');
        
        tables.forEach(table => {
            // Add role for accessibility
            if (!table.hasAttribute('role')) {
                table.setAttribute('role', 'table');
            }

            // Make tables keyboard scrollable
            const wrapper = table.closest('.table-wrapper');
            if (wrapper && !wrapper.hasAttribute('tabindex')) {
                wrapper.setAttribute('tabindex', '0');
                wrapper.setAttribute('role', 'region');
                wrapper.setAttribute('aria-label', 'Scrollable data table');
            }
        });
    }

    /* ==========================================================================
       Form Enhancements
       ========================================================================== */
    function initFormEnhancements() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Add novalidate to use custom validation
            form.setAttribute('novalidate', 'novalidate');

            // Handle form submission
            form.addEventListener('submit', function(e) {
                const invalidFields = form.querySelectorAll(':invalid');
                if (invalidFields.length > 0) {
                    e.preventDefault();
                    invalidFields[0].focus();
                }
            });
        });
    }

    /* ==========================================================================
       Console Welcome Message
       ========================================================================== */
    function logWelcomeMessage() {
        if (console && console.log) {
            console.log('%c⚾ MLB Baseball Impact - Fenway Modern', 
                'font-size: 16px; font-weight: bold; color: #0e5135;');
            console.log('%cExploring the global transformation of Major League Baseball', 
                'font-size: 12px; color: #7c8794;');
            
            if (prefersReducedMotion) {
                console.log('%c✓ Animations disabled (respecting prefers-reduced-motion)', 
                    'font-size: 11px; color: #2e8751;');
            }
        }
    }

    /* ==========================================================================
       Initialize All Features
       ========================================================================== */
    function init() {
        initMobileMenu();
        initSmoothScroll();
        initHeaderScroll();
        initDiamondBases();
        setActiveNavLink();
        initEmptyStates();
        initCardEffects();
        initLazyLoad();
        initFocusVisible();
        initTableEnhancements();
        initFormEnhancements();
        logWelcomeMessage();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
