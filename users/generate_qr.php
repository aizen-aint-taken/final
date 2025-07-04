<?php
session_start();
include("../config/conn.php");

header('Content-Type: image/png');

if (!isset($_SESSION['student_id'])) {
    http_response_code(403);
    echo 'Not authorized';
    exit;
}

$studentId = $_SESSION['student_id'];


$stmt = $conn->prepare("SELECT B.Title, R.DueDate FROM reservations R INNER JOIN books B ON R.BookID = B.BookID WHERE R.StudentID = ? AND R.STATUS = 'Approved' AND (R.DueDate IS NOT NULL AND R.DueDate >= CURDATE())");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = [
        'title' => $row['Title'],
        'due_date' => $row['DueDate']
    ];
}
$stmt->close();

if (count($books) <= 3) {
    http_response_code(400);
    echo 'QR code only available if you have more than 3 active borrowings.';
    exit;
}

$data = [
    'student_id' => $studentId,
    'books' => $books
];
$qrData = json_encode($data);

require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

$qr = Builder::create()
    ->writer(new PngWriter())
    ->data($qrData)
    ->encoding(new Encoding('UTF-8'))
    ->errorCorrectionLevel(ErrorCorrectionLevel::High)
    ->size(300)
    ->margin(10)
    ->build();

echo $qr->getString();
