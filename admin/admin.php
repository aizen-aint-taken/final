<?php
session_start();
require_once '../config/conn.php';
error_reporting(E_ALL);


// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";

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
    $result = $conn->query("SELECT COUNT(*) as count FROM admin WHERE role = 'sa'");
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

if (isset($_POST['addAdmin'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $roles = mysqli_real_escape_string($conn, $_POST['role']);
    // Hash the password before storing
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if trying to add a super admin
    if ($roles === 'sa' && checkSuperAdminExists($conn)) {
        $error = "Only one Super Admin account is allowed in the system.";
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("INSERT INTO admin (name, email, role, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $roles, $hashed_password);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting into admin table");
            }

            $usertype = $roles;
            $stmt2 = $conn->prepare("INSERT INTO webuser (email, usertype) VALUES (?, ?)");
            $stmt2->bind_param("ss", $email, $usertype);

            if (!$stmt2->execute()) {
                throw new Exception("Error inserting into webuser table");
            }

            $conn->commit();
            $success = "Admin added successfully.";

            // Debug: Log successful addition
            error_log("New admin added - Email: $email, Role: $roles");
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
            error_log("Error adding admin: " . $e->getMessage());
        }
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
    <div class="container mt-5 w-100">
        <h1 class="text-center mb-4">List of Librarian Accounts</h1>
        <hr>
        <div class="d-flex justify-content-end mb-3">
            <?php if ($_SESSION['role'] === 'sa'): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fas fa-plus"></i> Add Admin
                </button>
            <?php endif; ?>
        </div>
        <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <div class="table-responsive">
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
                    <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= ($admin['role'] === 'a') ? 'Admin' : 'Super Admin' ?></td>
                            <?php if ($_SESSION['role'] === 'sa'): ?>
                                <td>
                                    <?php if ($admin['role'] !== 'sa'): ?>
                                        <a href="?delete=<?= $admin['email'] ?>" class="btn btn-danger btn-sm btn-action" onclick="confirmDelete(event)">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-primary btn-sm btn-action editAdmin"
                                        data-name="<?= $admin['name'] ?>"
                                        data-email="<?= $admin['email'] ?>"
                                        data-role="<?= $admin['role'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAdminModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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