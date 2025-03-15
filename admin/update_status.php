<?php
session_start();
include("../config/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = $_POST['reservation_id'];
    $newStatus = $_POST['status'];
    $dueDate = isset($_POST['due_date']) ? $_POST['due_date'] : null;

    // Get current status
    $checkStmt = $conn->prepare("SELECT STATUS FROM reservations WHERE id = ?");
    $checkStmt->bind_param("i", $reservationId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $currentStatus = $result->fetch_assoc()['STATUS'];
    $checkStmt->close();

    // Validate status transitions
    $validTransition = true;
    $message = '';

    switch ($newStatus) {
        case 'Approved':
            // Can only approve if current status is Pending
            if ($currentStatus !== 'Pending') {
                $validTransition = false;
                $message = 'Can only approve pending reservations';
            }
            break;
        case 'Rejected':
            // Can only reject if current status is Pending
            if ($currentStatus !== 'Pending') {
                $validTransition = false;
                $message = 'Can only reject pending reservations';
            }
            break;
        case 'Returned':
            // Can only mark as returned if current status is Approved
            if ($currentStatus !== 'Approved') {
                $validTransition = false;
                $message = 'Can only mark approved books as returned';
            }
            break;
    }

    if ($validTransition) {
        if ($newStatus === 'Approved') {
            // Set due date to 7 days from now
            $dueDate = date('Y-m-d', strtotime('+7 days'));
            $formattedDueDate = date('m-d-Y', strtotime($dueDate)); // Format for display

            $stmt = $conn->prepare("UPDATE reservations SET STATUS = ?, DueDate = ? WHERE id = ?");
            $stmt->bind_param("ssi", $newStatus, $dueDate, $reservationId);
        } else {
            // For other statuses, just update the status
            $stmt = $conn->prepare("UPDATE reservations SET STATUS = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $reservationId);
        }

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully',
                'dueDate' => $newStatus === 'Approved' ? $formattedDueDate : null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating status: ' . $stmt->error
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
