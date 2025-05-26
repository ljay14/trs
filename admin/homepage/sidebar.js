// Simpler sidebar management to ensure consistent behavior
document.addEventListener('DOMContentLoaded', function() {
    // Ensure sidebar is sticky across all pages
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.style.position = 'sticky';
        sidebar.style.top = '52px'; // Updated to account for top bar height
        sidebar.style.height = 'calc(100vh - 52px)'; // Adjusted height
        sidebar.style.overflowY = 'auto';
        sidebar.style.zIndex = '90'; // Add z-index for proper stacking
    }
    
    const menuHeaders = document.querySelectorAll('.menu-header');
    const path = window.location.pathname;
    
    // Log the actual path for debugging
    console.log("Current path:", path);
    
    // Helper function to save dropdown state
    function saveDropdownState(isOpen) {
        localStorage.setItem('sidebarDropdownState', isOpen ? 'open' : 'closed');
    }

    // Helper function to get dropdown state
    function getDropdownState() {
        return localStorage.getItem('sidebarDropdownState') === 'open';
    }
    
    // Helper function to close all dropdowns
    function closeAllDropdowns() {
        menuHeaders.forEach(header => {
            const dropdownContent = header.nextElementSibling;
            const icon = header.querySelector('.dropdown-icon');
            icon.classList.remove('expanded');
            dropdownContent.classList.remove('show');
        });
        saveDropdownState(false);
    }
    
    // Helper function to open a specific section
    function openSection(sectionName) {
        console.log("Opening section:", sectionName);
        menuHeaders.forEach(header => {
            const label = header.querySelector('span').textContent.trim().toLowerCase();
            console.log("Checking label:", label, "against:", sectionName);
            if (label === sectionName) {
                const dropdownContent = header.nextElementSibling;
                const icon = header.querySelector('.dropdown-icon');
                icon.classList.add('expanded');
                dropdownContent.classList.add('show');
                saveDropdownState(true);
            }
        });
    }
    
    // Helper function to set active section based on URL
    function setActiveSection(path) {
        // Check if we're in any of the account-related pages
        if (path.includes('/registeredaccount/') || 
            path.includes('panel.php') || 
            path.includes('adviser.php')) {
            
            // If the dropdown was previously open or we're on an accounts page, open it
            if (getDropdownState() || path.includes('panel.php') || path.includes('adviser.php')) {
                openSection('accounts');
            }
            
            // Highlight the appropriate submenu
            if (path.includes('panel_register.php') || path.includes('panel.php')) {
                const panelLink = document.querySelector('a[href*="panel_register.php"]');
                if (panelLink) panelLink.classList.add('active');
            } else if (path.includes('adviser_register.php') || path.includes('adviser.php')) {
                const adviserLink = document.querySelector('a[href*="adviser_register.php"]');
                if (adviserLink) adviserLink.classList.add('active');
            } else if (path.includes('student_register.php')) {
                const studentLink = document.querySelector('a[href*="student_register.php"]');
                if (studentLink) studentLink.classList.add('active');
            }
        }
        // Other sections
        else if (path.includes('/titleproposal/')) {
            openSection('title proposal');
        } else if (path.includes('/final/')) {
            openSection('final');
        } else if (path.includes('/departmentcourse/')) {
            openSection('department course');
        }
    }
    
    // Helper function to highlight active submenu
    function highlightActiveSubmenu(path) {
        const submenuItems = document.querySelectorAll('.submenu-item');
        submenuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && (path.includes(href) || 
                        (path.includes('panel.php') && href.includes('panel_register.php')) ||
                        (path.includes('adviser.php') && href.includes('adviser_register.php')))) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }
    
    // First step: Close all dropdowns by default only if there's no saved state
    if (!getDropdownState()) {
        closeAllDropdowns();
    }
    
    // Second step: Open the active section based on URL
    setActiveSection(path);
    
    // Add click handlers for all menu headers
    menuHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            // Prevent the click from affecting parent elements
            e.stopPropagation();
            
            const dropdownContent = this.nextElementSibling;
            const icon = this.querySelector('.dropdown-icon');
            
            // Toggle the clicked dropdown
            const wasOpen = dropdownContent.classList.contains('show');
            
            // Close all other dropdowns (but not this one yet)
            menuHeaders.forEach(h => {
                if (h !== this) {
                    const otherIcon = h.querySelector('.dropdown-icon');
                    const otherContent = h.nextElementSibling;
                    otherIcon.classList.remove('expanded');
                    otherContent.classList.remove('show');
                }
            });
            
            // Now toggle this dropdown
            if (wasOpen) {
                icon.classList.remove('expanded');
                dropdownContent.classList.remove('show');
                saveDropdownState(false);
            } else {
                icon.classList.add('expanded');
                dropdownContent.classList.add('show');
                saveDropdownState(true);
            }
        });
    });
    
    // Prevent submenu items from closing the dropdown when clicked
    const submenuItems = document.querySelectorAll('.submenu-item');
    submenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't let the click bubble up to the parent elements
            e.stopPropagation();
            saveDropdownState(true); // Keep dropdown open when clicking submenu items
        });
    });
    
    // Highlight active submenu item
    highlightActiveSubmenu(path);
});