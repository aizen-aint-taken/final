<?php
require_once 'config/conn.php';

// Start transaction
$conn->begin_transaction();

try {

    $users = $conn->query("SELECT id, email, password FROM users");
    while ($user = $users->fetch_assoc()) {
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user['id']);
        $stmt->execute();
    }


    $admins = $conn->query("SELECT email, password FROM admin");
    while ($admin = $admins->fetch_assoc()) {
        $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $admin['email']);
        $stmt->execute();
    }


    $conn->commit();
    echo "All existing passwords have been hashed successfully!";
} catch (Exception $e) {

    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
