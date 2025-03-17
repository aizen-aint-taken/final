<?php
require_once '../config/conn.php';


$users = $conn->query("SELECT email, password FROM users LIMIT 5");
echo "Users table passwords:\n";
while ($user = $users->fetch_assoc()) {
    echo $user['email'] . ": " . $user['password'] . "\n";
}


$admins = $conn->query("SELECT email, password FROM admin LIMIT 5");
echo "\nAdmin table passwords:\n";
while ($admin = $admins->fetch_assoc()) {
    echo $admin['email'] . ": " . $admin['password'] . "\n";
}
