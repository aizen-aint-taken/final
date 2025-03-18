<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// echo "hello";



error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/conn.php';




if (isset($_SESSION['usertype'])) {
    if ($_SESSION['usertype'] === 'u') {
        header('Location: users/index.php');
        exit();
    } elseif ($_SESSION['usertype'] === 'a') {
        header('Location: admin/index.php');
        exit();
    }
}

// echo $_SESSION;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id']) && isset($_POST['password'])) {
    $login_id = mysqli_real_escape_string($conn, $_POST['login_id']);
    $userpassword = $_POST['password'];

    // Check in webuser table for either email or name
    $query = "SELECT * FROM webuser WHERE email = '$login_id' OR name = '$login_id'";
    $getemail = $conn->query($query);

    if ($getemail->num_rows == 1) {
        $userData = $getemail->fetch_assoc();
        $usertype = $userData['usertype'];
        $usermail = $userData['email'];

        if ($usertype == 'u') {

            $validate = $conn->query("SELECT * FROM users WHERE email = '$usermail'");
            if ($validate->num_rows == 1) {
                $user = $validate->fetch_assoc();
                if (password_verify($userpassword, $user['password'])) {
                    $_SESSION['user'] = $usermail;
                    $_SESSION['usertype'] = 'u';
                    $_SESSION['student_id'] = $user['id'];
                    $_SESSION['username'] = $user['name'];
                    header('location: users/index.php');
                    exit();
                }
            }
            $error = 'Invalid Name/Email or Password!';
        } else if ($usertype == 'a' || $usertype == 'sa') {
            // For admin
            $validate = $conn->query("SELECT * FROM admin WHERE email = '$usermail'");
            if ($validate->num_rows == 1) {
                $admin = $validate->fetch_assoc();
                if (password_verify($userpassword, $admin['password'])) {
                    $_SESSION['user'] = $usermail;
                    $_SESSION['email'] = $usermail;
                    $_SESSION['usertype'] = $usertype;
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['role'] = $admin['role'];
                    header('location: admin/index.php');
                    exit();
                }
            }
            $error = 'Invalid Name/Email or Password!';
        }
    } else {
        $error = 'Account not found!';
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LOGIN PAGE">
    <meta name="author" content="Ely Gian Ga">
    <title>MNHS Library Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>

    </style>
</head>

<body>
    <div class="background-wrapper">
        <div class="background-image"></div>
    </div>

    <div class="login-container">
        <div id="clock"></div>
        <h1 class="login-title">Welcome Back</h1>
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa-solid fa-user"></i>
                </span>
                <input type="text" name="login_id" class="form-control" placeholder="Enter your name or email" required>
            </div>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa-solid fa-lock"></i>
                </span>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-login w-100">Sign In</button>
        </form>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="reset_password.php" method="POST">
                        <div class="form-group mb-3">
                            <label for="resetEmail">Email Address</label>
                            <input type="email" class="form-control" name="email" id="resetEmail" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="newPassword">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="newPassword" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                        </div>
                        <button type="submit" name="resetPassword" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clock = document.getElementById('clock');

            function updateClock() {
                const now = new Date();
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                };
                clock.textContent = now.toLocaleString('en-PH', options);
            }

            setInterval(updateClock, 1000);
            updateClock();
        });

        document.querySelector('#forgotPasswordModal form').addEventListener('submit', function(e) {
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>

</html>