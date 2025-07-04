<?php
require_once 'config/conn.php';


$test_password = "password123";
$hashed = password_hash($test_password, PASSWORD_DEFAULT);
echo "Original password: " . $test_password . "\n";
echo "Hashed password: " . $hashed . "\n";
echo "Verification test: " . (password_verify($test_password, $hashed) ? "Success" : "Failed") . "\n";


$test_email = "test@example.com";
$test_password = "actual_password";

$query = "SELECT password FROM users WHERE email = '$test_email'";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stored_hash = $row['password'];
    echo "\nStored hash for $test_email: " . $stored_hash . "\n";
    echo "Verification result: " . (password_verify($test_password, $stored_hash) ? "Success" : "Failed") . "\n";
}
