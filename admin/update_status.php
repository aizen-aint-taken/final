<?php
session_start();
include("../config/conn.php");

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = $_POST['reservation_id'];
    $newStatus = $_POST['status'];
    $previousStatus = $_POST['previous_status'];


    $validTransitions = [
        'Pending' => ['Borrowed', 'Rejected'],
        'Borrowed' => ['Returned'],
        'Rejected' => ['Pending'],
    ];


    if (
        !isset($validTransitions[$previousStatus]) ||
        !in_array($newStatus, $validTransitions[$previousStatus])
    ) {
        echo json_encode([
            'success' => false,
            'message' => "Invalid status transition from '$previousStatus' to '$newStatus'"
        ]);
        exit;
    }

    $conn->begin_transaction();

    try {
        if ($newStatus === 'Borrowed') {

            $dueDate = date('Y-m-d', strtotime('+7 days'));
            $updateStmt = $conn->prepare("UPDATE reservations SET STATUS = ?, DueDate = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $newStatus, $dueDate, $reservationId);
        } else {
            $updateStmt = $conn->prepare("UPDATE reservations SET STATUS = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newStatus, $reservationId);
        }
        $updateStmt->execute();


        if ($newStatus === 'Returned' && $previousStatus === 'Borrowed') {
            $bookStmt = $conn->prepare("SELECT BookID FROM reservations WHERE id = ?");
            $bookStmt->bind_param("i", $reservationId);
            $bookStmt->execute();
            $result = $bookStmt->get_result();
            $bookId = $result->fetch_assoc()['BookID'];

            $stockStmt = $conn->prepare("UPDATE books SET Stock = Stock + 1 WHERE BookID = ?");
            $stockStmt->bind_param("i", $bookId);
            $stockStmt->execute();
        }

        $conn->commit();

        $response = [
            'success' => true,
            'message' => 'Status updated successfully'
        ];

        if ($newStatus === 'Borrowed') {
            $response['dueDate'] = $dueDate;
            $response['message'] = 'Book borrowed. Due date set to ' . date('M d, Y', strtotime($dueDate));
        }

        echo json_encode($response);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
