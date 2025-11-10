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
        $allValues = [];
        $placeholders = [];
        $updates = [];
        $sourceOfAcquisitionLists = ["Government", "Private", "Donated", "Other", "Purchased"];

        foreach ($rows as $row) {
            // Handle standard book import format (7 columns)
            $row = array_pad($row, 7, ''); // Pad to 7 columns
            [$title, $author, $publisher, $sourceOfAcquisition, $publishDate, $language, $stock] = $row;

            if (empty($title) || empty($author) || empty($publisher) || empty($sourceOfAcquisition) || empty($publishDate) || empty($language) || !is_numeric($stock)) {
                $_SESSION['error'][] = "Invalid data in row: " . implode(", ", $row);
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
                $updates[] = "UPDATE books SET Stock = $newStock, stock_update = COALESCE(stock_update, Stock) WHERE BookID = $bookId";
                $_SESSION['success'][] = "Updated stock for existing book: $title";
                continue;
            }

            // For new books, we need 8 values: Title, Author, Publisher, Source, PublishDate, Subject, Stock, stock_update
            $allValues[] = $title;
            $allValues[] = $author;
            $allValues[] = $publisher;
            $allValues[] = $sourceOfAcquisition;
            $allValues[] = $publishDate;
            $allValues[] = $language;
            $allValues[] = (string)$stock; // Stock
            $allValues[] = (string)$stock; // stock_update (same as initial stock)
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?)";
        }

        foreach ($updates as $updateSql) {
            if (!$conn->query($updateSql)) {
                $_SESSION['error'][] = "Error updating stock: " . $conn->error;
            }
        }

        if (!empty($placeholders)) {
            $sql = "INSERT INTO books (Title, Author, Publisher, `Source of Acquisition`, PublishedDate, Subject, Stock, stock_update) VALUES " . implode(", ", $placeholders);
            $stmt = $conn->prepare($sql);

            // Create the type string for bind_param - 8 strings per book (all values are strings)
            $types = str_repeat('ssssssss', count($placeholders)); // 8 's' per book
            
            if (count($allValues) > 0) {
                $stmt->bind_param($types, ...$allValues);

                if ($stmt->execute()) {
                    $_SESSION['success'][] = "Books imported successfully with import tracking.";

                 
                    $conn->query("UPDATE books SET created_date = NOW() WHERE created_date IS NULL");
                    
                    
                    
                } else {
                    $_SESSION['error'][] = "Error: " . $stmt->error;
                }
            }
        }

        // Ensure all books have stock_update initialized
        $conn->query("UPDATE books SET stock_update = Stock WHERE stock_update IS NULL");

        header('location:index.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'][] = "Error loading file: " . $e->getMessage();
        header('location:index.php');
        exit;
    }
}
?>
