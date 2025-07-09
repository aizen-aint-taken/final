<?php
session_start();
require_once '../config/conn.php';

if (!isset($_SESSION['usertype']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ids']) || empty($data['ids'])) {
    echo json_encode(['success' => false, 'message' => 'No reservations selected']);
    exit;
}

try {

    $conn->begin_transaction();

    $ids = array_map('intval', $data['ids']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';

    $stmt = $conn->prepare("DELETE FROM reservations WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Reservations deleted successfully']);
    } else {
        throw new Exception('Failed to delete reservations');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
