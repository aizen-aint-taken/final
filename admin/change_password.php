<?php
session_start();
require_once '../config/conn.php';

// Add error logging
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');
error_reporting(E_ALL);

// Check if user is admin or super admin
if (!isset($_SESSION['usertype']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('location: ../index.php');
    exit;
}

if (isset($_POST['change_password'])) {
    $student_id = $_POST['student_id'];
    $student_email = $_POST['student_email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header('Location: student.php');
        exit();
    }

    // Store password as plain text to match login verification
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND email = ?");
    $stmt->bind_param("sis", $new_password, $student_id, $student_email);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Password has been changed successfully.";
    } else {
        $_SESSION['error'] = "Error changing password.";
    }

    header('Location: student.php');
    exit();
}
