<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Maharlika Library</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Clean Navbar Design */
    .main-header.navbar {
      background: #ffffff !important;
      border-bottom: 1px solid #dee2e6;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .main-header.navbar .nav-link {
      color: #495057 !important;
      font-weight: 500;
      transition: all 0.3s ease;
      border-radius: 8px;
      margin: 0 2px;
      position: relative;
      overflow: hidden;
    }

    .main-header.navbar .nav-link:hover {
      color: #007bff !important;
      background: rgba(0, 123, 255, 0.1);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .main-header.navbar .nav-link:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(0, 123, 255, 0.1), transparent);
      transition: left 0.5s;
    }

    .main-header.navbar .nav-link:hover:before {
      left: 100%;
    }

    /* Animated hamburger menu */
    .main-header.navbar .fas.fa-bars {
      transition: transform 0.3s ease;
    }

    .main-header.navbar .fas.fa-bars:hover {
      transform: scale(1.2) rotate(90deg);
    }

    /* User Dropdown Enhancement */
    .nav-item.dropdown {
      position: relative;
    }

    .dropdown-menu {
      background: #ffffff;
      border: 1px solid #dee2e6;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      overflow: hidden;
      position: absolute;
      top: 100%;
      right: 0;
      z-index: 1050;
      display: none;
      min-width: 250px;
      margin: 0;
    }

    .dropdown-menu.show {
      display: block !important;
    }

    .dropdown-toggle::after {
      margin-left: 0.5rem;
    }

    .dropdown-item {
      transition: all 0.3s ease;
      border-radius: 8px;
      margin: 2px 8px;
    }

    .dropdown-item:hover {
      background: rgba(0, 123, 255, 0.1);
      transform: translateX(5px);
    }

    .dropdown-header {
      background: #007bff;
      color: white !important;
      font-weight: 600;
      border-radius: 8px;
      margin: 5px;
    }

    @media (max-width: 500px) {
      #notification-dropdown {
        width: 95vw;
        left: 2.5vw;
        transform: none;
      }
    }

    /* Responsive navbar adjustments */
    @media (max-width: 768px) {
      .navbar-nav .nav-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.9rem;
      }

      .navbar-nav .nav-link i {
        margin-right: 0.25rem;
      }
    }

    @media (max-width: 576px) {
      .navbar-nav .nav-link {
        padding: 0.2rem 0.4rem;
        font-size: 0.85rem;
      }

      .d-none.d-md-inline {
        display: none !important;
      }
    }

    /* AdminLTE Sidebar Toggle Support */
    .sidebar-collapse .main-sidebar {
      margin-left: -250px;
    }

    .main-sidebar {
      transition: margin-left 0.3s ease-in-out;
      position: fixed;
      top: 56px !important;
      /* Start below navbar */
      left: 0;
      z-index: 1037;
      height: calc(100vh - 56px) !important;
      /* Adjust height for navbar */
      width: 250px;
    }

    .content-wrapper {
      transition: margin-left 0.3s ease-in-out;
      margin-left: 250px;
      margin-top: 56px;
      min-height: calc(100vh - 56px);
    }

    .sidebar-collapse .content-wrapper {
      margin-left: 0;
    }

    /* Ensure navbar extends properly and is above sidebar */
    .main-header.navbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1040 !important;
      /* Higher than sidebar z-index */
      margin-left: 0 !important;
      width: 100% !important;
    }

    /* Hamburger Menu Icon Styling - Force Visibility and Proper Positioning */
    .main-header.navbar .navbar-nav {
      position: relative;
      z-index: 1050 !important;
      /* Ensure nav items are above everything */
    }

    .main-header.navbar .nav-link[data-widget="pushmenu"] {
      display: flex !important;
      align-items: center;
      justify-content: center;
      padding: 8px 12px !important;
      color: #495057 !important;
      background: transparent !important;
      border: none !important;
      min-width: 40px;
      min-height: 40px;
      visibility: visible !important;
      opacity: 1 !important;
      position: relative !important;
      z-index: 1051 !important;
      /* Highest z-index to ensure visibility */
      margin-left: 10px !important;
      /* Add some margin from left edge */
      cursor: pointer !important;
    }

    .main-header.navbar .nav-link[data-widget="pushmenu"]:hover {
      background: rgba(0, 123, 255, 0.1) !important;
      color: #007bff !important;
      border-radius: 8px;
    }

    .main-header.navbar .nav-link[data-widget="pushmenu"] .fas.fa-bars {
      font-size: 18px !important;
      color: inherit !important;
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
      line-height: 1 !important;
      font-weight: 900 !important;
    }

    /* Ensure navbar-nav items are visible and properly positioned */
    .main-header.navbar .navbar-nav {
      display: flex !important;
      align-items: center;
      position: relative;
      z-index: 1050 !important;
    }

    .main-header.navbar .navbar-nav .nav-item {
      display: flex !important;
      position: relative;
      z-index: 1050 !important;
    }

    .main-header.navbar .navbar-nav .nav-item:first-child {
      margin-left: 0 !important;
      /* Ensure first item (hamburger) is at the edge */
    }

    @media (max-width: 991.98px) {
      .main-sidebar {
        margin-left: -250px;
      }

      .sidebar-open .main-sidebar {
        margin-left: 0;
      }

      .content-wrapper {
        margin-left: 0;
      }

      /* Ensure hamburger is even more prominent on mobile */
      .main-header.navbar .nav-link[data-widget="pushmenu"] {
        padding: 10px 15px !important;
        background: rgba(0, 123, 255, 0.05) !important;
        border-radius: 5px;
      }

      .main-header.navbar .nav-link[data-widget="pushmenu"] .fas.fa-bars {
        font-size: 20px !important;
      }
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <!-- Main Header Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Top Navigation Links -->
        <li class="nav-item">
          <a href="../admin/index.php" class="nav-link">
            <i class="fas fa-home mr-1"></i> Home
          </a>
        </li>
        <li class="nav-item">
          <a href="../aboutUs/index.php" class="nav-link">
            <i class="fas fa-info-circle mr-1"></i> About
          </a>
        </li>
        <li class="nav-item">
          <a href="../admin/reservations.php" class="nav-link">
            <i class="fas fa-calendar-check mr-1"></i> Borrowed Books
          </a>
        </li>

        <!-- 🔔 Notifications - MOVED TO SIDEBAR -->

        <!-- User Menu -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="far fa-user"></i>
            <span class="d-none d-md-inline ml-1">
              <?= isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'Admin Account' ?>
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-item dropdown-header">
              <strong>Logged in as:</strong><br>
              <?= isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'Admin Account' ?>
            </span>
            <div class="dropdown-divider"></div>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item text-danger" data-toggle="modal" data-target="#logoutModal">
              <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </nav>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Are you sure you want to log out?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <a href="../logout.php" class="btn btn-danger">Logout</a>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Single initialization function for AdminLTE and sidebar
      document.addEventListener('DOMContentLoaded', function() {
        // Initialize sidebar toggle functionality immediately
        initializeSidebarToggle();

        // Initialize AdminLTE if available
        if (typeof AdminLTE !== 'undefined') {
          AdminLTE.init();
        }
      });

      function initializeSidebarToggle() {
        const toggleButton = document.querySelector('[data-widget="pushmenu"]');
        if (toggleButton) {
          // Remove any existing event listeners to prevent conflicts
          toggleButton.removeEventListener('click', handleSidebarToggle);
          toggleButton.addEventListener('click', handleSidebarToggle);
        }
      }

      function handleSidebarToggle(e) {
        e.preventDefault();
        e.stopPropagation();

        const body = document.body;
        const sidebar = document.querySelector('.main-sidebar');

        if (body.classList.contains('sidebar-collapse')) {
          body.classList.remove('sidebar-collapse');
          if (sidebar) sidebar.classList.remove('sidebar-collapse');
        } else {
          body.classList.add('sidebar-collapse');
          if (sidebar) sidebar.classList.add('sidebar-collapse');
        }
      }

      // Initialize Bootstrap dropdowns
      document.addEventListener('DOMContentLoaded', function() {
        // Fallback for dropdown functionality
        const userDropdown = document.querySelector('.nav-item.dropdown .dropdown-toggle');
        const dropdownMenu = document.querySelector('.nav-item.dropdown .dropdown-menu');

        if (userDropdown && dropdownMenu) {
          userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Toggle dropdown visibility
            const isVisible = dropdownMenu.style.display === 'block';
            if (isVisible) {
              dropdownMenu.style.display = 'none';
              dropdownMenu.classList.remove('show');
            } else {
              dropdownMenu.style.display = 'block';
              dropdownMenu.classList.add('show');
            }
          });

          // Close dropdown when clicking outside
          document.addEventListener('click', function(event) {
            if (!userDropdown.contains(event.target) && !dropdownMenu.contains(event.target)) {
              dropdownMenu.style.display = 'none';
              dropdownMenu.classList.remove('show');
            }
          });
        }
      });
    </script>

    <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    </original_code>