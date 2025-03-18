<header class="navbar navbar-expand-lg navbar-light fixed-top py-2">
    <div class="container-fluid px-3">
        <!-- Mobile Menu Toggle -->
        <button type="button" class="navbar-toggler border-0 d-lg-none" id="sidebarToggleBtn">
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
                        <!-- <li>
                            <a class="dropdown-item py-2" href="profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </li> -->
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

<style>
    .navbar {
        background: linear-gradient(135deg, rgb(190, 117, 147), #0d47a1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        min-height: var(--header-height);
    }

    .navbar-brand {
        font-weight: 600;
        font-size: 1.25rem;
    }

    .nav-link {
        padding: 0.5rem 1rem !important;
        color: rgba(255, 255, 255, 0.9) !important;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.1);
    }

    .dropdown-menu {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        padding: 0.5rem;
    }

    .dropdown-item {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }

    #mobileSidebarToggle {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    #mobileSidebarToggle:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    @media (max-width: 991.98px) {
        .navbar {
            padding: 0.5rem;
        }

        .navbar-brand {
            font-size: 1.1rem;
        }

        .dropdown-menu {
            border-radius: 8px;
            margin-top: 0.5rem;
        }
    }

    #sidebarToggleBtn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    #sidebarToggleBtn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }

    #sidebarToggleBtn:active {
        transform: scale(0.95);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Toggle button clicked');

                // Directly toggle the classes
                if (sidebar && overlay) {
                    sidebar.classList.toggle('show-sidebar');
                    overlay.classList.toggle('show-overlay');
                    document.body.style.overflow = sidebar.classList.contains('show-sidebar') ? 'hidden' : '';
                }
            });
        }
    });
</script>