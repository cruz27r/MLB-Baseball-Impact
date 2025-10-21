/**
 * CS437 MLB Global Era - Site JavaScript
 * 
 * Minimal interactivity: smooth scroll, mobile menu toggle, and chart placeholders.
 */

(function() {
    'use strict';

    // Mobile menu toggle
    function initMobileMenu() {
        const toggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.main-nav');
        
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                nav.classList.toggle('active');
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
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Tab switching
    function initTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                const tabGroup = this.closest('.tabs').getAttribute('data-tab-group') || 'default';
                
                // Remove active class from all buttons in group
                document.querySelectorAll(`.tabs[data-tab-group="${tabGroup}"] .tab-button`).forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Remove active class from all contents in group
                document.querySelectorAll(`.tab-content[data-tab-group="${tabGroup}"]`).forEach(content => {
                    content.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Show corresponding content
                const content = document.querySelector(`.tab-content[data-tab="${tabId}"][data-tab-group="${tabGroup}"]`);
                if (content) {
                    content.classList.add('active');
                }
            });
        });
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
                        <div style="background: linear-gradient(135deg, #0e5135 0%, #12603f 100%); 
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
                }
            });
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
    }

})();
