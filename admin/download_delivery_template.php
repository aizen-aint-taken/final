<?php
session_start();
require '../vendor-import-excel/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if user is admin or super admin
if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('location: ../index.php');
    exit;
}

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setCellValue('A1', 'Delivery Data Import Template');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

    // Instructions
    $sheet->setCellValue('A3', 'Instructions:');
    $sheet->setCellValue('A4', '1. Fill in the data starting from row 7');
    $sheet->setCellValue('A5', '2. Book ID must match existing books in the system');
    $sheet->setCellValue('A6', '3. Date format should be YYYY-MM-DD');

    // Headers
    $headers = ['Book ID', 'Grade Level', 'Quantity Delivered', 'Quantity Allocated', 'Delivery Date', 'Delivery Site'];
    $sheet->fromArray([$headers], NULL, 'A8');
    $sheet->getStyle('A8:F8')->getFont()->setBold(true);
    $sheet->getStyle('A8:F8')->getFill()->setFillType('solid')->getStartColor()->setRGB('D3D3D3');


    $sheet->fromArray($sampleData, NULL, 'A9');

    // Auto-size columns
    foreach (range('A', 'F') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Set download headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="delivery_import_template.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    $_SESSION['error'][] = "Error creating template: " . $e->getMessage();
    header("location: index.php");
    exit;
}
