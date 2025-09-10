<?php
session_start();
include "../config/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['author'], $_POST['publisher'], $_POST['sourceOfAcquisition'], $_POST['published_date'], $_POST['language'], $_POST['stock'])) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $sourceOfAcquisition = trim($_POST['sourceOfAcquisition']);
    $published_date = trim($_POST['published_date']);
    $language = trim($_POST['language']);
    $stock = (int)$_POST['stock'];

    // Validate input
    if (empty($title) || empty($author) || empty($publisher) || empty($sourceOfAcquisition) || empty($published_date) || empty($language) || $stock < 0) {
        $_SESSION['error'] = ["Invalid input. Please ensure all fields are filled correctly."];
        header("Location: ../admin/index.php");
        exit;
    }

    try {
        $conn->begin_transaction();

        // Check for duplicate books (same title, author, and publisher)
        $checkStmt = $conn->prepare("SELECT BookID FROM books WHERE LOWER(Title) = LOWER(?) AND LOWER(Author) = LOWER(?) AND LOWER(Publisher) = LOWER(?)");
        $checkStmt->bind_param("sss", $title, $author, $publisher);
        $checkStmt->execute();
        $duplicateResult = $checkStmt->get_result();

        if ($duplicateResult->num_rows > 0) {
            $_SESSION['exists'] = ["A book with the same title, author, and publisher already exists. You can edit the existing book instead."];
            header("Location: ../admin/index.php");
            exit;
        }

        // Insert the new book
        $stmt = $conn->prepare("INSERT INTO books (Title, Author, Publisher, `Source of Acquisition`, PublishedDate, Subject, Stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $title, $author, $publisher, $sourceOfAcquisition, $published_date, $language, $stock);

        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = ["Book '" . htmlspecialchars($title) . "' added successfully!"];
        } else {
            $conn->rollback();
            $_SESSION['error'] = ["Failed to add the book. Please try again."];
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Add book error: " . $e->getMessage());
        $_SESSION['error'] = ["An error occurred while adding the book. Please try again."];
    }

    header("Location: ../admin/index.php");
    exit;
} else {
    $_SESSION['error'] = ["Invalid request. Please fill all required fields."];
    header("Location: ../admin/index.php");
    exit;
}
