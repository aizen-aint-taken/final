<?php
session_start();
include("../config/conn.php");
require '../vendor-import-excel/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


try {

    $result = $conn->query("SELECT Title, Author, Publisher, `Source of Acquisition`, PublishedDate, Subject, Stock FROM books");


    if ($result->num_rows === 0) {
        $_SESSION['error'][] = "No books found to export.";
        header("location:../categories/Books.php");
        exit;
    }


    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();


    $headers = ["Title", "Author", "Publisher", "Source of Acquisition", "Published Date", "Subject", "Stock"];
    $sheet->fromArray([$headers], NULL, 'A1');


    $rowIndex = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->fromArray([$row], NULL, "A$rowIndex");
        $rowIndex++;
    }


    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="books_export.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    $_SESSION['error'][] = "Error exporting data: " . $e->getMessage();
    header("location:../categories/Books.php");
    exit;
}