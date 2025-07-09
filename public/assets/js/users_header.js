document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            if (e.target.closest('.sidebar-toggle-btn')) {
                e.preventDefault();
                e.stopPropagation();

                if (sidebar && overlay) {
                    sidebar.classList.toggle('show-sidebar');
                    overlay.classList.toggle('show-overlay');
                    document.body.style.overflow = sidebar.classList.contains('show-sidebar') ? 'hidden' : '';
                }
            }
        });
    }
}); 