<?php
include "../config/conn.php";

if (isset($_POST['deleteBook'])) {
    $bookId = (int)$_POST['deleteBook'];

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("DELETE FROM books WHERE BookID = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();

        // Reset auto-increment safely
        $conn->query("SET @new_id = 0");
        $conn->query("UPDATE books SET BookID = (@new_id := @new_id + 1) ORDER BY BookID");

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM books");
        $stmt->execute();
        $rowCount = $stmt->get_result()->fetch_assoc()['total'];

        if ($rowCount == 0) {
            $conn->query("ALTER TABLE books AUTO_INCREMENT = 1");
        }

        $conn->commit();
        header("Location: ../admin/index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        die("An error occurred while deleting the book. Please try again later.");
    }
} else {
    die("Invalid request!");
}
