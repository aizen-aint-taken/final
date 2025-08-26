document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const toggleBtn = document.getElementById('sidebarToggleBtn');

    // Open sidebar from header button
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (sidebar && overlay) {
                sidebar.classList.toggle('show-sidebar');
                overlay.classList.toggle('show-overlay');
                document.body.style.overflow = sidebar.classList.contains('show-sidebar') ? 'hidden' : '';
            }
        });
    }

    // Close button click event
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.remove('show-sidebar');
            overlay.classList.remove('show-overlay');
            document.body.style.overflow = '';
        });
    }

    // Overlay click event
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.remove('show-sidebar');
            overlay.classList.remove('show-overlay');
            document.body.style.overflow = '';
        });
    }

    // Close sidebar when clicking links (mobile only)
    const links = document.querySelectorAll('.nav-links a');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            if (window.innerWidth < 992) {
                e.stopPropagation();
                sidebar.classList.remove('show-sidebar');
                overlay.classList.remove('show-overlay');
                document.body.style.overflow = '';
            }
        });
    });
}); 