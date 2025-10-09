/**
 * CS437 MLB Global Era - Main JavaScript
 * 
 * Interactive functionality for the MLB Baseball Impact project website.
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('MLB Global Era website initialized');
        
        initializeNavigation();
        initializeFilters();
        initializeDataTables();
    });

    /**
     * Initialize navigation functionality
     */
    function initializeNavigation() {
        // Highlight active page in navigation
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const navLinks = document.querySelectorAll('.main-nav a, .footer-nav a');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href').includes(currentPage)) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Initialize filter form handling
     */
    function initializeFilters() {
        const filterForm = document.getElementById('filter-form');
        
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                
                console.log('Applying filters:', Object.fromEntries(params));
                
                // Placeholder for AJAX call to fetch filtered data
                fetchFilteredData(params);
            });
        }
    }

    /**
     * Fetch filtered data from API
     * 
     * @param {URLSearchParams} params - Filter parameters
     */
    function fetchFilteredData(params) {
        // Placeholder for API call
        console.log('Fetching data with params:', params.toString());
        
        // Example API endpoint structure:
        // fetch(`/api/leaders_index.php?${params.toString()}`)
        //     .then(response => response.json())
        //     .then(data => updateTable(data))
        //     .catch(error => console.error('Error fetching data:', error));
        
        // For now, show placeholder message
        const tableContainer = document.getElementById('table-container');
        if (tableContainer) {
            showLoadingMessage(tableContainer);
        }
    }

    /**
     * Initialize data table functionality
     */
    function initializeDataTables() {
        const tables = document.querySelectorAll('.data-table');
        
        tables.forEach(table => {
            // Add sorting functionality to table headers
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    sortTable(table, index);
                });
            });
        });
    }

    /**
     * Sort table by column
     * 
     * @param {HTMLTableElement} table - Table element
     * @param {number} columnIndex - Column to sort by
     */
    function sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Toggle sort direction
        const currentDirection = table.dataset.sortDirection || 'asc';
        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
        table.dataset.sortDirection = newDirection;
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            // Try numeric comparison first
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
            }
            
            // Fall back to string comparison
            return newDirection === 'asc' 
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    /**
     * Show loading message in container
     * 
     * @param {HTMLElement} container - Container element
     */
    function showLoadingMessage(container) {
        container.innerHTML = '<p class="loading">Loading data... (API endpoint not yet implemented)</p>';
    }

    /**
     * Update table with new data
     * 
     * @param {Array} data - Array of data objects
     */
    function updateTable(data) {
        // Placeholder for table update logic
        console.log('Updating table with data:', data);
    }

    // Export functions for external use if needed
    window.MLBGlobalEra = {
        fetchFilteredData,
        sortTable,
        updateTable
    };

})();
