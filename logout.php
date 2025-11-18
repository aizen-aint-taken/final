<?php
session_start();

// If user is logged in, clear their session status
if (isset($_SESSION['user']) && isset($_SESSION['usertype'])) {
    // Include database connection
    require_once 'config/conn.php';

    // Clear session status for users
    if ($_SESSION['usertype'] === 'u') {
        $stmt = $conn->prepare("UPDATE webuser SET session_status = NULL, session_id = NULL WHERE email = ?");
        $stmt->bind_param("s", $_SESSION['user']);
        $stmt->execute();
    }
}

// Destroy session
session_unset();
session_destroy();
header('location: index.php');
exit;
