<?php
session_start();
include("../config/conn.php");

// ... existing code ...
if (isset($_POST['update'])) {
    $bookID = (int)$_POST['bookID'];
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $sourceOfAcquisition = trim($_POST['source']);
    $publishedDate = trim($_POST['publishedDate']);
    $language = trim($_POST['language']);
    $stock = (int)$_POST['stock'];

    try {
        $stmt = $conn->prepare("UPDATE books SET Title = ?, Author = ?, Publisher = ?, `Source of Acquisition` = ?, PublishedDate = ?, Subject = ?, Stock = ?, stock_update = ? WHERE BookID = ?");
        $stmt->bind_param("ssssssiii", $title, $author, $publisher, $sourceOfAcquisition, $publishedDate, $language, $stock, $stock, $bookID);

        if ($stmt->execute()) {
            $_SESSION['success'] = ["Book updated successfully."];
        } else {
            $_SESSION['error'] = ["Error updating book: " . $stmt->error];
        }
    } catch (Exception $e) {
        $_SESSION['error'] = ["Error updating book: " . $e->getMessage()];
    }

    // Check if the referrer is from the admin panel or standalone Books page
    $redirectPage = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/admin/') !== false) ? '../admin/index.php' : '../categories/Books.php';
    header("Location: " . $redirectPage);
    exit;
}
// ... existing code ...
