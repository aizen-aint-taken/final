
<?php
session_start();
header('Content-Type: application/json');

$userEmail = $_SESSION['user'];
$sessionToken = $_SESSION['session_status'];

$stmt = $conn->prepare("SELECT session_status FROM webuser WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row || $row['session_status'] !== $sessionToken) {
    echo json_encode(['logged_out' => true]);
    exit;
}

echo json_encode(['logged_out' => false]);
