<div class="sidebar" id="sidebar">
    <div class="logo">
        <h3><i class="fas fa-book-reader"></i> Library System</h3>
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

<button id="sidebarToggle" class="sidebar-toggle d-lg-none">
    <i class="fas fa-bars"></i>
</button>

<style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #34495e;
        --accent-color: #3498db;
        --text-light: #ecf0f1;
        --text-dark: #2c3e50;
        --transition: all 0.3s ease;
        --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .sidebar {
        height: 100vh;
        width: 250px;
        position: fixed;
        top: 0;
        left: 0;
        background-color: var(--primary-color);
        padding-top: 1rem;
        box-shadow: var(--shadow);
        transition: var(--transition);
        z-index: 1000;
    }

    .sidebar .logo {
        padding: 1.5rem 1rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar .logo h3 {
        color: var(--text-light);
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-links {
        padding: 0.5rem 0;
    }

    .sidebar a {
        padding: 0.875rem 1.5rem;
        text-decoration: none;
        font-size: 0.95rem;
        color: var(--text-light);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: var(--transition);
        border-left: 3px solid transparent;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background-color: rgba(255, 255, 255, 0.1);
        border-left-color: var(--accent-color);
    }

    .sidebar-toggle {
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1001;
        background-color: var(--primary-color);
        color: var(--text-light);
        border: none;
        padding: 0.625rem;
        border-radius: 0.375rem;
        cursor: pointer;
        box-shadow: var(--shadow);
        display: none;
    }

    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: block;
        }
    }
</style>

<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');

        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target) && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
</script>