<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="HEADER PAGE">
  <meta name="author" content="Ely Gian Ga">
  <title>Admin</title>
  <link rel="stylesheet" href="../public/assets/css/adminLTE.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body.light-mode {
      background-color: #f8f9fa;
      color: #343a40;
    }

    body.dark-mode {
      background-color: #343a40;
      color: #f8f9fa;
    }

    .navbar-light {
      background-color: #f8f9fa !important;
      border-bottom: 1px solid #dee2e6;
    }

    .navbar-dark {
      background-color: #343a40 !important;
      border-bottom: 1px solid #495057;
    }

    .navbar-toggler-icon {
      filter: invert(100%);
    }

    .theme-toggle {
      cursor: pointer;
      font-size: 1.5rem;
      margin-left: auto;
      transition: color 0.3s ease;
    }

    body.light-mode .theme-toggle {
      color: #343a40;
    }

    body.dark-mode .theme-toggle {
      color: #f8f9fa;
    }

    body {
      transition: background-color 0.3s, color 0.3s;
    }

    .notification-bell {
      position: relative;
      z-index: 1100;
      /* higher than sidebar */
    }

    #notification-dropdown {
      display: none;
      position: absolute;
      left: 50%;
      top: 50px;
      transform: translateX(-50%);
      background: white;
      border: 1px solid #ccc;
      width: 350px;
      max-height: 400px;
      overflow-y: auto;
      z-index: 1200;

      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 500px) {
      #notification-dropdown {
        width: 95vw;
        left: 2.5vw;
        transform: none;
      }
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed light-mode">
  <div class="wrapper">

    <form id="logout-form" action="../logout.php" method="POST" style="display:none;">

    </form>

    <?php
    include_once("../config/conn.php");
    if (!isset(
      $conn
    )) {

      $conn = $GLOBALS['conn'];
    }
    $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
    ?>
    <nav class="main-header navbar navbar-expand navbar-light">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
      </ul>

      <div class="notification-bell" onclick="toggleNotifications()">
        <i class="fa fa-bell" style="font-size: 30px; color: black;"></i>
        <span id="notification-count" style="position: absolute; top: 0; right: 0; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px;">
          <?= $result->num_rows ?>
        </span>
        <div id="notification-dropdown">
          <div id="notifications">
            <?php while ($row = $result->fetch_assoc()): ?>
              <div class="notification-item" style="padding: 10px; border-bottom: 1px solid #eee;">
                <div style="margin-bottom: 2px;"><strong>Book Title:</strong> <span class="text-info"><?= htmlspecialchars($row['title']) ?></span></div>
                <div style="margin-bottom: 2px;"><strong>Author:</strong> <?= htmlspecialchars($row['author']) ?></div>
                <div style="margin-bottom: 2px;"><strong>Reserved by:</strong> <span class="text-success"><?= htmlspecialchars($row['name']) ?></span></div>
                <div style="font-size: 12px; color: #888;">Reserved on: <?= date('F j, Y \a\t g:i A', strtotime($row['created_at'])) ?></div>
                <hr style="border: 2px solid #007bff; margin: 6px 0; border-radius: 2px;">
              </div>
            <?php endwhile; ?>
            <?php if ($result->num_rows === 0): ?>
              <div class="notification-item" style="padding: 10px;">No notifications yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a href="index.php" class="nav-link">Home</a>
        </li>
        <li class="nav-item">
          <a href="../aboutUs/index.php" class="nav-link">About</a>
        </li>
        <li class="nav-item">
          <a href="../admin/reservations.php" class="nav-link">Reservations</a>
        </li>
        <!-- User Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
            <span id="usernameDisplay">
              <?php
              // Check if admin's email exists in session and display it
              echo isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'Admin Account';
              ?>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
    <script>
      function toggleNotifications() {
        const dropdown = document.getElementById('notification-dropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
      }

      document.addEventListener('click', (event) => {
        const bell = document.querySelector('.notification-bell');
        if (!bell.contains(event.target)) {
          document.getElementById('notification-dropdown').style.display = 'none';
        }
      });
    </script>

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
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </div>
</body>

</html>