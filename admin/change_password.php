<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/conn.php';

ini_set('log_errors', 1);
ini_set('error_log', '../error.log');
error_reporting(E_ALL);

if (!isset($_SESSION['usertype']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Handle student password change
if (isset($_POST['change_password'])) {
    try {
        if (!isset($_POST['student_id'], $_POST['student_email'], $_POST['new_password'], $_POST['confirm_password'])) {
            throw new Exception('Missing required fields');
        }

        $student_id = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);
        $student_email = filter_var($_POST['student_email'], FILTER_VALIDATE_EMAIL);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Additional input validation
        if ($student_id === false || !$student_email) {
            throw new Exception('Invalid student ID or email format');
        }

        // Password validation
        if (strlen($new_password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        if ($new_password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND email = ?");
        $stmt->bind_param("sis", $hashed_password, $student_id, $student_email);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update password");
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("No user found with provided ID and email");
        }

        // Log success (remove password from logs in production)
        error_log("Password changed successfully for user ID: $student_id, Email: $student_email");

        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle admin password change
if (isset($_POST['change_admin_password'])) {
    try {
        // Check if user is super admin
        if ($_SESSION['usertype'] !== 'sa') {
            throw new Exception('Only Super Admin can change admin passwords');
        }

        if (!isset($_POST['admin_email'], $_POST['new_password'], $_POST['confirm_password'])) {
            throw new Exception('Missing required fields');
        }

        $admin_email = filter_var($_POST['admin_email'], FILTER_VALIDATE_EMAIL);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Email validation
        if (!$admin_email) {
            throw new Exception('Invalid email format');
        }

        // Password validation
        if (strlen($new_password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        if ($new_password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        // Verify target user is an admin
        $stmt = $conn->prepare("SELECT role FROM admin WHERE email = ?");
        $stmt->bind_param("s", $admin_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No admin found with provided email");
        }

        $admin_data = $result->fetch_assoc();
        if ($admin_data['role'] === 'sa') {
            throw new Exception("Cannot change Super Admin password through this interface");
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update only admin table (remove webuser table update since it doesn't have password column)
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $admin_email);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update password in database");
        }

        echo json_encode(['success' => true, 'message' => 'Admin password changed successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update password: ' . $e->getMessage()]);
    }
    exit();
}
