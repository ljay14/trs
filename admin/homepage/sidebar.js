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
    
    // Helper function to close all dropdowns
    function closeAllDropdowns() {
        menuHeaders.forEach(header => {
            const dropdownContent = header.nextElementSibling;
            const icon = header.querySelector('.dropdown-icon');
            icon.classList.remove('expanded');
            dropdownContent.classList.remove('show');
        });
    }
    
    // Helper function to open a specific section
    function openSection(sectionName) {
        console.log("Opening section:", sectionName);
        menuHeaders.forEach(header => {
            const label = header.querySelector('span').textContent.trim().toLowerCase();
            console.log("Checking label:", label, "against:", sectionName);
            if (label === sectionName || (sectionName === 'title proposal' && label.includes('title proposal'))) {
                const dropdownContent = header.nextElementSibling;
                const icon = header.querySelector('.dropdown-icon');
                icon.classList.add('expanded');
                dropdownContent.classList.add('show');
            }
        });
    }
    
    // Helper function to set active section based on URL
    function setActiveSection(path) {
        if (path.includes('/titleproposal/')) {
            openSection('title proposal');
            openSection('research proposal');
        } else if (path.includes('/final/')) {
            openSection('final');
            openSection('final defense');
        } else if (path.includes('/departmentcourse/')) {
            openSection('department course');
        } else if (path.includes('/registeredAccount/') || 
                   path.includes('/panel_register.php') || 
                   path.includes('/adviser_register.php') || 
                   path.includes('/student_register.php')) {
            openSection('accounts');
        }
    }
    
    // Helper function to highlight active submenu
    function highlightActiveSubmenu(path) {
        const submenuItems = document.querySelectorAll('.submenu-item');
        submenuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && path.includes(href)) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }
    
    // First step: Close all dropdowns by default
    closeAllDropdowns();
    
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
            } else {
                icon.classList.add('expanded');
                dropdownContent.classList.add('show');
            }
        });
    });
    
    // Prevent submenu items from closing the dropdown when clicked
    const submenuItems = document.querySelectorAll('.submenu-item');
    submenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't let the click bubble up to the parent elements
            e.stopPropagation();
        });
    });
    
    // Highlight active submenu item
    highlightActiveSubmenu(path);
});