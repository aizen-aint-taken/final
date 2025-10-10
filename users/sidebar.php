<div class="sidebar user-sidebar modern-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo" style="display: flex; align-items: center; gap: 0.75rem;">
            <div class="logo-container">
                <img src="../maharlika/logo.jpg" alt="Logo" class="logo-image">
                <div class="logo-badge">
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>
            <div class="brand-info text-center">
                <span class="brand-name">Maharlika Library</span>
                <small class="brand-subtitle">Student Portal</small>
            </div>
        </div>
        <button type="button" class="close-btn d-lg-none" id="sidebarCloseBtn">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- User Info Section -->
    <div class="user-info-section">
        <div class="user-avatar-large">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-details">
            <h6 class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></h6>
            <small class="user-role">Student</small>
        </div>
    </div>

    <div class="nav-links">
        <div class="nav-section">
            <small class="nav-section-title">MAIN MENU</small>
            <a href="index.php" class="nav-link-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-home"></i>
                </div>
                <span class="nav-text">Browse Books</span>
                <div class="nav-indicator"></div>
            </a>
            <a href="reservations.php" class="nav-link-item <?= basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-bookmark"></i>
                </div>
                <span class="nav-text">My Books Lists</span>
                <div class="nav-badge" id="reservationCount"></div>
                <div class="nav-indicator"></div>
            </a>
        </div>

        <div class="nav-section">
            <small class="nav-section-title">ACCOUNT</small>
            <a href="logout.php" class="nav-link-item logout-link" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <div class="nav-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <span class="nav-text">Logout</span>
                <div class="nav-indicator"></div>
            </a>
        </div>
    </div>


</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<link rel="stylesheet" href="../public/assets/css/users_main.css">
<link rel="stylesheet" href="../public/assets/css/users_sidebar.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/assets/js/users_sidebar.js"></script>
<script src="../public/assets/js/reservation-counter.js"></script>