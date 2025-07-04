<?php
session_start();
include("../config/conn.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['student_id']) || !isset($input['books']) || !is_array($input['books'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid QR data.']);
    exit;
}

$studentId = $input['student_id'];
$books = $input['books'];

$updated = 0;
foreach ($books as $book) {
    if (!isset($book['title']) || !isset($book['due_date'])) continue;

    $stmt = $conn->prepare("SELECT id FROM reservations R INNER JOIN books B ON R.BookID = B.BookID WHERE R.StudentID = ? AND B.Title = ? AND R.DueDate = ? AND R.STATUS = 'Approved'");
    $stmt->bind_param("iss", $studentId, $book['title'], $book['due_date']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $reservationId = $row['id'];

        $update = $conn->prepare("UPDATE reservations SET STATUS = 'Returned' WHERE id = ?");
        $update->bind_param("i", $reservationId);
        $update->execute();
        $update->close();
        $updated++;
    }
    $stmt->close();
}

if ($updated > 0) {
    echo json_encode(['success' => true, 'message' => "Marked $updated book(s) as returned."]);
} else {
    echo json_encode(['success' => false, 'message' => 'No matching reservations found or already returned.']);
}
