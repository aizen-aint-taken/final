<?php
require_once '../config/conn.php';


$stmt = $conn->prepare("SELECT email, password FROM users LIMIT ?");
$limit = 5;
$stmt->bind_param("i", $limit);
$stmt->execute();
$users = $stmt->get_result();

echo "Users table passwords:\n";
while ($user = $users->fetch_assoc()) {
    echo $user['email'] . ": " . $user['password'] . "\n";
}


$stmt = $conn->prepare("SELECT email, password FROM admin LIMIT ?");
$stmt->bind_param("i", $limit);
$stmt->execute();
$admins = $stmt->get_result();

echo "\nAdmin table passwords:\n";
while ($admin = $admins->fetch_assoc()) {
    echo $admin['email'] . ": " . $admin['password'] . "\n";
}
