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

// Fetch student name
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
$alreadyReturned = 0;

foreach ($books as $book) {
    if (!isset($book['title']) || !isset($book['due_date'])) continue;

    // Find reservation by title + due date
    $stmt = $conn->prepare("SELECT id, STATUS FROM reservations R 
                            INNER JOIN books B ON R.BookID = B.BookID 
                            WHERE R.StudentID = ? AND B.Title = ? AND R.DueDate = ?");
    $stmt->bind_param("iss", $studentId, $book['title'], $book['due_date']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $reservationId = $row['id'];
        $status = $row['STATUS'];

        if ($status === 'Borrowed') {

            date_default_timezone_set('Asia/Manila');
            $returnedDate = date('Y-m-d H:i:s');
            $update = $conn->prepare("UPDATE reservations SET STATUS = 'Returned', ReturnedDate = ? WHERE id = ?");
            $update->bind_param("si", $returnedDate, $reservationId);
            $update->execute();
            $update->close();

            // Increase book stock
            $bookIdStmt = $conn->prepare("SELECT B.BookID FROM reservations R 
                                          INNER JOIN books B ON R.BookID = B.BookID 
                                          WHERE R.id = ?");
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
        } elseif ($status === 'Returned') {
            $alreadyReturned++;
        }
    }

    $stmt->close();
}


if ($updated > 0) {
    echo json_encode([
        'success' => true,
        'message' => "Marked $updated book(s) as returned.",
        'student_name' => $studentName
    ]);
} elseif ($alreadyReturned > 0) {
    echo json_encode([
        'success' => false,
        'message' => "This book(s) is/are already returned by $studentName.",
        'student_name' => $studentName
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No matching borrowing book(s) found for this QR code.',
        'student_name' => $studentName
    ]);
}
