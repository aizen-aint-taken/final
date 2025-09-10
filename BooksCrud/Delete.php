<?php
session_start();
include "../config/conn.php";

if (isset($_POST['deleteBook'])) {
    // Debug logging
    error_log("Delete request - deleteBook value: '" . $_POST['deleteBook'] . "' (type: " . gettype($_POST['deleteBook']) . ")");

    $bookId = (int)$_POST['deleteBook'];
    error_log("Converted to integer: " . $bookId);

    if ($bookId <= 0) {
        error_log("Invalid book ID detected: " . $bookId);
        $_SESSION['error'] = ["Invalid book ID. Received value: '" . $_POST['deleteBook'] . "' which converted to: " . $bookId];
        header("Location: ../admin/index.php");
        exit;
    }

    try {
        $conn->begin_transaction();

        // Check if book exists
        $checkStmt = $conn->prepare("SELECT Title FROM books WHERE BookID = ?");
        $checkStmt->bind_param("i", $bookId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = ["Book not found."];
            header("Location: ../admin/index.php");
            exit;
        }

        $bookTitle = $result->fetch_assoc()['Title'];

        // Delete the book
        $deleteStmt = $conn->prepare("DELETE FROM books WHERE BookID = ?");
        $deleteStmt->bind_param("i", $bookId);

        if ($deleteStmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = ["Book '" . htmlspecialchars($bookTitle) . "' deleted successfully."];
        } else {
            $conn->rollback();
            $_SESSION['error'] = ["Failed to delete the book. Please try again."];
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete book error: " . $e->getMessage());
        $_SESSION['error'] = ["An error occurred while deleting the book. Please try again."];
    }

    header("Location: ../admin/index.php");
    exit;
} else {
    $_SESSION['error'] = ["Invalid request."];
    header("Location: ../admin/index.php");
    exit;
}
