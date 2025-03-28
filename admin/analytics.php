<?php
session_start();
include("../config/conn.php");


if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('location: ../index.php');
    exit;
}


$sql1 = "SELECT Subject, SUM(Stock) as total_stock FROM books GROUP BY Subject";
$result1 = $conn->query($sql1);
$bookStock = [];
while ($row = $result1->fetch_assoc()) {
    $bookStock[] = $row;
}


$sql2 = "SELECT b.Title, COUNT(r.BookID) as borrow_count 
         FROM reservations r 
         JOIN books b ON r.BookID = b.BookID 
         GROUP BY r.BookID 
         ORDER BY borrow_count DESC 
         LIMIT 5";
$result2 = $conn->query($sql2);
$topBorrowed = [];
while ($row = $result2->fetch_assoc()) {
    $topBorrowed[] = $row;
}


$sql3 = "SELECT STATUS, COUNT(*) as total FROM reservations GROUP BY STATUS";
$result3 = $conn->query($sql3);
$statusDistribution = [];
while ($row = $result3->fetch_assoc()) {
    $statusDistribution[] = $row;
}

$sql4 = "SELECT 
            DATE_FORMAT(ReserveDate, '%Y-%m') as month,
            COUNT(*) as reservation_count
         FROM reservations
         WHERE ReserveDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(ReserveDate, '%Y-%m')
         ORDER BY month ASC";
$result4 = $conn->query($sql4);
$monthlyTrends = [];
while ($row = $result4->fetch_assoc()) {
    $monthlyTrends[] = $row;
}


$borrowStats = [
    'approved_count' => 0,
    'returned_count' => 0
];

try {
    // Count approved books
    $stmt = $conn->prepare("SELECT COUNT(*) as approved_count 
                           FROM reservations 
                           WHERE STATUS = 'Approved'");
    $stmt->execute();
    $result = $stmt->get_result();
    $borrowStats['approved_count'] = $result->fetch_assoc()['approved_count'];

    // Count returned books
    $stmt = $conn->prepare("SELECT COUNT(*) as returned_count 
                           FROM reservations 
                           WHERE STATUS = 'Returned'");
    $stmt->execute();
    $result = $stmt->get_result();
    $borrowStats['returned_count'] = $result->fetch_assoc()['returned_count'];
} catch (Exception $e) {
    error_log("Error fetching borrow stats: " . $e->getMessage());
}


$data = [
    "bookStock" => $bookStock,
    "topBorrowed" => $topBorrowed,
    "statusDistribution" => $statusDistribution,
    "monthlyTrends" => $monthlyTrends,
    "borrowStats" => $borrowStats
];

echo json_encode($data);
$conn->close();
