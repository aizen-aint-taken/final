<?php
session_start();
include("../config/conn.php");
require '../vendor-import-excel/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['success'])) $_SESSION['success'] = [];
if (!isset($_SESSION['exists'])) $_SESSION['exists'] = [];
if (!isset($_SESSION['error'])) $_SESSION['error'] = [];

if (isset($_POST['import'])) {
    $fileName = $_FILES['books']['name'];
    $fileTmp = $_FILES['books']['tmp_name'];
    $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

    if (!in_array($fileType, ['xlsx', 'xls'])) {
        $_SESSION['error'][] = "Invalid file type. Please upload an Excel file.";
        header('location:index.php');
        exit;
    }

    try {
        $spreadsheet = IOFactory::load($fileTmp);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        array_shift($rows); // Skip the header row

        $existingBooks = [];
        $result = $conn->query("SELECT Title, Author, Stock, BookID FROM books");
        while ($book = $result->fetch_assoc()) {
            $existingBooks[$book['Title'] . "|" . $book['Author']] = [
                'stock' => $book['Stock'],
                'id' => $book['BookID']
            ];
        }

        // Process data
        $values = [];
        $placeholders = [];
        $updates = [];
        $sourceOfAcquisitionLists = ["Government", "Private", "Donated", "Other", "Purchased"];

        foreach ($rows as $row) {
            // Handle standard book import format (7 columns)
            $row = array_pad($row, 7, ''); // Pad to 7 columns
            [$title, $author, $publisher, $sourceOfAcquisition, $publishDate, $language, $stock] = $row;

            if (empty($title) || empty($author) || empty($publisher) || empty($sourceOfAcquisition) || empty($publishDate) || empty($language) || !is_numeric($stock)) {
                $_SESSION['error'][] = "Invalid data in row: " . $sourceOfAcquisition . implode(", ", $row);
                continue;
            }

            $sourceOfAcquisition = ucfirst(strtolower(trim($sourceOfAcquisition)));
            if (!in_array($sourceOfAcquisition, $sourceOfAcquisitionLists)) {
                $_SESSION['error'][] = "Invalid source of acquisition in row: " . implode(", ", $row);
                continue;
            }

            try {
                $date = new DateTime($publishDate);
                $publishDate = $date->format('Y-m-d');
            } catch (Exception $e) {
                $_SESSION['error'][] = "Invalid date format in row: " . implode(", ", $row);
                continue;
            }

            $key = $title . "|" . $author;
            if (isset($existingBooks[$key])) {
                $newStock = $existingBooks[$key]['stock'] + $stock;
                $bookId = $existingBooks[$key]['id'];
                $updates[] = "UPDATE books SET Stock = $newStock WHERE BookID = $bookId";
                $_SESSION['success'][] = "Updated stock for existing book: $title";
                continue;
            }

            $values = array_merge($values, [$title, $author, $publisher, $sourceOfAcquisition, $publishDate, $language, $stock]);
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, NOW())";
        }

        foreach ($updates as $updateSql) {
            if (!$conn->query($updateSql)) {
                $_SESSION['error'][] = "Error updating stock: " . $conn->error;
            }
        }

        if (!empty($placeholders)) {
            $sql = "INSERT INTO books (Title, Author, Publisher, `Source of Acquisition`, PublishedDate, Subject, Stock, created_date) VALUES " . implode(", ", $placeholders);
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('s', count($values)), ...$values);

            if ($stmt->execute()) {
                $_SESSION['success'][] = "Books imported successfully with import tracking.";

                // Reorder all BookIDs sequentially
                $conn->query("SET @new_id = 0;");
                $conn->query("
                    UPDATE books
                    SET BookID = (@new_id := @new_id + 1)
                    ORDER BY BookID;
                ");

                // Reset AUTO_INCREMENT to next available ID
                $result = $conn->query("SELECT COUNT(*) AS total FROM books");
                $row = $result->fetch_assoc();
                $nextId = $row['total'] + 1;
                $conn->query("ALTER TABLE books AUTO_INCREMENT = $nextId");
            } else {
                $_SESSION['error'][] = "Error: " . $stmt->error;
            }
        }

        header('location:index.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'][] = "Error loading file: " . $e->getMessage();
        header('location:index.php');
        exit;
    }
}
