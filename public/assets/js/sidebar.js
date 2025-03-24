function toggleSidebar() {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
    
 
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    
   
    if (window.innerWidth <= 768) {
        mainContent.style.marginLeft = sidebar.classList.contains('open') ? '0' : '0';
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

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const mainContent = document.querySelector('.main-content');
    
    if (window.innerWidth > 768) {
        mainContent.style.marginLeft = '250px';
    } else {
        mainContent.style.marginLeft = '0';
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
});