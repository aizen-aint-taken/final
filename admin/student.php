<?php
session_start();
require_once '../config/conn.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $name = mysqli_real_escape_string($conn, $_POST["name"]);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $sect = mysqli_real_escape_string($conn, $_POST['sect']);
    $email = filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

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
            // Insert user details into `users` table
            $stmt = $conn->prepare("INSERT INTO users (email, password, name, age, year, sect) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $email, $password, $name, $age, $year, $sect);

            if ($stmt->execute()) {
                // Insert usertype into `webuser` table
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

// Fetch all users
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
    <!-- <link rel="stylesheet" href="/public/assets/css/admin_index.css"> -->
    <title>Add Student</title>

</head>

<body>
    <div class="container mt-5 w-50">
        <h1 class="text-center">Lists of Student Accounts</h1>
        <hr>
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookModal">Add Students</button>
        </div>

        <!-- Success message -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Error message -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Table View -->
        <div class="table-responsive w-200 mt-5">
            <table class="table table-striped text-center ">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Age</th>
                        <th>Year Level</th>
                        <th>Section</th>
                        <th>View Profile</th>
                        <th>Delete User?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['age']) ?></td>
                            <td><?= htmlspecialchars($user['year']) ?></td>
                            <td><?= htmlspecialchars($user['sect']) ?></td>
                            <td>
                                <a href="profile.php?id=<?= $user['id'] ?>" class="btn btn-primary"><i class="fa-solid fa-user"></i></a>
                            </td>

                            <td>
                                <!-- Delete button -->
                                <form action=" ../admin/deleteStudent.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                                    <!-- <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"> -->
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this student?');"><i class="fa-solid fa-trash"></i></button>
                                </form>
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

    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>