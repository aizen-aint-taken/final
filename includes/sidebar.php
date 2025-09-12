<?php

error_log('Current page: ' . $_SERVER['PHP_SELF']);
error_log('User type: ' . (isset($_SESSION['usertype']) ? $_SESSION['usertype'] : 'not set'));

include '../config/conn.php';

// Get notifications for sidebar
if (!isset($conn)) {
  $conn = $GLOBALS['conn'];
}
$notifications_result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");

function isSuperAdmin()
{
  return isset($_SESSION['role']) && $_SESSION['role'] === 'sa';
}
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-light-secondary elevation-4 border border-indigo">
  <!-- Brand Logo -->
  <div class="d-flex justify-content-center align-items-center py-3">
    <img class="sidebar-logo" src="../maharlika/logo.jpg" alt="Maharlika Logo" onerror="this.style.display='none'">
  </div>

  <!-- Sidebar -->
  <div class="sidebar">
    <nav class="mt-3">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../admin/index.php" class="nav-link">
            <i class="fa-solid fa-border-all"></i>
            <p>All Books</p>
          </a>
        </li>



        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <!-- <li class="nav-item">
          <a href="../admin/depedbooks.php" class="nav-link">
            <i class="fa-solid fa-book"></i>
            <p>Books(Grade 7-12)</p>
          </a>
        </li> -->

        <!-- <li class="nav-item">
          <hr class="sidebar-divider">
        </li> -->



        <!-- Notifications Section -->
        <li class="nav-item has-treeview" id="notifications-section">
          <a href="#" class="nav-link" id="notifications-toggle">
            <i class="nav-icon fas fa-bell"></i>
            <p>
              Notifications
              <span class="badge badge-warning right" id="sidebar-notification-count">
                <?= $notifications_result->num_rows ?>
              </span>
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview" id="notifications-dropdown" style="display: none;">
            <li class="nav-header">📔 Recent Notifications</li>
            <?php
            $notifications_result->data_seek(0); // Reset result pointer
            while ($row = $notifications_result->fetch_assoc()): ?>
              <li class="nav-item">
                <div class="nav-link notification-item-sidebar">
                  <div class="notification-content">
                    <div style="font-size: 12px; font-weight: 600;">📚 <?= htmlspecialchars($row['title']) ?></div>
                    <div style="font-size: 11px; color: #666; margin-top: 2px;">👤 <?= htmlspecialchars($row['name']) ?></div>
                    <div style="font-size: 10px; color: #999; margin-top: 2px;">🕒 <?= date('M j, g:i A', strtotime($row['created_at'])) ?></div>
                  </div>
                </div>
              </li>
            <?php endwhile; ?>
            <?php if ($notifications_result->num_rows === 0): ?>
              <li class="nav-item">
                <div class="nav-link text-center text-muted">
                  📭 No notifications
                </div>
              </li>
            <?php endif; ?>
          </ul>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>


        <li class="nav-item">
          <a href="../analysis/displayStats.php" class="nav-link">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>Analysis</p>
          </a>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../admin/reservations.php" class="nav-link">
            <i class="nav-icon fas fa-box"></i>
            <p>Borrowed Books</p>
          </a>
        </li>

        <li class="nav-item">
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a href="../admin/scan_qr.php" class="nav-link">
            <i class="nav-icon fas fa-qrcode"></i>
            <p>Scan QR</p>
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

        <?php if (isSuperAdmin()): ?>
          <li class="nav-item">
            <hr class="sidebar-divider">
          </li>
        <?php endif; ?>

        <?php if (isSuperAdmin()): ?>
          <li class="nav-item">
            <a href="../admin/admin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>">
              <i class="fas fa-user-shield"></i>
              <span>Add Librarian</span>
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
</aside>

<style>
  .main-sidebar {
    position: fixed !important;
    top: 56px !important;

    left: 0;
    width: 250px;
    height: calc(100vh - 56px) !important;

    z-index: 1037;
    overflow-y: auto;
  }

  .sidebar-logo {
    width: 80%;
    max-height: 100px;
    object-fit: contain;
    border-radius: 50%;
    display: block;
    margin: 10px auto;
  }

  .sidebar-divider {
    border-top: 2px solid rgba(25, 116, 206, 0.8);
    margin: 10px 0;
  }

  .nav-link {
    transition: background-color 0.3s, color 0.3s;
  }

  .nav-link:hover {
    background-color: #007bff;
    color: #fff;
    border-radius: 5px;
  }

  .nav-link.active {
    background-color: #007bff;
    color: #fff;
    border-radius: 5px;
  }

  /* Sidebar Notifications Styling */
  #notifications-section .nav-link {
    position: relative;
  }

  #sidebar-notification-count {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    animation: pulse 2s infinite;
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

  .notification-item-sidebar {
    background: rgba(0, 123, 255, 0.05);
    margin: 2px 8px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .notification-item-sidebar:hover {
    background: rgba(0, 123, 255, 0.1);
    transform: translateX(5px);
  }

  .notification-content {
    padding: 8px;
  }

  .nav-treeview {
    background: rgba(0, 0, 0, 0.02);
    max-height: 300px;
    overflow-y: auto;
  }

  .nav-treeview .nav-header {
    font-weight: 600;
    color: #495057;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 16px;
  }

  /* Notification toggle animation */
  #notifications-toggle .fas.fa-angle-left {
    transition: transform 0.3s ease;
  }

  #notifications-section.menu-open #notifications-toggle .fas.fa-angle-left {
    transform: rotate(-90deg);
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const notificationsToggle = document.getElementById('notifications-toggle');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    const notificationsSection = document.getElementById('notifications-section');

    if (notificationsToggle && notificationsDropdown) {
      notificationsToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const isOpen = notificationsDropdown.style.display === 'block';

        if (isOpen) {

          notificationsDropdown.style.display = 'none';
          notificationsSection.classList.remove('menu-open');
        } else {

          notificationsDropdown.style.display = 'block';
          notificationsSection.classList.add('menu-open');
        }
      });
    }


    document.addEventListener('click', function(event) {
      if (notificationsSection && !notificationsSection.contains(event.target)) {
        if (notificationsDropdown) {
          notificationsDropdown.style.display = 'none';
        }
        if (notificationsSection) {
          notificationsSection.classList.remove('menu-open');
        }
      }
    });


    if (notificationsDropdown) {
      notificationsDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }
  });
</script>