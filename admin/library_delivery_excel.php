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

// Handle Excel Export with dynamic filtering
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    try {
        // Build dynamic query based on filters
        $query = "SELECT title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site, created_at FROM library_deliveries WHERE 1=1";
        $params = [];
        $types = "";

        // Date range filter
        if (!empty($_GET['start_date'])) {
            $query .= " AND date_of_delivery >= ?";
            $params[] = $_GET['start_date'];
            $types .= "s";
        }
        if (!empty($_GET['end_date'])) {
            $query .= " AND date_of_delivery <= ?";
            $params[] = $_GET['end_date'];
            $types .= "s";
        }

        // School/Delivery site filter
        if (!empty($_GET['delivery_site'])) {
            $query .= " AND name_of_school_delivery_site LIKE ?";
            $params[] = "%" . $_GET['delivery_site'] . "%";
            $types .= "s";
        }

        // Grade level filter
        if (!empty($_GET['grade_level'])) {
            $query .= " AND title_and_grade_level LIKE ?";
            $params[] = "%" . $_GET['grade_level'] . "%";
            $types .= "s";
        }

        // Quantity filter (only records with deliveries)
        if (isset($_GET['has_delivery']) && $_GET['has_delivery'] === '1') {
            $query .= " AND quantity_delivered > 0";
        }

        // Sorting
        $sortColumn = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';

        $allowedColumns = ['title_and_grade_level', 'quantity_delivered', 'quantity_allocated', 'date_of_delivery', 'name_of_school_delivery_site', 'created_at'];
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'created_at';
        }
        if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }

        $query .= " ORDER BY $sortColumn $sortOrder";

        // Execute query with parameters
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'][] = "No delivery records found matching the selected filters.";
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

        // Dynamic sheet title based on filters
        $sheetTitle = "Deliveries";
        if (!empty($_GET['start_date']) || !empty($_GET['end_date'])) {
            $sheetTitle .= " (" . ($_GET['start_date'] ?? 'All') . " to " . ($_GET['end_date'] ?? 'All') . ")";
        }
        $sheet->setTitle(substr($sheetTitle, 0, 31)); // Excel limit is 31 characters

        // Add filter summary at the top
        $filterRow = 1;
        if (!empty($_GET['start_date']) || !empty($_GET['end_date']) || !empty($_GET['delivery_site']) || !empty($_GET['grade_level'])) {
            $sheet->setCellValue('A1', 'Export Filters:');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $filterRow = 2;

            $filters = [];
            if (!empty($_GET['start_date'])) $filters[] = "From: " . $_GET['start_date'];
            if (!empty($_GET['end_date'])) $filters[] = "To: " . $_GET['end_date'];
            if (!empty($_GET['delivery_site'])) $filters[] = "Site: " . $_GET['delivery_site'];
            if (!empty($_GET['grade_level'])) $filters[] = "Grade: " . $_GET['grade_level'];
            if (isset($_GET['has_delivery']) && $_GET['has_delivery'] === '1') $filters[] = "With Deliveries Only";

            $sheet->setCellValue('B1', implode(' | ', $filters));
            $filterRow = 3;
        }

        // Set headers
        $headers = [
            "Title and Grade Level",
            "Quantity Delivered",
            "Quantity Allocated",
            "Date of Delivery",
            "Name of School/Delivery Site",
            "Record Created"
        ];

        $headerRow = $filterRow;
        $sheet->fromArray([$headers], NULL, "A$headerRow");

        // Style headers
        $headerRange = "A$headerRow:F$headerRow";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4CAF50');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Add data with statistics
        $rowIndex = $headerRow + 1;
        $totalDelivered = 0;
        $totalAllocated = 0;

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

            // Add conditional formatting for quantities
            $qtyDeliveredCell = "B$rowIndex";
            $qtyAllocatedCell = "C$rowIndex";

            if ($row['quantity_delivered'] > 0) {
                $sheet->getStyle($qtyDeliveredCell)->getFont()->getColor()->setRGB('006400'); // Dark green
            }

            if ($row['quantity_delivered'] < $row['quantity_allocated']) {
                $sheet->getStyle($qtyDeliveredCell)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFF9C4'); // Light yellow
            }

            // Style data rows with borders
            $dataRange = "A$rowIndex:F$rowIndex";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $totalDelivered += $row['quantity_delivered'];
            $totalAllocated += $row['quantity_allocated'];
            $rowIndex++;
        }

        // Add summary row
        $summaryRow = $rowIndex + 1;
        $sheet->setCellValue("A$summaryRow", "TOTAL");
        $sheet->setCellValue("B$summaryRow", $totalDelivered);
        $sheet->setCellValue("C$summaryRow", $totalAllocated);
        $sheet->setCellValue("D$summaryRow", "Records: " . ($rowIndex - $headerRow - 1));

        $summaryRange = "A$summaryRow:F$summaryRow";
        $sheet->getStyle($summaryRange)->getFont()->setBold(true);
        $sheet->getStyle($summaryRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle($summaryRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane("A" . ($headerRow + 1));

        // Generate dynamic filename
        $filename = 'library_deliveries_';
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $filename .= $_GET['start_date'] . '_to_' . $_GET['end_date'] . '_';
        }
        $filename .= date('Y-m-d_H-i-s') . '.xlsx';

        // Set response headers
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

// Handle Excel Import (keeping your existing import code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    try {
        $uploadedFile = $_FILES['excel_file'];

        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $uploadedFile['error']);
        }

        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls'])) {
            throw new Exception('Invalid file type. Please upload .xlsx or .xls files only.');
        }

        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        $spreadsheet = IOFactory::load($uploadedFile['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        $headers = array_shift($data);

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
            $actualRowNum = $rowIndex + 2;

            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $title_grade = trim($row[0] ?? '');
                $qty_delivered = is_numeric($row[1]) ? (int)$row[1] : 0;
                $qty_allocated = is_numeric($row[2]) ? (int)$row[2] : 0;
                $delivery_date = trim($row[3] ?? '');
                $delivery_site = trim($row[4] ?? '');

                if (empty($title_grade)) {
                    throw new Exception("Row $actualRowNum: Title and Grade Level is required");
                }

                if (empty($delivery_site)) {
                    $delivery_site = 'MAHARLIKA NHS';
                }

                if (!empty($delivery_date)) {
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
                    $delivery_date = date('Y-m-d');
                }

                $stmt = $conn->prepare("INSERT INTO Library_deliveries (title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("siiss", $title_grade, $qty_delivered, $qty_allocated, $delivery_date, $delivery_site);

                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Row $actualRowNum: " . $e->getMessage();

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
                $_SESSION['error'] = array_slice($errors, 0, 10);
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
// Handle template download with actual database data
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    try {
        // Fetch actual data from database
        $result = $conn->query("SELECT title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site FROM library_deliveries ORDER BY title_and_grade_level ASC");

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator("Maharlika Library System")
            ->setTitle("Library Deliveries Import Template")
            ->setSubject("Template for importing library delivery data")
            ->setDescription("Use this template to import library delivery records - Contains current database records");

        $headers = [
            "Title and Grade Level",
            "Quantity Delivered",
            "Quantity Allocated",
            "Date of Delivery",
            "Name of School/Delivery Site"
        ];

        $sheet->fromArray([$headers], NULL, 'A1');

        // Add actual data from database
        $rowIndex = 2;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data = [
                    $row['title_and_grade_level'],
                    $row['quantity_delivered'],
                    $row['quantity_allocated'],
                    $row['date_of_delivery'],
                    $row['name_of_school_delivery_site']
                ];
                $sheet->fromArray([$data], NULL, "A$rowIndex");
                $rowIndex++;
            }
        } else {
            // If no data exists, add sample rows as guidance
            $sampleData = [
                ["G11 - English", 0, 0, "", "MAHARLIKA NHS"],
                ["G7 - Math", 0, 0, "", "MAHARLIKA NHS"],
                ["SHS - Personal Development", 0, 0, "", "MAHARLIKA NHS"]
            ];

            foreach ($sampleData as $row) {
                $sheet->fromArray([$row], NULL, "A$rowIndex");
                $rowIndex++;
            }
        }

        // Style headers
        $headerRange = 'A1:E1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4CAF50');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

        // Add borders
        $dataRange = "A1:E" . ($rowIndex - 1);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add instruction comment
        $sheet->getComment('A1')->getText()->createTextRun('This template contains your current delivery records. You can edit them and re-import, or add new rows.');

        $filename = 'library_delivery_template_' . date('Y-m-d_H-i-s') . '.xlsx';
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
header('Location: ../delivery/delivery.php');
exit;
