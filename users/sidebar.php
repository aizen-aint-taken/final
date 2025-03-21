<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-book-reader me-2"></i> Library System</h3>
        <button type="button" class="close-btn d-lg-none" id="sidebarCloseBtn">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="nav-links">
        <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="reservations.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : '' ?>">
            <i class="fas fa-book"></i> Reservations
        </a>
        <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #34495e;
        --accent-color: #3498db;
        --text-light: #ecf0f1;
        --text-dark: #2c3e50;
        --transition: all 0.3s ease;
        --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        --sidebar-width: 250px;
        --header-height: 60px;
    }

    .sidebar {
        height: 100vh;
        width: 250px;
        position: fixed;
        top: 0;
        left: -250px;
        background: linear-gradient(135deg, rgb(184, 172, 175), rgb(10, 11, 11));
        color: white;
        z-index: 1041;
        transition: transform 0.3s ease;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .sidebar.show-sidebar {
        transform: translateX(250px);
    }

    .sidebar-header {
        height: 60px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0, 0, 0, 0.1);
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
        display: none;
    }

    .sidebar-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .nav-links {
        padding: 1rem 0;
    }

    .nav-links a {
        display: flex;
        align-items: center;
        padding: 0.875rem 1.5rem;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .nav-links a i {
        margin-right: 1rem;
        width: 20px;
        text-align: center;
    }

    .nav-links a:hover,
    .nav-links a.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.show-overlay {
        display: block;
        opacity: 1;
    }

    @media (max-width: 991.98px) {
        .sidebar {
            top: var(--header-height);
            height: calc(100vh - var(--header-height));
        }

        .close-btn {
            display: block;
        }
    }

    @media (min-width: 992px) {
        .sidebar {
            left: 0;
            transform: none;
        }

        .content-wrapper {
            margin-left: 250px;
        }
    }

    body {
        padding-top: 60px;
        overflow-x: hidden;
    }

    .content-wrapper {
        transition: margin-left 0.3s;
        padding: 1rem;
    }

    @media (min-width: 992px) {
        .content-wrapper {
            margin-left: 250px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('sidebarCloseBtn');

        // Close button click event
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('show-sidebar');
                overlay.classList.remove('show-overlay');
                document.body.style.overflow = '';
            });
        }

        // Overlay click event
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show-sidebar');
                overlay.classList.remove('show-overlay');
                document.body.style.overflow = '';
            });
        }

        // Close sidebar when clicking links (mobile only)
        const links = document.querySelectorAll('.nav-links a');
        links.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show-sidebar');
                    overlay.classList.remove('show-overlay');
                    document.body.style.overflow = '';
                }
            });
        });
    });
</script>