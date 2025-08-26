<div class="sidebar user-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo" style="display: flex; align-items: center; gap: 0.5rem;">
            <img src="../maharlika/logo.jpg" alt="Logo" style="height: 36px; width: 36px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <span style="font-weight: 700; font-size: 1.15rem; letter-spacing: 0.03em; color: #fff;">Maharlika Library</span>
        </div>
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
<link rel="stylesheet" href="../public/assets/css/users_main.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../public/assets/js/bootstrap.bundle.min.js"></script>
<script src="../public/assets/js/users_sidebar.js"></script>