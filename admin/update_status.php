// include("../config/conn.php");

// if ($_SERVER["REQUEST_METHOD"] == "POST") {
// $reservationId = $_POST['reservation_id'];
// $newStatus = $_POST['status'];

// $stmt = $conn->prepare("UPDATE reservations SET STATUS = ? WHERE id = ?");
// $stmt->bind_param("si", $newStatus, $reservationId);

// if ($stmt->execute()) {
// echo json_encode(["success" => true]);
// } else {
// echo json_encode(["success" => false]);
// }

// $stmt->close();
// $conn->close();
// }



<?php
session_start();
include("../config/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = $_POST['reservation_id'];
    $newStatus = $_POST['status'];


    if ($newStatus === 'Approved') {

        $dueDate = date('Y-m-d', strtotime('+7 days'));


        $stmt = $conn->prepare("UPDATE reservations SET STATUS = ?, DueDate = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $dueDate, $reservationId);
    } else {

        $stmt = $conn->prepare("UPDATE reservations SET STATUS = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $reservationId);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
