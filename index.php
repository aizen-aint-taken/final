<?php
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "hello";


// At the top of the file, after session_start()
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/conn.php';

// var_dump($_SESSION);


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

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $usermail = mysqli_real_escape_string($conn, $_POST['email']);
    $userpassword = $_POST['password'];

    // Debug: Print the login attempt
    error_log("Login attempt for email: " . $usermail);

    // Get user details from webuser table
    $getemail = $conn->query("SELECT * FROM webuser WHERE email = '$usermail'");

    if ($getemail->num_rows == 1) {
        $userData = $getemail->fetch_assoc();
        $usertype = $userData['usertype'];

        // Debug: Print user type
        error_log("User type from webuser: " . $usertype);

        if ($usertype == 'u') {
            // Set session name before starting the session
            if (session_status() === PHP_SESSION_NONE) {
                session_name('user_session');
                session_start();
            }

            $validate = $conn->query("SELECT * FROM users WHERE email = '$usermail' AND password = '$userpassword'");
            if ($validate->num_rows == 1) {
                $user = $validate->fetch_assoc();
                $_SESSION['user'] = $usermail;
                $_SESSION['usertype'] = 'u';
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];

                header('location: users/index.php');
                exit();
            } else {
                $error = 'Invalid Email or Password!';
            }
        } else if ($usertype == 'a' || $usertype == 'sa') {  // Check for both admin and super admin
            // Set session name before starting the session
            if (session_status() === PHP_SESSION_NONE) {
                session_name('admin_session');
                session_start();
            }

            // Debug: Print admin validation query
            $query = "SELECT * FROM admin WHERE email = '$usermail' AND password = '$userpassword'";
            error_log("Admin validation query: " . $query);

            // Validate against admin table
            $validate = $conn->query($query);

            if ($validate->num_rows == 1) {
                $admin = $validate->fetch_assoc();

                // Set all necessary session variables
                $_SESSION['user'] = $usermail;
                $_SESSION['email'] = $usermail;
                $_SESSION['usertype'] = $usertype;  // Use usertype from webuser table
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['role'] = $admin['role'];

                // Debug: Print session data
                error_log("Session data set: " . print_r($_SESSION, true));

                header('location: admin/index.php');
                exit();
            } else {
                $error = 'Invalid Email or Password!';
                error_log("Admin validation failed for email: " . $usermail);
            }
        } else {
            $error = 'Invalid user type!';
            error_log("Invalid user type: " . $usertype);
        }
    } else {
        $error = 'Email not found!';
        error_log("Email not found in webuser table: " . $usermail);
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
                    <i class="fa-solid fa-envelope"></i>
                </span>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa-solid fa-lock"></i>
                </span>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-login w-100">Sign In</button>
            <div class="forgot-password">
                <a href="#">Forgot password?</a>
            </div>
        </form>
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
    </script>
</body>

</html>