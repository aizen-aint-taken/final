<?php
session_start();
require_once 'config/conn.php';

if (isset($_POST['reset_password'])) {
    try {
        // Validate password length/complexity here
        if (strlen($_POST['new_password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        $email = $_POST['email'];
        $new_password = $_POST['new_password'];
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Start transaction for multiple updates
        $conn->begin_transaction();

        // Update users table
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();

        // Update admin table
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['success'] = ["Password has been reset successfully. Please login with your new password."];
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = ["Password reset failed: " . $e->getMessage()];
        header('Location: index.php');
        exit();
    }
}
