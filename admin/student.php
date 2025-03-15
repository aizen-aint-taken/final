<?php
session_start();
require_once '../config/conn.php';
// include '../student/edit.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('location: ../index.php');
    exit;
}

// if ($_SESSION['usertype'] !== 'a') {
//     header("Location: ../index.php");
//     exit;
// }

$error = '';
$success = '';

if ($_POST) {
    $name = mysqli_real_escape_string($conn, $_POST["name"]);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $sect = mysqli_real_escape_string($conn, $_POST['sect']);
    $email = filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL);
    // $password = $_POST['password'];
    $password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : "";


    if ($email === false) {
        $error = "Invalid email format.";
    } else {
        // Check if the email already exists in `users`
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $email_result = $stmt->get_result();

        if ($email_result->num_rows > 0) {
            $error = "Already have an account for this Email address.";
        } else {

            $stmt = $conn->prepare("INSERT INTO users (email, password, name, age, year, sect) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $email, $password, $name, $age, $year, $sect);

            if ($stmt->execute()) {

                $stmt = $conn->prepare("INSERT INTO webuser (email, usertype) VALUES (?, 'u')");
                $stmt->bind_param("s", $email);
                $stmt->execute();

                $success = "Account Successfully Created";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
    }
}

$users = $conn->query("SELECT * FROM `users`");

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
    <!-- <link rel="stylesheet" href="../public/assets/css/student.css"> -->
    <title>Student Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
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

        .btn-add-student {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-add-student:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #1e3c72;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .btn-edit,
        .btn-delete {
            padding: 0.5rem;
            border-radius: 8px;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: #ffc107;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-edit:hover,
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.8rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            border-color: #1e3c72;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                margin-top: 60px;
                padding: 1.5rem;
            }

            .table-container {
                padding: 1rem;
            }

            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="page-header">
            <h1 class="h3 mb-0 text-center">Student Management</h1>
            <p class="mb-0 text-center">Manage student accounts and information</p>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-add-student" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-2"></i>Add Student
            </button>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="table-container" style="border-top: 10px solidrgb(185, 22, 177);">
            <table class="table table-hover">
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
                            <!-- <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"> -->
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
                        <form action="../admin/student.php" method="POST">
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

    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../student/editStudent.js"></script>
    <!-- <script src="../public/assets/js/student.js"></script> -->

    <!-- <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".btn-delete").forEach((button) => {
                button.addEventListener("click", function(e) {
                    e.preventDefault();
                    let row = this.closest("tr");
                    let userId = row.querySelector("input[name='delete_id']").value;

                    Swal.fire({
                        title: "Are you sure?",
                        text: "This action cannot be undone!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#3085d6",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('deleteStudent.php', {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/x-www-form-urlencoded"
                                    },
                                    body: `delete_id=${userId}`
                                })
                                .then(response => response.text())
                                .then(data => {
                                    if (data.trim() === "success") {
                                        row.remove();
                                        Swal.fire("Deleted!", "Student has been removed.", "success");
                                    } else {
                                        Swal.fire("Error!", "Failed to delete student.", "error");
                                    }
                                });
                        }
                    });
                });
            });

            // UPDATE USER (Only Submitting Form)
            document.querySelector("#editStudentModal form").addEventListener("submit", function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                fetch(window.location.href, {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            Swal.fire("Updated!", "Student details updated.", "success").then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire("Error!", "Update failed.", "error");
                        }
                    });
            });

            // ADD USER
            document.querySelector("#addBookModal form").addEventListener("submit", function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                fetch(window.location.href, {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            Swal.fire("Added!", "New student has been added.", "success").then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire("Error!", "Failed to add student.", "error");
                        }
                    });
            });
        });
    </script> -->
</body>

</html>