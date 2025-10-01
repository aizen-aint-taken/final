document.addEventListener('DOMContentLoaded', () => {
    // ULTRA AGGRESSIVE Modal backdrop fix
    function ultraFixModalBackdrop() {
        // Target all possible backdrop selectors
        const backdrops = document.querySelectorAll('.modal-backdrop, [class*="modal-backdrop"], div[class*="backdrop"]');
        backdrops.forEach(backdrop => {
            backdrop.style.setProperty('background-color', 'rgba(0, 0, 0, 0.15)', 'important');
            backdrop.style.setProperty('background', 'rgba(0, 0, 0, 0.15)', 'important');
            backdrop.style.setProperty('opacity', '0.15', 'important');
            backdrop.style.setProperty('z-index', '1050', 'important');
            backdrop.style.setProperty('pointer-events', 'none', 'important');
        });
        
        // Force CSS variables
        document.documentElement.style.setProperty('--bs-modal-backdrop-bg', 'rgba(0, 0, 0, 0.15)', 'important');
        document.documentElement.style.setProperty('--bs-modal-backdrop-opacity', '0.15', 'important');
        document.body.style.setProperty('--bs-modal-backdrop-bg', 'rgba(0, 0, 0, 0.15)', 'important');
        document.body.style.setProperty('--bs-modal-backdrop-opacity', '0.15', 'important');
    }
    
    // Multiple observers for comprehensive coverage
    const backdropObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Check if node itself is a backdrop
                    if (node.classList && (node.classList.contains('modal-backdrop') || node.className.includes('backdrop'))) {
                        setTimeout(() => {
                            node.style.setProperty('background-color', 'rgba(0, 0, 0, 0.15)', 'important');
                            node.style.setProperty('background', 'rgba(0, 0, 0, 0.15)', 'important');
                            node.style.setProperty('opacity', '0.15', 'important');
                            node.style.setProperty('z-index', '1050', 'important');
                            node.style.setProperty('pointer-events', 'none', 'important');
                        }, 10);
                    }
                    // Check children for backdrops
                    if (node.querySelectorAll) {
                        const backdrops = node.querySelectorAll('.modal-backdrop, [class*="modal-backdrop"], div[class*="backdrop"]');
                        backdrops.forEach(backdrop => {
                            setTimeout(() => {
                                backdrop.style.setProperty('background-color', 'rgba(0, 0, 0, 0.15)', 'important');
                                backdrop.style.setProperty('background', 'rgba(0, 0, 0, 0.15)', 'important');
                                backdrop.style.setProperty('opacity', '0.15', 'important');
                                backdrop.style.setProperty('z-index', '1050', 'important');
                                backdrop.style.setProperty('pointer-events', 'none', 'important');
                            }, 10);
                        });
                    }
                }
            });
        });
        // Run fix after any DOM changes
        setTimeout(ultraFixModalBackdrop, 20);
    });
    
    backdropObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'style']
    });
    
    // Initial fix
    ultraFixModalBackdrop();
    
    // Continuous fixing with intervals
    setInterval(ultraFixModalBackdrop, 100);
    
    // Modal event listeners with aggressive backdrop fixing
    const modal = document.getElementById('modalId');
    if (modal) {
        modal.addEventListener('show.bs.modal', event => {
            try {
                const button = event.relatedTarget;
                const bookId = button.getAttribute('data-id');
                const bookTitle = button.getAttribute('data-title');
                const bookAuthor = button.getAttribute('data-author');

                // Set modal z-index to ensure it's on top
                modal.style.zIndex = '1055';
                
                // Populate modal fields
                const bookIdInput = document.getElementById('reserveBookId');
                const bookTitleInput = document.getElementById('reserveBookTitle');
                const bookAuthorInput = document.getElementById('reserveBookAuthor');
                
                if (bookIdInput) bookIdInput.value = bookId || '';
                if (bookTitleInput) bookTitleInput.value = bookTitle || '';
                if (bookAuthorInput) bookAuthorInput.value = bookAuthor || '';
                
                console.log('Modal opened with book:', { bookId, bookTitle, bookAuthor });
                
                // Ultra aggressive backdrop fix when modal is showing
                setTimeout(ultraFixModalBackdrop, 50);
                setTimeout(ultraFixModalBackdrop, 200);
                setTimeout(ultraFixModalBackdrop, 500);
            } catch (error) {
                console.error('Error handling modal show:', error);
            }
        });
        
        // Ensure modal is clickable when shown
        modal.addEventListener('shown.bs.modal', () => {
            modal.style.pointerEvents = 'auto';
            modal.querySelector('.modal-content').style.pointerEvents = 'auto';
            // Ultra aggressive backdrop fix after modal is fully shown
            setTimeout(ultraFixModalBackdrop, 50);
            setTimeout(ultraFixModalBackdrop, 200);
            setTimeout(ultraFixModalBackdrop, 500);
            setTimeout(ultraFixModalBackdrop, 1000);
        });
        
        // Fix backdrop on modal hide
        modal.addEventListener('hide.bs.modal', () => {
            setTimeout(ultraFixModalBackdrop, 50);
        });
    }
    
    // Handle logout modal with ultra aggressive fixes
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
        logoutModal.addEventListener('show.bs.modal', () => {
            logoutModal.style.zIndex = '1055';
            setTimeout(ultraFixModalBackdrop, 50);
            setTimeout(ultraFixModalBackdrop, 200);
            setTimeout(ultraFixModalBackdrop, 500);
        });
        
        logoutModal.addEventListener('shown.bs.modal', () => {
            logoutModal.style.pointerEvents = 'auto';
            logoutModal.querySelector('.modal-content').style.pointerEvents = 'auto';
            // Ultra aggressive backdrop fix after modal is fully shown
            setTimeout(ultraFixModalBackdrop, 50);
            setTimeout(ultraFixModalBackdrop, 200);
            setTimeout(ultraFixModalBackdrop, 500);
            setTimeout(ultraFixModalBackdrop, 1000);
        });
        
        logoutModal.addEventListener('hide.bs.modal', () => {
            setTimeout(ultraFixModalBackdrop, 50);
        });
    }

    // Enhanced search functionality
    const searchBar = document.getElementById('searchBar');
    const searchForm = searchBar.closest('form');

    // Handle form submission
    searchForm.addEventListener('submit', function(e) {
        if (!searchBar.value.trim()) {
            e.preventDefault();
            return;
        }
    });

    // Enhanced real-time search with animations
    searchBar.addEventListener('input', function() {
        const searchValue = this.value.toLowerCase().trim();
        const tableRows = document.querySelectorAll('#booksTable tbody tr');
        const mobileCards = document.querySelectorAll('.modern-book-card');
        let hasResults = false;

        // Add typing animation
        this.classList.add('typing');
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.classList.remove('typing'), 300);

        // Filter table rows with animation
        tableRows.forEach((row, index) => {
            const rowText = row.textContent.toLowerCase();
            if (rowText.includes(searchValue)) {
                setTimeout(() => {
                    row.style.display = '';
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        row.style.opacity = '1';
                        row.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 50);
                hasResults = true;
            } else {
                row.style.opacity = '0';
                row.style.transform = 'translateY(-10px)';
                setTimeout(() => row.style.display = 'none', 200);
            }
        });

        // Filter mobile cards with animation
        mobileCards.forEach((card, index) => {
            const cardText = card.textContent.toLowerCase();
            if (cardText.includes(searchValue) || searchValue === '') {
                setTimeout(() => {
                    card.parentElement.style.display = '';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9) translateY(20px)';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'scale(1) translateY(0)';
                    }, 100);
                }, index * 100);
                hasResults = true;
            } else {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => card.parentElement.style.display = 'none', 200);
            }
        });

        // Show/hide enhanced no results message
        const noResultsMsg = document.getElementById('noResultsMessage');
        if (noResultsMsg) {
            if (hasResults) {
                noResultsMsg.style.display = 'none';
            } else {
                setTimeout(() => {
                    noResultsMsg.style.display = 'block';
                    noResultsMsg.style.opacity = '0';
                    setTimeout(() => noResultsMsg.style.opacity = '1', 100);
                }, 300);
            }
        }
    });

    // Enhanced filter functionality
    const booksFilter = document.getElementById('booksFilter');
    if (booksFilter) {
        booksFilter.addEventListener('change', function() {
            this.classList.add('changed');
            // Add ripple effect
            const ripple = document.createElement('div');
            ripple.className = 'filter-ripple';
            this.parentNode.appendChild(ripple);
            
            setTimeout(() => {
                this.classList.remove('changed');
                ripple.remove();
            }, 600);
        });
    }

    // Button hover effects
    document.querySelectorAll('.reserve-btn, .reserve-btn-mobile').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Enhanced card animations on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Observe all cards
    document.querySelectorAll('.modern-book-card, .modern-card').forEach(card => {
        observer.observe(card);
    });

    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        .typing {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef) !important;
        }
        
        .filter-ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .animate-in {
            animation: slideInUp 0.6s ease forwards;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

    // Welcome banner animation
    const welcomeBanner = document.querySelector('.welcome-banner');
    if (welcomeBanner) {
        welcomeBanner.style.opacity = '0';
        welcomeBanner.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            welcomeBanner.style.transition = 'all 0.8s ease';
            welcomeBanner.style.opacity = '1';
            welcomeBanner.style.transform = 'translateY(0)';
        }, 100);
    }
});