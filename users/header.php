<header class="navbar navbar-expand-md navbar-light bg-light fixed-top py-3 px-4 shadow-sm" style="width: 100%;">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">Home</a>
                </li>
                <li class="nav-item">
                    <a href="about.php" class="nav-link">About</a>
                </li>
                <li class="nav-item">
                    <a href="contact.php" class="nav-link">Contact</a>
                </li>
                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
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

<!-- Custom CSS for Enhancements -->
<style>
    .navbar {
        transition: all 0.3s ease-in-out;
        position: relative;
    }

    .navbar-nav .nav-item .nav-link {
        font-size: 1.1rem;
        padding: 0.5rem 1rem;
        color: #333;
        transition: color 0.3s ease-in-out;
    }

    .navbar-nav .nav-item .nav-link:hover {
        color: #007bff;
    }

    .navbar .dropdown-menu {
        border-radius: 8px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar .dropdown-menu .dropdown-item {
        padding: 0.75rem 1.25rem;
        font-size: 1rem;
        color: #555;
    }

    .navbar .dropdown-menu .dropdown-item:hover {
        background-color: #f1f1f1;
        color: #007bff;
    }

    .navbar-toggler-icon {
        background-color: #007bff;
    }

    .modal-content {
        border-radius: 10px;
    }

    .modal-header .btn-close {
        background-color: transparent;
        border: none;
    }

    .modal-footer .btn {
        border-radius: 5px;
        font-size: 1rem;
    }
</style>