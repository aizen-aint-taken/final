<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once 'config/conn.php';

$userEmail = $_SESSION['user'];
$sessionToken = $_SESSION['session_status'];

// Check if session is still valid
$stmt = $conn->prepare("SELECT session_status FROM webuser WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Check if session has been invalidated by admin (only check session_status, not session_id)
if (!$row || $row['session_status'] !== $sessionToken) {
    // Session has been invalidated, destroy the session
    session_unset();
    session_destroy();
    echo json_encode(['logged_out' => true]);
    exit;
}

echo json_encode(['logged_out' => false]);
