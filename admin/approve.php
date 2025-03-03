<?php
session_start();
include("../config/conn.php");



if (
    isset($_GET['reserve_id'])
    && is_numeric($_GET['reserve_id'])
) {
    $reserveId = $_GET['reserve_id'];
    // echo $reserveId;

    $stmt = $conn->prepare("UPDATE reservations SET STATUS = 'Approved' WHERE ID = ?");
    $stmt->bind_param("i", $reserveId);
    if ($stmt->execute()) {

        header("Location: ../admin/reservations.php");
        exit;
    } else {

        echo "Error updating the reservation status.";
    }
} else {

    echo "Invalid reservation ID.";
}
