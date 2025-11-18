<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/conn.php';

// Generate a unique session token for tracking
function generateSessionToken()
{
    return bin2hex(random_bytes(16));
}

if (isset($_SESSION['usertype'])) {
    if ($_SESSION['usertype'] === 'u') {
        header('Location: users/index.php');
        exit();
    } elseif ($_SESSION['usertype'] === 'a' || $_SESSION['usertype'] === 'sa') {
        header('Location: admin/index.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id']) && isset($_POST['password'])) {
    try {
        // Check if user exists in webuser table by email or username
        $stmt = $conn->prepare("SELECT * FROM webuser WHERE BINARY email = ? OR BINARY name = ?");
        $login_id = trim($_POST['login_id']);
        $userpassword = $_POST['password'];

        $stmt->bind_param("ss", $login_id, $login_id);
        $stmt->execute();
        $getemail = $stmt->get_result();

        if ($getemail->num_rows == 1) {
            $userData = $getemail->fetch_assoc();
            $usertype = $userData['usertype'];
            $usermail = $userData['email'];
            $username = $userData['name'];

            if ($usertype == 'u') {
                // Check user credentials
                $stmt = $conn->prepare("SELECT * FROM users WHERE BINARY email = ? OR BINARY name = ?");
                $stmt->bind_param("ss", $usermail, $username);
                $stmt->execute();
                $validate = $stmt->get_result();

                if ($validate->num_rows == 1) {
                    $user = $validate->fetch_assoc();
                    if (password_verify($userpassword, $user['password'])) {
                        // Regenerate session ID for security FIRST
                        session_regenerate_id(true);

                        // Generate session token for tracking
                        $sessionToken = generateSessionToken();

                        // Update webuser table with session token and session ID
                        $sessionId = session_id();
                        $updateStmt = $conn->prepare("UPDATE webuser SET session_status = ?, session_id = ? WHERE email = ?");
                        $updateStmt->bind_param("sss", $sessionToken, $sessionId, $usermail);
                        $updateStmt->execute();

                        // Set session variables
                        $_SESSION['user'] = $usermail;
                        $_SESSION['usertype'] = 'u';
                        $_SESSION['student_id'] = $user['id'];
                        $_SESSION['username'] = $user['name'];
                        $_SESSION['session_status'] = $sessionToken;

                        header('location: users/index.php');
                        exit();
                    } else {
                        $error = 'Invalid credentials!';
                    }
                } else {
                    $error = 'User account not found!';
                }
            } else if ($usertype == 'a' || $usertype == 'sa') {
                // Check admin credentials
                $stmt = $conn->prepare("SELECT * FROM admin WHERE BINARY email = ? OR BINARY name = ?");
                $stmt->bind_param("ss", $usermail, $username);
                $stmt->execute();
                $validate = $stmt->get_result();

                if ($validate->num_rows == 1) {
                    $admin = $validate->fetch_assoc();
                    if (password_verify($userpassword, $admin['password'])) {
                        // Set admin session variables
                        $_SESSION['user'] = $usermail;
                        $_SESSION['email'] = $usermail;
                        $_SESSION['usertype'] = $usertype;
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['role'] = $admin['role'];

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        header('location: admin/index.php');
                        exit();
                    } else {
                        $error = 'Invalid credentials!';
                    }
                } else {
                    $error = 'Admin account not found!';
                }
            }
        } else {
            $error = 'Account not found!';
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'An error occurred during login. Please try again.';
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
    <link rel="icon" href="assets/img/favicon.ico" sizes="any">
    <link rel="icon" type="image/jpeg" href="maharlika/logo.jpg">

    <link rel="stylesheet" href="login.css">
    <style>
        /* Dropdown Menu Styles */
        .help-dropdown {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .dropdown-toggle {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 500;
            color: #333;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            border: none;
            padding: 10px 0;
            min-width: 200px;
        }

        .dropdown-item {
            padding: 12px 20px;
            transition: all 0.2s ease;
            color: #333;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #007bff;
            padding-left: 25px;
        }

        .dropdown-item i {
            margin-right: 10px;
            width: 20px;
        }

        /* Loading Animation Styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            color: white;
            font-size: 16px;
            margin-top: 20px;
            font-weight: 500;
        }

        .loading-content {
            text-align: center;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .form-disabled {
            pointer-events: none;
            opacity: 0.6;
        }

        .btn-login.loading {
            position: relative;
            color: transparent;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Modal Styles */
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="background-wrapper">
        <div class="background-image"></div>
    </div>

    <!-- Help Dropdown -->
    <div class="help-dropdown dropdown">
        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-circle-question"></i> Help
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">
                    <i class="fa-solid fa-circle-info"></i> About Us
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#tutorialModal">
                    <i class="fa-solid fa-book-open"></i> Tutorial
                </a>
            </li>
        </ul>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Signing you in...</div>
        </div>
    </div>

    <div class="login-container">
        <div id="clock"></div>
        <h1 class="login-title">Welcome Back</h1>
        <?php if (!empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST') : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST" id="loginForm">
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
            <button type="submit" class="btn btn-login w-100" id="loginBtn">
                <span class="btn-text">Sign In</span>
            </button>
        </form>
    </div>

    <!-- About Us Modal -->
    <div class="modal fade" id="aboutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-circle-info me-2"></i>About MNHS Library System</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3">Our Mission</h6>
                    <p>The MNHS Library System is dedicated to providing students and staff with easy access to educational resources and streamlined library management.</p>

                    <h6 class="fw-bold mb-3 mt-4">Features</h6>
                    <ul>
                        <li>Digital book catalog and search</li>
                        <li>Easy borrowing and return process</li>
                        <li>Real-time availability tracking</li>
                        <li>User-friendly interface</li>
                        <li>Secure account management</li>
                    </ul>


                </div>
            </div>
        </div>


    </div>

    <!-- Tutorial Modal -->
    <div class="modal fade" id="tutorialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-book-open me-2"></i>How to Use the Library System</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3">Getting Started (For Students)</h6>
                    <ol>
                        <li class="mb-3">
                            <strong>Request for Account:</strong> Go to the Library and the Librarian will provide the login credentials for you
                        </li>
                        <li class="mb-3">
                            <strong>Login:</strong> Enter your username or email and password to access the system.
                        </li>
                        <li class="mb-3">
                            <strong>Browse Books:</strong> Use the search function to find books by title, author, publisher and etc.
                            and in My Books list you can see your borrowed books and its status(pending, approved, returned or rejected).
                        </li>
                        <li class="mb-3">
                            <strong>Borrow Books:</strong> Click on a book to view details and select "Borrow" if available.
                        </li>
                        <li class="mb-3">
                            <strong>Return Books:</strong> Go to "My Books List Section Qr Code(Will be generated once the librarian approved your request).
                        </li>

                    </ol>

                    <h6 class="fw-bold mb-3 mt-4">Tips</h6>
                    <ul>
                        <li>You can Borrow an 8 maximum books in total</li>
                        <li>Use filters to narrow down search results</li>
                        <li>You will have 7 days to return your borrowed Books</li>
                        <li>Contact or Go to the librarian if you need assistance</li>
                    </ul>

                    <div class="alert alert-info mt-4">
                        <i class="fa-solid fa-lightbulb me-2"></i>
                        <strong>Need Help?</strong> Contact the library staff for personalized assistance.
                    </div>
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

        // Login form loading animation
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');

        loginForm.addEventListener('submit', function(e) {
            const loginId = document.querySelector('input[name="login_id"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;

            if (!loginId || !password) {
                e.preventDefault();
                alert('Please fill in all fields!');
                return false;
            }

            showLoading();
        });

        function showLoading() {
            loadingOverlay.style.display = 'flex';
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
            loginForm.classList.add('form-disabled');
        }

        function hideLoading() {
            loadingOverlay.style.display = 'none';
            loginBtn.classList.remove('loading');
            loginBtn.disabled = false;
            loginForm.classList.remove('form-disabled');
        }

        // Forgot password modal form validation
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