<?php
include '../config/conn.php';

$type = isset($_GET['type']) ? $_GET['type'] : null;
if ($type === null) {
    echo json_encode([]);
    exit;
}

// var_dump($type);
if ($type === 'monthly_reservations') {
    $sql = "SELECT DATE_FORMAT(ReserveDate, '%Y-%m') AS month, COUNT(*) AS total
            FROM reservations GROUP BY month ORDER BY month ASC";
} elseif ($type == 'stock_levels') {
    $sql = "SELECT Title, Stock FROM books";
} elseif ($type == 'user_activity') {
    $sql = "SELECT DATE(ReserveDate) AS date, COUNT(*) AS total
            FROM reservations GROUP BY date ORDER BY date ASC";
} elseif ($type === 'top_borrowed_books') {
    $sql = "SELECT books.Title, COUNT(reservations.BookID) AS total_borrowed
                FROM reservations JOIN books ON reservations.BookID = books.BookID
              GROUP BY reservations.BookID ORDER BY total_borrowed DESC LIMIT 5";
} else {
    echo json_encode([]);
    exit;
}


$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
