<?php
session_start();

// Check authentication
if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['a', 'sa'])) {
    header('Location: ../index.php');
    exit;
}

include('../config/conn.php');
require '../vendor-import-excel/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Handle Excel Export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    try {
        $result = $conn->query("SELECT title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site, created_at FROM Library_Deliveries ORDER BY created_at DESC");

        if ($result->num_rows === 0) {
            $_SESSION['error'][] = "No delivery records found to export.";
            header("Location: ../delivery/delivery.php");
            exit;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Maharlika Library System")
            ->setTitle("Library Deliveries Export")
            ->setSubject("Library Delivery Records")
            ->setDescription("Export of library delivery confirmation data");

        // Set headers
        $headers = [
            "Title and Grade Level",
            "Quantity Delivered",
            "Quantity Allocated",
            "Date of Delivery",
            "Name of School/Delivery Site",
            "Record Created"
        ];

        $sheet->fromArray([$headers], NULL, 'A1');

        // Style headers
        $headerRange = 'A1:F1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D3D3D3');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Add data
        $rowIndex = 2;
        while ($row = $result->fetch_assoc()) {
            $data = [
                $row['title_and_grade_level'],
                $row['quantity_delivered'],
                $row['quantity_allocated'],
                $row['date_of_delivery'],
                $row['name_of_school_delivery_site'],
                $row['created_at']
            ];
            $sheet->fromArray([$data], NULL, "A$rowIndex");

            // Style data rows with borders
            $dataRange = "A$rowIndex:F$rowIndex";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $rowIndex++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set response headers
        $filename = 'library_deliveries_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'][] = "Error exporting data: " . $e->getMessage();
        header('Location: ../delivery/delivery.php');
        exit;
    }
}

// Handle Excel Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    try {
        $uploadedFile = $_FILES['excel_file'];

        // Check for upload errors
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $uploadedFile['error']);
        }

        // Validate file type
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls'])) {
            throw new Exception('Invalid file type. Please upload .xlsx or .xls files only.');
        }

        // Check file size (limit to 5MB)
        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        // Load spreadsheet
        $spreadsheet = IOFactory::load($uploadedFile['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Remove header row
        $headers = array_shift($data);

        // Validate headers
        $expectedHeaders = ['Title and Grade Level', 'Quantity Delivered', 'Quantity Allocated', 'Date of Delivery', 'Name of School/Delivery Site'];
        $headerMatch = true;
        for ($i = 0; $i < count($expectedHeaders); $i++) {
            if (!isset($headers[$i]) || trim(strtolower($headers[$i])) !== strtolower($expectedHeaders[$i])) {
                $headerMatch = false;
                break;
            }
        }

        if (!$headerMatch) {
            throw new Exception('Invalid Excel format. Please ensure headers match the template: ' . implode(', ', $expectedHeaders));
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        $conn->begin_transaction();

        foreach ($data as $rowIndex => $row) {
            $actualRowNum = $rowIndex + 2; // +2 because we removed header and arrays are 0-indexed

            // Skip completely empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                // Validate required fields
                $title_grade = trim($row[0] ?? '');
                $qty_delivered = is_numeric($row[1]) ? (int)$row[1] : 0;
                $qty_allocated = is_numeric($row[2]) ? (int)$row[2] : 0;
                $delivery_date = trim($row[3] ?? '');
                $delivery_site = trim($row[4] ?? '');

                if (empty($title_grade)) {
                    throw new Exception("Row $actualRowNum: Title and Grade Level is required");
                }

                if (empty($delivery_site)) {
                    $delivery_site = 'MAHARLIKA NHS'; // Default value
                }

                // Validate and format date
                if (!empty($delivery_date)) {
                    // Try to parse various date formats
                    $date_obj = null;
                    $dateFormats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y-m-d H:i:s', 'm-d-Y', 'd-m-Y'];

                    foreach ($dateFormats as $format) {
                        $date_obj = DateTime::createFromFormat($format, $delivery_date);
                        if ($date_obj !== false) {
                            $delivery_date = $date_obj->format('Y-m-d');
                            break;
                        }
                    }

                    if ($date_obj === false) {
                        throw new Exception("Row $actualRowNum: Invalid date format. Use YYYY-MM-DD format");
                    }
                } else {
                    $delivery_date = date('Y-m-d'); // Default to today
                }

                // Insert data (without BookID since it's nullable)
                $stmt = $conn->prepare("INSERT INTO Library_Deliveries (title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("siiss", $title_grade, $qty_delivered, $qty_allocated, $delivery_date, $delivery_site);

                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Row $actualRowNum: " . $e->getMessage();

                // Stop processing if too many errors
                if ($errorCount > 50) {
                    $errors[] = "Too many errors. Import stopped.";
                    break;
                }
            }
        }

        if ($errorCount > 0 && $successCount === 0) {
            $conn->rollback();
            $_SESSION['error'] = $errors;
        } else {
            $conn->commit();
            $_SESSION['success'] = ["📊 Import completed: {$successCount} records imported successfully"];

            if ($errorCount > 0) {
                $_SESSION['error'] = array_slice($errors, 0, 10); // Show only first 10 errors
                if (count($errors) > 10) {
                    $_SESSION['error'][] = "... and " . (count($errors) - 10) . " more errors.";
                }
            }
        }
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $_SESSION['error'][] = "📤 Import error: " . $e->getMessage();
    }

    header('Location: ../delivery/delivery.php');
    exit;
}

// Handle template download
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Maharlika Library System")
            ->setTitle("Library Deliveries Import Template")
            ->setSubject("Template for importing library delivery data")
            ->setDescription("Use this template to import library delivery records");

        // Set headers
        $headers = [
            "Title and Grade Level",
            "Quantity Delivered",
            "Quantity Allocated",
            "Date of Delivery",
            "Name of School/Delivery Site"
        ];

        $sheet->fromArray([$headers], NULL, 'A1');

        // Add sample data
        $sampleData = [
            ["G4 - English", 25, 25, "2024-01-15", "MAHARLIKA NHS"],
            ["G7 - Math", 30, 30, "2024-01-16", "MAHARLIKA NHS"],
            ["SHS - Personal Development", 50, 50, "2024-01-17", "MAHARLIKA NHS"],
            ["G4 - Filipino", 0, 0, "", "MAHARLIKA NHS"],
            ["G4 - Science", 0, 0, "", "MAHARLIKA NHS"]
        ];

        $rowIndex = 2;
        foreach ($sampleData as $row) {
            $sheet->fromArray([$row], NULL, "A$rowIndex");
            $rowIndex++;
        }

        // Style headers
        $headerRange = 'A1:E1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4CAF50');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

        // Add borders to all data
        $dataRange = "A1:E$rowIndex";
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add instructions in comments
        $sheet->getComment('A1')->getText()->createTextRun('Fill in the delivery information for each book/grade level combination. Leave Quantity fields as 0 if no delivery occurred.');

        $filename = 'library_delivery_template.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'][] = "Error creating template: " . $e->getMessage();
        header('Location: ../delivery/delivery.php');
        exit;
    }
}

// If no action specified, redirect back
header('Location: ../delivery/delivery.php');
exit;
