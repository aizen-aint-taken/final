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


$studentName = null;
$studentStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$studentStmt->bind_param("i", $studentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
if ($row = $studentResult->fetch_assoc()) {
    $studentName = $row['name'];
}
$studentStmt->close();

$updated = 0;
foreach ($books as $book) {
    if (!isset($book['title']) || !isset($book['due_date'])) continue;

    $stmt = $conn->prepare("SELECT id FROM reservations R INNER JOIN books B ON R.BookID = B.BookID WHERE R.StudentID = ? AND B.Title = ? AND R.DueDate = ? AND R.STATUS = 'Borrowed'");
    $stmt->bind_param("iss", $studentId, $book['title'], $book['due_date']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $reservationId = $row['id'];

        $update = $conn->prepare("UPDATE reservations SET STATUS = 'Returned' WHERE id = ?");
        $update->bind_param("i", $reservationId);
        $update->execute();
        $update->close();


        $bookIdStmt = $conn->prepare("SELECT B.BookID FROM reservations R INNER JOIN books B ON R.BookID = B.BookID WHERE R.id = ?");
        $bookIdStmt->bind_param("i", $reservationId);
        $bookIdStmt->execute();
        $bookIdResult = $bookIdStmt->get_result();
        if ($bookIdRow = $bookIdResult->fetch_assoc()) {
            $bookId = $bookIdRow['BookID'];
            $incStock = $conn->prepare("UPDATE books SET Stock = Stock + 1 WHERE BookID = ?");
            $incStock->bind_param("i", $bookId);
            $incStock->execute();
            $incStock->close();
        }
        $bookIdStmt->close();
        $updated++;
    }
    $stmt->close();
}

if ($updated > 0) {
    echo json_encode(['success' => true, 'message' => "Marked $updated book(s) as returned.", 'student_name' => $studentName]);
} else {
    echo json_encode(['success' => false, 'message' => 'No matching reservations found or already returned.', 'student_name' => $studentName]);
}
