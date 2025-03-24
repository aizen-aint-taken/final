<?php
require_once '../config/conn.php';

header('Content-Type: application/json');

try {
    $query = "SELECT b.Title as title, COUNT(*) as count 
              FROM reservations r 
              JOIN books b ON r.BookID = b.BookID 
              GROUP BY b.BookID 
              ORDER BY count DESC 
              LIMIT 5";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'title' => $row['title'],
            'count' => (int)$row['count']
        ];
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
