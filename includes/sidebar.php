<?php
include '../config/conn.php';
function adminSidebar($conn)
{
  $result = $conn->query("SELECT COUNT(*) as count FROM admin WHERE role = 'sa'");
  $row = $result->fetch_assoc();
  return $row['count'] > 0;
}
?>

<aside class="main-sidebar sidebar-light-secondary elevation-5">
  <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
  <div class="d-flex justify-content-center align-items-center">
    <img class="w-100 h-50" src="../maharlika/logo.jpg" alt="">
  </div>

  <div class="sidebar">
    <nav class="mt-3">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../admin/index.php" class="nav-link">
            <i class="nav-icon fas fa-home"></i>
            <p>Home</p>
            <!-- <span class="badge badge-pill badge-danger">New</span> Notification Badge -->
          </a>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../analysis/displayStats.php" class="nav-link">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>Analytics</p>
            <!-- <span class="badge badge-pill badge-danger">New</span> Notification Badge -->
          </a>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../admin/reservations.php" class="nav-link">
            <i class="nav-icon fas fa-box"></i>
            <p>Reservation</p>
          </a>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../admin/student.php" class="nav-link">
            <i class="nav-icon fa-regular fa-circle-user"></i>
            <p>Add Student</p>
          </a>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <?php if (adminSidebar($conn)) : ?>
          <li class="nav-item">
            <a href="../admin/admin.php" class="nav-link">
              <i class="fa-solid fa-user-tie"></i>
              <p>Add Librarian</p>
            </a>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../aboutUs/index.php" class="nav-link">
            <i class="nav-icon fas fa-info-circle"></i>
            <p>About Us</p>
          </a>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>
      </ul>
    </nav>
  </div>
  <div class="sidebar-backdrop"></div>
</aside>

<style>
  .main-sidebar {
    width: 250px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    z-index: 1000;
    background: linear-gradient(180deg, rgb(218, 208, 223), rgb(206, 225, 238));
    overflow-y: auto;
    transition: all 0.3s ease-in-out;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
    border-right: 1px solid #dee2e6;
    padding-top: 20px;
  }

  img {
    margin: 15px;
    border-radius: 50%;
    padding: 20px;
    height: 200px;
    width: 200px;
    box-shadow: #007bff;
  }

  .sidebar-logo {
    width: 80%;
    max-height: 100px;
    object-fit: contain;
    border-radius: 5px;
    display: block;
    margin: 10px auto;
  }

  .nav-link {
    color: #ffffff;
    font-size: 16px;
    font-weight: 500;
    padding: 12px 20px;
    position: relative;
    transition: background-color 0.3s, color 0.3s, transform 0.3s;
    border-radius: 10px;
  }

  .nav-link:hover {
    background-color: #1c74b0;
    color: #fff;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transform: scale(1.05);
  }

  .nav-link i {
    margin-right: 10px;
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .nav-link:hover i {
    transform: scale(1.2);
    color: #ffd700;
  }

  .nav-item.active .nav-link {
    background-color: #007bff;
    color: #fff;
    border-radius: 5px;
    animation: pulse 1s infinite;
  }

  .badge {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 12px;
    padding: 5px;
    border-radius: 50%;
  }

  .sidebar-divider {
    border-top: 3px solid rgba(25, 116, 206, 0.8);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding-top: 10px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    margin: 10px 0;
  }

  .menu-toggle {
    display: none;
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 24px;
    background-color: transparent;
    border: none;
    color: #495057;
    cursor: pointer;
    z-index: 1050;
  }

  @media (max-width: 768px) {
    .main-sidebar {
      width: 0;
      left: -250px;
      transition: left 0.3s ease-in-out;
    }

    .main-sidebar.open {
      left: 0;
      width: 250px;
    }

    .content-wrapper {
      margin-left: 0;
    }

    .menu-toggle {
      display: block;
    }
  }

  .main-sidebar::-webkit-scrollbar {
    width: 8px;
  }

  .main-sidebar::-webkit-scrollbar-thumb {
    background: #adb5bd;
    border-radius: 4px;
  }

  .main-sidebar::-webkit-scrollbar-thumb:hover {
    background: #6c757d;
  }

  .sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    z-index: 1040;
  }

  .sidebar-backdrop.show {
    display: block;
  }

  @keyframes pulse {
    0% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.1);
    }

    100% {
      transform: scale(1);
    }
  }
</style>

<script>
  function toggleSidebar() {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
  }

  document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickOnMenuToggle = event.target.closest('.menu-toggle');

    if (!isClickInsideSidebar && !isClickOnMenuToggle && sidebar.classList.contains('open')) {
      sidebar.classList.remove('open');
      backdrop.classList.remove('show');
    }
  });
</script>

<!-- Backdrop for mobile -->