<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/conn.php';
require_once 'change_password.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('location: ../index.php');
    exit;
}



$years = $conn->query("SELECT DISTINCT year FROM users ORDER BY year DESC");

$SectionYear =  isset($_GET['year']) && $_GET['year'] !== '' ? $_GET['year'] : null;
if ($SectionYear) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE year = ?");
    $stmt->bind_param("s", $SectionYear);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM users");
}

$error = '';
$success = '';

if ($_POST) {
    error_log('Form submitted with data: ' . print_r($_POST, true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id']) && isset($_POST['password'])) {
    $login_id = $_POST['login_id'];
    $userpassword = $_POST['password'];

    // Check in webuser table for either email or name
    $stmt = $conn->prepare("SELECT * FROM webuser WHERE email = ? OR name = ?");
    $stmt->bind_param("ss", $login_id, $login_id);
    $stmt->execute();
    $getemail = $stmt->get_result();

    if ($getemail->num_rows == 1) {
        $userData = $getemail->fetch_assoc();
        $usertype = $userData['usertype'];
        $usermail = $userData['email'];

        if ($usertype == 'u') {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $usermail);
            $stmt->execute();
            $validate = $stmt->get_result();

            if ($validate->num_rows == 1) {
                $user = $validate->fetch_assoc();
                if (password_verify($userpassword, $user['password'])) {
                    // ... existing session code ...
                }
            }
        }
    }
}

if ($_POST && isset($_POST['mail']) && isset($_POST['password'])) {
    $name = mysqli_real_escape_string($conn, $_POST["name"]);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $sect = mysqli_real_escape_string($conn, $_POST['sect']);
    $email = filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL);

    $password = $_POST['password'];

    if ($email === false) {
        $error = "Invalid email format.";
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $email_result = $stmt->get_result();

        if ($email_result->num_rows > 0) {
            $error = "Already have an account for this Email address.";
        } else {

            $conn->begin_transaction();

            try {

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);


                $stmt = $conn->prepare("INSERT INTO users (email, password, name, age, year, sect) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $email, $hashed_password, $name, $age, $year, $sect);

                if (!$stmt->execute()) {
                    throw new Exception("Error creating user account: " . $stmt->error);
                }

                $stmt = $conn->prepare("INSERT INTO webuser (email, name, usertype) VALUES (?, ?, 'u')");
                $stmt->bind_param("ss", $email, $name);

                if (!$stmt->execute()) {
                    throw new Exception("Error creating webuser entry: " . $stmt->error);
                }


                $conn->commit();
                $success = "Account Successfully Created";


                $_SESSION['success_message'] = "Account Successfully Created";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } catch (Exception $e) {

                $conn->rollback();
                $error = "Error creating account: " . $e->getMessage();
                error_log('Error creating account: ' . $e->getMessage());
            }
        }
    }
}

include('../includes/header.php');
include('../includes/sidebar.php');

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="STUDENT PAGE">
    <meta name="author" content="Ely Gian Ga">
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/css/addStudent.css">
    <link rel="stylesheet" href="../public/assets/css/student.css">
    <title>Student Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <div class="main-content">
        <div class="page-header">
            <h1 class="h3 mb-0 text-center">Student Management</h1>
            <p class="mb-0 text-center">Manage student accounts and information</p>
        </div>
        <div class="row mb-3">
            <div class="col-12 mb-2">
                <div class="input-group search-group w-100">
                    <input type="text" id="studentSearchInput" class="form-control" placeholder="Search by name, email, section...">
                </div>
            </div>
            <div class="col-12">
                <form id="yearFilterForm" class="w-100">
                    <label for="year" class="form-label">Filter by Year Level Who Borrowed:</label>
                    <select name="year" class="form-select w-100" id="yearFilterSelect">
                        <option value="">All Year Level</option>
                        <?php foreach ($years as $row): ?>
                            <option value="<?= htmlspecialchars($row['year']) ?>" <?= ($SectionYear == $row['year']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['year']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-add-student" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-2"></i>Add Student
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover d-none d-md-table">
                <thead>
                    <tr>
                        <th>FULL NAME</th>
                        <th>AGE</th>
                        <th>YEAR LEVEL</th>
                        <th>SECTION</th>
                        <th>EMAIL</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['age']) ?></td>
                            <td><?= htmlspecialchars($user['year']) ?></td>
                            <td><?= htmlspecialchars($user['sect']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editStudentModal"
                                        data-id="<?= $user['id'] ?>"
                                        data-name="<?= htmlspecialchars($user['name']) ?>"
                                        data-age="<?= htmlspecialchars($user['age']) ?>"
                                        data-year="<?= htmlspecialchars($user['year']) ?>"
                                        data-sect="<?= htmlspecialchars($user['sect']) ?>"
                                        data-mail="<?= htmlspecialchars($user['email']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- change Pass -->
                                    <button class="btn btn-secondary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#changePasswordModal"
                                        data-id="<?= $user['id'] ?>"
                                        data-email="<?= htmlspecialchars($user['email']) ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <form action="../admin/deleteStudent.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this student?');">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>


        <div class="mobile-cards d-md-none">
            <?php foreach ($users as $user): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($user['name']) ?></h5>
                        <div class="card-text">
                            <p><strong>Age:</strong> <?= htmlspecialchars($user['age']) ?></p>
                            <p><strong>Year Level:</strong> <?= htmlspecialchars($user['year']) ?></p>
                            <p><strong>Section:</strong> <?= htmlspecialchars($user['sect']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <button class="btn btn-warning btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editStudentModal"
                                data-id="<?= $user['id'] ?>"
                                data-name="<?= htmlspecialchars($user['name']) ?>"
                                data-age="<?= htmlspecialchars($user['age']) ?>"
                                data-year="<?= htmlspecialchars($user['year']) ?>"
                                data-sect="<?= htmlspecialchars($user['sect']) ?>"
                                data-mail="<?= htmlspecialchars($user['email']) ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-secondary btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#changePasswordModal"
                                data-id="<?= $user['id'] ?>"
                                data-email="<?= htmlspecialchars($user['email']) ?>">
                                <i class="fas fa-key"></i> Password
                            </button>
                            <form action="../admin/deleteStudent.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Student Modal -->
        <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBookModalLabel">Add Students</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="../admin/student.php" method="POST">
                            <h1></h1>

                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Put Your Full Name" required>
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="text" class="form-control" name="age" id="age" placeholder="Put Your Age" required>
                            </div>
                            <div class="form-group">
                                <label for="year">Year Level</label>
                                <input type="text" class="form-control" name="year" id="year" placeholder="Your Grade(7-12)" required>
                            </div>
                            <div class="form-group">
                                <label for="sect">Section</label>
                                <input type="text" class="form-control" name="sect" id="sect" placeholder="Your Section" required>
                            </div>
                            <div class="form-group">
                                <label for="mail">Email</label>
                                <input type="email" class="form-control" name="mail" id="mail" placeholder="Your Email" required>
                            </div>
                            <div class="form-group">
                                <label for="pass">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="*******" id="password" required>
                            </div>
                            <button type="submit" class="btn btn-success">Add Student</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- edit modal -->
        <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="../student/edit.php" method="POST">
                            <input type="hidden" name="id" id="edit-id">

                            <div class="form-group">
                                <label for="edit-name">Full Name</label>
                                <input type="text" class="form-control" name="name" id="edit-name" required>
                            </div>

                            <div class="form-group">
                                <label for="edit-age">Age</label>
                                <input type="text" class="form-control" name="age" id="edit-age" required>
                            </div>

                            <div class="form-group">
                                <label for="edit-year">Year Level</label>
                                <input type="text" class="form-control" name="year" id="edit-year" required>
                            </div>

                            <div class="form-group">
                                <label for="edit-sect">Section</label>
                                <input type="text" class="form-control" name="sect" id="edit-sect" required>
                            </div>

                            <div class="form-group">
                                <label for="edit-mail">Email</label>
                                <input type="email" class="form-control" name="mail" id="edit-mail" required>
                            </div>

                            <button type="submit" class="btn btn-success">Update Student</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- change password modal -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Student Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="POST">
                            <input type="hidden" name="student_id" id="change-password-id">
                            <input type="hidden" name="student_email" id="change-password-email">

                            <div class="form-group mb-3">
                                <label>Student Email:</label>
                                <p id="student-email-display" class="form-control-static"></p>
                            </div>

                            <div class="form-group mb-3">
                                <label for="new_password">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary w-100">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../student/editStudent.js"></script>
    <script src="../public/assets/js/resetpassword.js"></script>
    <script src="../public/assets/js/student.js"></script>


</body>

</html>