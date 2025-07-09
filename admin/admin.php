<?php
session_start();
include('../config/conn.php');


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
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $roles = trim($_POST['role']);
        $password = $_POST['password'];

        if (empty($name) || empty($email) || empty($roles) || empty($password)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $stmtCheckAdmin = $conn->prepare("SELECT email FROM admin WHERE email = ?");
        $stmtCheckAdmin->bind_param("s", $email);
        $stmtCheckAdmin->execute();
        if ($stmtCheckAdmin->get_result()->num_rows > 0) {
            throw new Exception("Email already exists in admin table");
        }
        $stmtCheckAdmin->close();

        $stmtCheckWebUser = $conn->prepare("SELECT email FROM webuser WHERE email = ?");
        $stmtCheckWebUser->bind_param("s", $email);
        $stmtCheckWebUser->execute();
        if ($stmtCheckWebUser->get_result()->num_rows > 0) {
            throw new Exception("Email already exists in webuser table");
        }
        $stmtCheckWebUser->close();

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if (!$hashed_password) {
            throw new Exception("Password hashing failed");
        }

        $conn->begin_transaction();

        $stmtInsertAdmin = $conn->prepare("INSERT INTO admin (name, email, role, password) VALUES (?, ?, ?, ?)");
        if (!$stmtInsertAdmin) {
            throw new Exception("Failed to prepare admin insert statement: " . $conn->error);
        }
        $stmtInsertAdmin->bind_param("ssss", $name, $email, $roles, $hashed_password);
        if (!$stmtInsertAdmin->execute()) {
            throw new Exception("Failed to insert into admin table: " . $stmtInsertAdmin->error);
        }
        $stmtInsertAdmin->close();

        $stmtInsertWebUser = $conn->prepare("INSERT INTO webuser (email, name, usertype) VALUES (?, ?, ?)");
        if (!$stmtInsertWebUser) {
            throw new Exception("Failed to prepare webuser insert statement: " . $conn->error);
        }
        $stmtInsertWebUser->bind_param("sss", $email, $name, $roles);
        if (!$stmtInsertWebUser->execute()) {
            throw new Exception("Failed to insert into webuser table: " . $stmtInsertWebUser->error);
        }
        $stmtInsertWebUser->close();

        $conn->commit();
        $success = "Admin added successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    $deleteEmail = $_GET['delete'];

    $checkRole = $conn->prepare("SELECT role FROM admin WHERE email = ?");
    $checkRole->bind_param("s", $deleteEmail);
    $checkRole->execute();
    $result = $checkRole->get_result();
    $adminData = $result->fetch_assoc();

    if (!$adminData) {
        $error = "Admin account not found.";
    } else if ($adminData['role'] === 'sa') {
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
        $stmt = $conn->prepare("UPDATE admin SET name = ?, role = ? WHERE BINARY email = ?");
        $stmt->bind_param("sss", $name, $role, $email);
        $stmt->execute();

        $usertype = ($role === 'sa') ? 'sa' : 'a';
        $stmt2 = $conn->prepare("UPDATE webuser SET name = ?, usertype = ? WHERE BINARY email = ?");
        $stmt2->bind_param("sss", $name, $usertype, $email);
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
    <link rel="stylesheet" href="../public/assets/css/addAdmin.css">
</head>

<body>

    <?php
    include('../adminModals/addAdmin.php');
    include('../adminModals/deleteAdminModal.php');
    include('../adminModals/editModal.php');
    ?>
    <div class="main-content">
        <div class="page-header d-flex flex-column flex-md-row align-items-center justify-content-between">
            <h1 class="text-center mb-4">List of Librarian Accounts</h1>
            <a href="scan_qr.php" class="btn btn-primary mb-3 mb-md-0"><i class="fas fa-qrcode me-2"></i>Scan Student QR Code</a>
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
                        $superAdmins = [];
                        $regularAdmins = [];

                        while ($admin = $admins->fetch_assoc()) {
                            if ($admin['role'] === 'sa') {
                                $superAdmins[] = $admin;
                            } else {
                                $regularAdmins[] = $admin;
                            }
                        }

                        foreach ($superAdmins as $superAdmin): ?>
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
                        <?php endforeach;

                        foreach ($regularAdmins as $admin): ?>
                            <tr>
                                <td><?= htmlspecialchars($admin['name']) ?></td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>Admin</td>
                                <?php if ($_SESSION['role'] === 'sa'): ?>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?delete=<?= htmlspecialchars($admin['email']) ?>"
                                                class="btn btn-danger btn-sm delete-admin"
                                                data-name="<?= htmlspecialchars($admin['name']) ?>"
                                                data-email="<?= htmlspecialchars($admin['email']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <button class="btn btn-secondary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#changeAdminPasswordModal"
                                                data-email="<?= htmlspecialchars($admin['email']) ?>">
                                                <i class="fas fa-key"></i>
                                            </button>
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
                <?php foreach ($superAdmins as $superAdmin): ?>
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
                <?php endforeach;

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
                                    <a href="?delete=<?= htmlspecialchars($admin['email']) ?>"
                                        class="btn btn-danger btn-sm w-100 delete-admin"
                                        data-name="<?= htmlspecialchars($admin['name']) ?>"
                                        data-email="<?= htmlspecialchars($admin['email']) ?>">
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

    <!-- Change Admin Password Modal -->
    <div class="modal fade" id="changeAdminPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Admin Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="adminPasswordChangeForm">
                        <input type="hidden" name="admin_email" id="change-admin-password-email">
                        <div class="form-group mb-3">
                            <label>Admin Email:</label>
                            <p id="admin-email-display" class="form-control-static"></p>
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="8">
                            <small class="form-text text-muted">Password must be at least 8 characters long</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../public/assets/js/modals.js"></script>

</body>

</html>