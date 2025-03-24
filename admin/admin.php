<?php
session_start();
require_once '../config/conn.php';
error_reporting(E_ALL);




if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('location: ../index.php');
    exit;
}

if (!isset($_SESSION['role']) && isset($_SESSION['usertype'])) {
    $_SESSION['role'] = $_SESSION['usertype'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['delete'])) {
    if ($_SESSION['role'] !== 'sa') {
        header('location: index.php');
        exit;
    }
}

$error = '';
$success = '';


function checkSuperAdminExists($conn)
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin WHERE role = 'sa'");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Failed to get result: " . $stmt->error);
        }

        $row = $result->fetch_assoc();
        return isset($row['count']) && $row['count'] > 0;
    } catch (Exception $e) {
        error_log("Error in checkSuperAdminExists: " . $e->getMessage());
        return false;
    }
}

if (isset($_POST['addAdmin'])) {
    try {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $roles = mysqli_real_escape_string($conn, $_POST['role']);
        $password = $_POST['password'];

        if (empty($name) || empty($email) || empty($roles) || empty($password)) {
            throw new Exception("All fields are required");
        }


        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }


        $stmt = $conn->prepare("SELECT email FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists in admin table");
        }

        $stmt = $conn->prepare("SELECT email FROM webuser WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists in webuser table");
        }

        // Check for super admin
        if ($roles === 'sa' && checkSuperAdminExists($conn)) {
            throw new Exception("Only one Super Admin account is allowed in the system.");
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
            throw new Exception("Password hashing failed");
        }

        // Begin transaction
        $conn->begin_transaction();

        // Insert into admin table
        $stmt = $conn->prepare("INSERT INTO admin (name, email, role, password) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare admin insert statement");
        }
        $stmt->bind_param("ssss", $name, $email, $roles, $hashed_password);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into admin table");
        }

        // Insert into webuser table
        $usertype = $roles;
        $stmt2 = $conn->prepare("INSERT INTO webuser (email, usertype) VALUES (?, ?)");
        if (!$stmt2) {
            throw new Exception("Failed to prepare webuser insert statement");
        }
        $stmt2->bind_param("ss", $email, $usertype);
        if (!$stmt2->execute()) {
            throw new Exception("Failed to insert into webuser table");
        }

        // Commit transaction
        $conn->commit();
        $success = "Admin added successfully.";
        error_log("New admin added - Email: $email, Role: $roles");
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->connect_errno != 0) {
            $conn->rollback();
        }
        $error = "Error: " . $e->getMessage();
        error_log("Error adding admin: " . $e->getMessage());
    }
}




if (isset($_GET['delete'])) {
    $deleteEmail = $_GET['delete'];

    // First check if the target account is a super admin
    $checkRole = $conn->prepare("SELECT role FROM admin WHERE email = ?");
    $checkRole->bind_param("s", $deleteEmail);
    $checkRole->execute();
    $result = $checkRole->get_result();
    $adminData = $result->fetch_assoc();

    if ($adminData['role'] === 'sa') {
        $error = "Super Admin accounts cannot be deleted.";
    } else {
        $stmt = $conn->prepare("DELETE FROM admin WHERE email = ?");
        $stmt->bind_param("s", $deleteEmail);
        if ($stmt->execute()) {

            $stmt2 = $conn->prepare("DELETE FROM webuser WHERE email = ?");
            $stmt2->bind_param("s", $deleteEmail);
            $stmt2->execute();
            $success = "Admin deleted successfully.";
        } else {
            $error = "Error deleting admin.";
        }
    }
}

if (isset($_POST['updateAdmin'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];


    $conn->begin_transaction();

    try {
        // Update admin table
        $stmt = $conn->prepare("UPDATE admin SET name = ?, role = ? WHERE email = ?");
        $stmt->bind_param("sss", $name, $role, $email);
        $stmt->execute();

        // Update webuser table to match the role
        $usertype = ($role === 'sa') ? 'sa' : 'a';
        $stmt2 = $conn->prepare("UPDATE webuser SET usertype = ? WHERE email = ?");
        $stmt2->bind_param("ss", $usertype, $email);
        $stmt2->execute();

        $conn->commit();
        $success = "Admin updated successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error updating admin: " . $e->getMessage();
    }
}


$admins = $conn->query("SELECT * FROM admin");
include('../includes/header.php');
include('../includes/sidebar.php');

// Redirect if not super admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sa') {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins</title>
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../public/assets/css/librarian.css">

</head>

<body>
    <div class="main-content">
        <div class="page-header">
            <h1 class="text-center mb-4">List of Librarian Accounts</h1>
        </div>

        <div class="container-fluid px-4">
            <div class="d-flex justify-content-end mb-3">
                <?php if ($_SESSION['role'] === 'sa'): ?>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="fas fa-plus"></i> Add Admin
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <!-- Desktop Table View -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-striped text-center">
                    <thead class="thead-dark">
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <?php if ($_SESSION['role'] === 'sa'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $admins->data_seek(0);
                        $superAdmin = null;
                        $regularAdmins = [];

                        while ($admin = $admins->fetch_assoc()) {
                            if ($admin['role'] === 'sa') {
                                $superAdmin = $admin;
                            } else {
                                $regularAdmins[] = $admin;
                            }
                        }

                        if ($superAdmin): ?>
                            <tr>
                                <td><?= htmlspecialchars($superAdmin['name']) ?></td>
                                <td><?= htmlspecialchars($superAdmin['email']) ?></td>
                                <td>Super Admin</td>
                                <?php if ($_SESSION['role'] === 'sa'): ?>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-primary btn-sm editAdmin"
                                                data-name="<?= htmlspecialchars($superAdmin['name']) ?>"
                                                data-email="<?= htmlspecialchars($superAdmin['email']) ?>"
                                                data-role="<?= htmlspecialchars($superAdmin['role']) ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editAdminModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endif;

                        foreach ($regularAdmins as $admin): ?>
                            <tr>
                                <td><?= htmlspecialchars($admin['name']) ?></td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>Admin</td>
                                <?php if ($_SESSION['role'] === 'sa'): ?>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?delete=<?= $admin['email'] ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="confirmDelete(event)">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <button class="btn btn-primary btn-sm editAdmin"
                                                data-name="<?= htmlspecialchars($admin['name']) ?>"
                                                data-email="<?= htmlspecialchars($admin['email']) ?>"
                                                data-role="<?= htmlspecialchars($admin['role']) ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editAdminModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="mobile-cards d-md-none">
                <?php
                $admins->data_seek(0);
                $superAdmin = null;
                $regularAdmins = [];

                while ($admin = $admins->fetch_assoc()) {
                    if ($admin['role'] === 'sa') {
                        $superAdmin = $admin;
                    } else {
                        $regularAdmins[] = $admin;
                    }
                }

                if ($superAdmin): ?>
                    <div class="card mb-3 admin-card super-admin-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-shield me-2"></i>
                                <?= htmlspecialchars($superAdmin['name']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="admin-info">
                                <p>
                                    <i class="fas fa-envelope me-2"></i>
                                    <strong>Email:</strong> <?= htmlspecialchars($superAdmin['email']) ?>
                                </p>
                                <p>
                                    <i class="fas fa-user-shield me-2"></i>
                                    <strong>Role:</strong>
                                    <span class="badge bg-primary">Super Admin</span>
                                </p>
                            </div>
                            <?php if ($_SESSION['role'] === 'sa'): ?>
                                <div class="action-buttons mt-3">
                                    <button class="btn btn-primary btn-sm editAdmin w-100"
                                        data-name="<?= htmlspecialchars($superAdmin['name']) ?>"
                                        data-email="<?= htmlspecialchars($superAdmin['email']) ?>"
                                        data-role="<?= htmlspecialchars($superAdmin['role']) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAdminModal">
                                        <i class="fas fa-edit me-2"></i>Edit Admin
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif;

                foreach ($regularAdmins as $admin): ?>
                    <div class="card mb-3 admin-card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-tie me-2"></i>
                                <?= htmlspecialchars($admin['name']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="admin-info">
                                <p>
                                    <i class="fas fa-envelope me-2"></i>
                                    <strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?>
                                </p>
                                <p>
                                    <i class="fas fa-user-shield me-2"></i>
                                    <strong>Role:</strong>
                                    <span class="badge bg-info">Admin</span>
                                </p>
                            </div>
                            <?php if ($_SESSION['role'] === 'sa'): ?>
                                <div class="action-buttons mt-3">
                                    <button class="btn btn-primary btn-sm editAdmin w-100 mb-2"
                                        data-name="<?= htmlspecialchars($admin['name']) ?>"
                                        data-email="<?= htmlspecialchars($admin['email']) ?>"
                                        data-role="<?= htmlspecialchars($admin['role']) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAdminModal">
                                        <i class="fas fa-edit me-2"></i>Edit Admin
                                    </button>
                                    <a href="?delete=<?= $admin['email'] ?>"
                                        class="btn btn-danger btn-sm w-100"
                                        onclick="confirmDelete(event)">
                                        <i class="fas fa-trash me-2"></i>Delete Admin
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">

                        <div class="form-group mb-3">
                            <label for="editName">Full Name</label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editEmail">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editRole">Role</label>
                            <select class="form-control" name="role" id="editRole" required>
                                <option value="a">Admin</option>
                                <option value="sa">Super Admin</option>
                            </select>
                        </div>


                        <button type="submit" name="updateAdmin" class="btn btn-primary w-100">Update Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="form-group mb-3">
                            <label for="addName">Full Name</label>
                            <input type="text" class="form-control" name="name" id="addName" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="addEmail">Email</label>
                            <input type="email" class="form-control" name="email" id="addEmail" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="addRole">Role</label>
                            <select class="form-control" name="role" id="addRole" required>
                                <option value="a">Admin</option>
                                <?php if (!checkSuperAdminExists($conn)): ?>
                                    <option value="sa">Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                        </div>

                        <button type="submit" name="addAdmin" class="btn btn-success w-100">Add Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .main-content {
            min-height: 100vh;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding-top: 70px;
                /* Space for the menu toggle */
            }

            .page-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .container-fluid {
                padding: 10px;
            }

            /* Card styles for mobile */
            .mobile-cards .card {
                margin-bottom: 15px;
                border-radius: 10px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .mobile-cards .card-body {
                padding: 15px;
            }

            .mobile-cards .card-title {
                font-size: 1.1rem;
                margin-bottom: 15px;
                color: #1e3c72;
            }

            .mobile-cards .card-text p {
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 1px solid #eee;
            }

            /* Button styling */
            .btn {
                padding: 8px 16px;
                border-radius: 8px;
            }

            .btn-success {
                background: linear-gradient(45deg, #2ecc71, #27ae60);
                border: none;
                box-shadow: 0 2px 5px rgba(46, 204, 113, 0.3);
            }

            /* Modal adjustments for mobile */
            .modal-dialog {
                margin: 10px;
            }

            .modal-content {
                border-radius: 15px;
            }

            .modal-header {
                padding: 15px;
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                color: white;
                border-radius: 15px 15px 0 0;
            }

            .modal-body {
                padding: 20px;
            }

            /* Form controls */
            .form-control {
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 15px;
            }
        }

        /* Animation for sidebar transition */
        .main-sidebar.open~.main-content {
            transform: translateX(250px);
        }

        /* Ensure content is visible */
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .admin-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-5px);
        }

        .admin-card .card-header {
            border-radius: 15px 15px 0 0;
            padding: 15px;
        }

        .admin-card .card-body {
            padding: 20px;
        }

        .admin-info p {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .admin-info p:last-child {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-buttons .btn {
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
        }

        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                padding-top: 70px;
            }

            .page-header {
                margin-bottom: 20px;
            }

            .admin-card {
                margin-bottom: 15px;
            }
        }

        .super-admin-card {
            border: 2px solid #1e3c72;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.2);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.editAdmin').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('editName').value = this.getAttribute('data-name');
                document.getElementById('editEmail').value = this.getAttribute('data-email');
                document.getElementById('editRole').value = this.getAttribute('data-role');
            });
        });

        function confirmDelete(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = event.target.href;
                }
            });
        }
    </script>
</body>

</html>