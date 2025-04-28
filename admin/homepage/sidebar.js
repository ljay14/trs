document.addEventListener('DOMContentLoaded', function() {
    const menuHeaders = document.querySelectorAll('.menu-header');
    const path = window.location.pathname;

    menuHeaders.forEach(header => {
        const dropdownContent = header.nextElementSibling;
        const label = header.querySelector('span').textContent.trim().toLowerCase();

        // Default: close all
        header.querySelector('.dropdown-icon').classList.remove('expanded');
        dropdownContent.classList.remove('show');

        // Expand the right one based on URL
        if (path.includes('/titleproposal/') && label.includes('title proposal')) {
            header.querySelector('.dropdown-icon').classList.add('expanded');
            dropdownContent.classList.add('show');
        } else if (path.includes('/final/') && label === 'final') {
            header.querySelector('.dropdown-icon').classList.add('expanded');
            dropdownContent.classList.add('show');
        } else if (path.includes('/registeraccount/') && label === 'register account') {
            header.querySelector('.dropdown-icon').classList.add('expanded');
            dropdownContent.classList.add('show');
        } else if (path.includes('/registeredaccount/') && label === 'registered account') {
            header.querySelector('.dropdown-icon').classList.add('expanded');
            dropdownContent.classList.add('show');
        } else if (path.includes('/departmentcourse/') && label === 'department course') {
            header.querySelector('.dropdown-icon').classList.add('expanded');
            dropdownContent.classList.add('show');
        }

        // Accordion behavior
        header.addEventListener('click', function() {
            // Toggle the clicked one
            const icon = this.querySelector('.dropdown-icon');
            icon.classList.toggle('expanded');
            dropdownContent.classList.toggle('show');

            // Optional: Close others (accordion behavior)
            menuHeaders.forEach(h => {
                if (h !== this) {
                    const otherIcon = h.querySelector('.dropdown-icon');
                    const otherContent = h.nextElementSibling;
                    otherIcon.classList.remove('expanded');
                    otherContent.classList.remove('show');
                }
            });
        });
    });

    // Highlight active submenu item
    const submenuItems = document.querySelectorAll('.submenu-item');
    submenuItems.forEach(item => {
        if (path.includes(item.getAttribute('href'))) {
            item.classList.add('active');
        }
    });
});
