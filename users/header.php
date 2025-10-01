<header class="navbar navbar-expand-lg navbar-light fixed-top py-2 modern-header">
    <div class="container-fluid px-3">
        <!-- Mobile Menu Toggle -->
        <button type="button" class="navbar-toggler border-0 d-lg-none sidebar-toggle-btn" id="sidebarToggleBtn">
            <i class="fas fa-bars fa-lg text-white"></i>
        </button>

        <!-- Brand with enhanced logo -->
        <a class="navbar-brand d-lg-none modern-brand" href="index.php">
            <div class="brand-container">
                <i class="fas fa-graduation-cap brand-icon"></i>
                <span class="brand-text">Student Library</span>
            </div>
        </a>


        <!-- Mobile Navigation Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <div class="user-avatar">
                <i class="fas fa-user-circle fa-lg"></i>
            </div>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Quick Actions -->
                <li class="nav-item d-none d-lg-block me-3">
                    <div class="quick-actions">
                        <a href="index.php" class="quick-action-btn" title="Browse Books">
                            <i class="fas fa-book"></i>
                        </a>
                        <a href="reservations.php" class="quick-action-btn" title="My Reservations">
                            <i class="fas fa-bookmark"></i>
                        </a>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 user-dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <div class="user-avatar-container">
                            <i class="fas fa-user-circle fa-lg"></i>
                            <div class="user-status-dot"></div>
                        </div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user']) ?></span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow modern-dropdown">
                        <li>
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <i class="fas fa-user-graduate me-2"></i>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['user']) ?></div>
                                        <small class="text-muted">Student Account</small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="index.php">
                                <i class="fas fa-home me-2 text-primary"></i>
                                <span>Home</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="reservations.php">
                                <i class="fas fa-bookmark me-2 text-success"></i>
                                <span>My Reservations</span>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../public/assets/css/users_header.css">
<script src="../public/assets/js/users_header.js"></script>