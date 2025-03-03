<?php
session_start();
include("../config/conn.php");

if (isset($_POST['update'])) {
    $bookID = $_POST['bookID'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $sourceOfAcquisition = $_POST['source'];
    $publishedDate = $_POST['publishedDate'];
    $language = $_POST['language'];
    $stock = $_POST['stock'];

    $sql = "UPDATE books 
            SET Title = ?, 
                Author = ?, 
                Publisher = ?, 
                `Source of Acquisition` = ?, 
                PublishedDate = ?, 
                Language = ?, 
                Stock = ? 
            WHERE BookID = ?";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssssi", $title, $author, $publisher, $sourceOfAcquisition, $publishedDate, $language, $stock, $bookID);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Book updated successfully.";
        } else {
            $_SESSION['error'] = "Error updating book: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Error preparing statement: " . $conn->error;
    }

    header("Location: ../admin/index.php");
    exit;
}
