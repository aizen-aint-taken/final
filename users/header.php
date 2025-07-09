<header class="navbar navbar-expand-lg navbar-light fixed-top py-2">
    <div class="container-fluid px-3">
        <!-- Mobile Menu Toggle -->
        <button type="button" class="navbar-toggler border-0 d-lg-none sidebar-toggle-btn" id="sidebarToggleBtn">
            <i class="fas fa-bars fa-lg text-white"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand d-lg-none" href="index.php">
            <i class="fas fa-book-reader me-2"></i>Library
        </a>

        <!-- Mobile Navigation Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="fas fa-user-circle fa-lg"></i>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg"></i>
                        <span><?= htmlspecialchars($_SESSION['user']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">

                        <li>
                            <hr class="dropdown-divider">
                        <li>
                            <a class="dropdown-item py-2" href="reservations.php">
                                <i class="fas fa-user me-2"></i> Borrowed Books
                            </a>
                        </li>

                        <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item py-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
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