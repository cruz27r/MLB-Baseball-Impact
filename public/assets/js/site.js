/**
 * CS437 MLB Global Era - Site JavaScript
 * 
 * Progressive enhancement with accessibility support:
 * - Mobile menu toggle
 * - Tab switching
 * - Smooth scroll (respecting prefers-reduced-motion)
 * - Optional parallax effect (disabled if prefers-reduced-motion)
 */

(function() {
    'use strict';

    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Mobile menu toggle
    function initMobileMenu() {
        const toggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.main-nav');
        
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                nav.classList.toggle('active');
                const isExpanded = nav.classList.contains('active');
                toggle.setAttribute('aria-expanded', isExpanded);
            });
        }
    }

    // Smooth scroll for anchor links
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                        block: 'start'
                    });
                    // Set focus for accessibility
                    target.setAttribute('tabindex', '-1');
                    target.focus();
                }
            });
        });
    }

    // Tab switching with keyboard support
    function initTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        
        tabButtons.forEach((button, index) => {
            button.addEventListener('click', function() {
                activateTab(this);
            });
            
            // Keyboard navigation
            button.addEventListener('keydown', function(e) {
                const parent = this.parentElement.parentElement;
                const buttons = Array.from(parent.querySelectorAll('.tab-button'));
                const currentIndex = buttons.indexOf(this);
                
                let targetButton = null;
                
                if (e.key === 'ArrowRight') {
                    targetButton = buttons[currentIndex + 1] || buttons[0];
                } else if (e.key === 'ArrowLeft') {
                    targetButton = buttons[currentIndex - 1] || buttons[buttons.length - 1];
                } else if (e.key === 'Home') {
                    targetButton = buttons[0];
                } else if (e.key === 'End') {
                    targetButton = buttons[buttons.length - 1];
                }
                
                if (targetButton) {
                    e.preventDefault();
                    targetButton.focus();
                    activateTab(targetButton);
                }
            });
            
            // Set ARIA attributes
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-selected', button.classList.contains('active') ? 'true' : 'false');
        });
    }
    
    function activateTab(button) {
        const tabId = button.getAttribute('data-tab');
        const tabGroup = button.closest('.tabs').getAttribute('data-tab-group') || 'default';
        
        // Remove active class from all buttons in group
        document.querySelectorAll(`.tabs[data-tab-group="${tabGroup}"] .tab-button`).forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        
        // Remove active class from all contents in group
        document.querySelectorAll(`.tab-content[data-tab-group="${tabGroup}"]`).forEach(content => {
            content.classList.remove('active');
            content.setAttribute('aria-hidden', 'true');
        });
        
        // Add active class to clicked button
        button.classList.add('active');
        button.setAttribute('aria-selected', 'true');
        
        // Show corresponding content
        const content = document.querySelector(`.tab-content[data-tab="${tabId}"][data-tab-group="${tabGroup}"]`);
        if (content) {
            content.classList.add('active');
            content.setAttribute('aria-hidden', 'false');
        }
    }

    // Simple chart placeholder renderer
    function renderChartPlaceholders() {
        const placeholders = document.querySelectorAll('.chart-placeholder[data-values]');
        
        placeholders.forEach(placeholder => {
            try {
                const values = JSON.parse(placeholder.getAttribute('data-values'));
                const labels = placeholder.getAttribute('data-labels')?.split(',') || [];
                
                if (values && values.length > 0) {
                    placeholder.innerHTML = renderSimpleBarChart(values, labels);
                }
            } catch (e) {
                console.error('Error rendering chart:', e);
            }
        });
    }

    // Simple ASCII-style bar chart
    function renderSimpleBarChart(values, labels) {
        const max = Math.max(...values);
        let html = '<div style="font-family: monospace; line-height: 1.8;">';
        
        values.forEach((value, index) => {
            const width = (value / max) * 100;
            const label = labels[index] || `Item ${index + 1}`;
            html += `
                <div style="margin: 0.5rem 0;">
                    <div style="display: flex; align-items: center;">
                        <span style="min-width: 100px; text-align: right; margin-right: 10px;">${label}</span>
                        <div style="background: linear-gradient(135deg, #1a5e3b 0%, #12603f 100%); 
                                    width: ${width}%; height: 24px; border-radius: 4px;
                                    display: flex; align-items: center; justify-content: flex-end; padding-right: 8px;">
                            <span style="color: white; font-weight: bold;">${value}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    // Set active navigation link based on current page
    function setActiveNavLink() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.main-nav a');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (currentPath.endsWith(href) || (currentPath === '/' && href === '/index.php')) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            }
        });
    }

    // Toggle filter sections
    function initFilterToggles() {
        const filterToggles = document.querySelectorAll('.filter-toggle');
        
        filterToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                const filterSection = document.querySelector(target);
                
                if (filterSection) {
                    filterSection.classList.toggle('collapsed');
                    this.classList.toggle('active');
                    const isExpanded = !filterSection.classList.contains('collapsed');
                    this.setAttribute('aria-expanded', isExpanded);
                }
            });
        });
    }

    // Optional parallax effect (only if reduced motion is not preferred)
    function initParallax() {
        if (prefersReducedMotion) {
            return; // Skip parallax if user prefers reduced motion
        }

        const parallaxElements = document.querySelectorAll('.parallax-wall');
        if (parallaxElements.length === 0) return;

        let ticking = false;

        function updateParallax() {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const speed = element.getAttribute('data-speed') || 0.5;
                const yPos = -(scrolled * speed);
                element.style.transform = `translateY(${yPos}px) translateZ(0)`;
            });
            
            ticking = false;
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(updateParallax);
                ticking = true;
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initMobileMenu();
        initSmoothScroll();
        initTabs();
        renderChartPlaceholders();
        setActiveNavLink();
        initFilterToggles();
        initParallax();
        
        // Log accessibility support status
        if (prefersReducedMotion) {
            console.log('✓ Animations disabled (respecting prefers-reduced-motion)');
        }
    }

})();
