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
