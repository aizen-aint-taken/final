// Enhanced header functionality
document.addEventListener('DOMContentLoaded', function() {
    // Smooth dropdown animations
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        
        dropdown.addEventListener('show.bs.dropdown', function() {
            dropdownMenu.style.transform = 'translateY(-10px) scale(0.95)';
            dropdownMenu.style.opacity = '0';
            
            setTimeout(() => {
                dropdownMenu.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                dropdownMenu.style.transform = 'translateY(0) scale(1)';
                dropdownMenu.style.opacity = '1';
            }, 10);
        });
        
        dropdown.addEventListener('hide.bs.dropdown', function() {
            dropdownMenu.style.transform = 'translateY(-10px) scale(0.95)';
            dropdownMenu.style.opacity = '0';
        });
    });
    
    // Header scroll effect
    let lastScrollTop = 0;
    const header = document.querySelector('.modern-header');
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down
            header.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            header.style.transform = 'translateY(0)';
        }
        
        // Add/remove shadow based on scroll position
        if (scrollTop > 10) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    });
    
    // Add CSS for header scroll effects
    const style = document.createElement('style');
    style.textContent = `
        .modern-header {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .modern-header.scrolled {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }
    `;
    document.head.appendChild(style);
});