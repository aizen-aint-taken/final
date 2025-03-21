<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/conn.php';


// Add error logging
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');
error_reporting(E_ALL);

// Check if user is admin or super admin
if (!isset($_SESSION['usertype']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (isset($_POST['change_password'])) {
    $student_id = $_POST['student_id'];
    $student_email = $_POST['student_email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit();
    }

    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND email = ?");
        $stmt->bind_param("sis", $hashed_password, $student_id, $student_email);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update password");
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("No user found with provided ID and email");
        }

        // Add debug logging
        error_log("Password changed for user ID: $student_id, Email: $student_email");
        error_log("New hashed password: $hashed_password");

        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
