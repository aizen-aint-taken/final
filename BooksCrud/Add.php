<?php
include "../config/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['author'], $_POST['publisher'], $_POST['sourceOfAcquisition'], $_POST['published_date'], $_POST['language'], $_POST['stock'])) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $sourceOfAcquisition = trim($_POST['sourceOfAcquisition']);
    $published_date = trim($_POST['published_date']);
    $language = trim($_POST['language']);
    $stock = (int)$_POST['stock'];

    if (empty($title) || empty($author) || empty($publisher) || empty($sourceOfAcquisition) || empty($published_date) || empty($language) || $stock < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please ensure all fields are filled correctly.']);
        exit;
    }

    try {
        $sql = "INSERT INTO books (Title, Author, Publisher, `Source of Acquisition`, PublishedDate, Subject, Stock) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$title, $author, $publisher, $sourceOfAcquisition, $published_date, $language, $stock])) {
            echo json_encode(['status' => 'success', 'message' => 'Book added successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add the book. Please try again.']);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while adding the book.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request!']);
}
