<?php
require_once 'config/conn.php';


$conn->begin_transaction();

try {

    function isHashed($password)
    {
        return strlen($password) > 32 && (strpos($password, '$2y$') === 0 || strpos($password, '$2a$') === 0);
    }

    // Process users table
    $users = $conn->query("SELECT id, email, password FROM users WHERE password IS NOT NULL");
    $usersUpdated = 0;
    while ($user = $users->fetch_assoc()) {
        if (!isHashed($user['password'])) {
            $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user['id']);
            $stmt->execute();
            $usersUpdated++;
            echo "Updated user: " . $user['email'] . "\n";
        }
    }

    // Process admin table
    $admins = $conn->query("SELECT admin_id, email, password FROM admin WHERE password IS NOT NULL");
    $adminsUpdated = 0;
    while ($admin = $admins->fetch_assoc()) {
        if (!isHashed($admin['password'])) {
            $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
            $stmt->bind_param("si", $hashed_password, $admin['admin_id']);
            $stmt->execute();
            $adminsUpdated++;
            echo "Updated admin: " . $admin['email'] . "\n";
        }
    }

    // Process webuser table - only if it has a password column
    $webusersUpdated = 0;
    if ($conn->query("SHOW COLUMNS FROM webuser LIKE 'password'")->num_rows > 0) {
        $webusers = $conn->query("SELECT email, password FROM webuser WHERE password IS NOT NULL");
        while ($webuser = $webusers->fetch_assoc()) {
            if (!isHashed($webuser['password'])) {
                $hashed_password = password_hash($webuser['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE webuser SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $webuser['email']);
                $stmt->execute();
                $webusersUpdated++;
                echo "Updated webuser: " . $webuser['email'] . "\n";
            }
        }
    }

    $conn->commit();
    echo "\nPassword hashing completed successfully!\n";
    echo "Updated passwords:\n";
    echo "- Users table: $usersUpdated passwords\n";
    echo "- Admin table: $adminsUpdated passwords\n";
    echo "- Webuser table: $webusersUpdated passwords\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
