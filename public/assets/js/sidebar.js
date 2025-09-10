function toggleSidebar() {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const contentWrapper = document.querySelector('.content-wrapper');
    
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
    
    // Prevent body scrolling when sidebar is open on mobile
    if (window.innerWidth <= 768) {
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    }
    
    // Adjust content wrapper margin for desktop
    if (window.innerWidth > 768 && contentWrapper) {
        if (sidebar.classList.contains('open')) {
            contentWrapper.style.marginLeft = '0';
        } else {
            contentWrapper.style.marginLeft = '250px';
        }
    }
}


document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickOnMenuToggle = event.target.closest('.menu-toggle');
    const isClickOnLink = event.target.closest('.nav-link');

    
    if (isClickOnLink) {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
        return;
    }

    if (!isClickInsideSidebar && !isClickOnMenuToggle && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
});

window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const contentWrapper = document.querySelector('.content-wrapper');
    
    if (contentWrapper) {
        if (window.innerWidth > 768) {
            contentWrapper.style.marginLeft = '250px';
            // Reset mobile-specific styles on desktop
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        } else {
            contentWrapper.style.marginLeft = '0';
        }
    }
});