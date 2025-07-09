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



        <a href="logout.php" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<link rel="stylesheet" href="../public/assets/css/users_sidebar.css">
<script src="../public/assets/js/users_sidebar.js"></script>