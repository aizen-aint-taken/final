<?php
session_start();
require_once '../config/conn.php';
require '../vendor-import-excel/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if user is admin or super admin
if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listDeliveryRecords();
            break;

        case 'books':
            listBooks();
            break;

        case 'get':
            getDeliveryRecord();
            break;

        case 'add':
            addDeliveryRecord();
            break;

        case 'quick_add':
            quickAddDeliveryRecord();
            break;

        case 'update':
            updateDeliveryRecord();
            break;

        case 'delete':
            deleteDeliveryRecord();
            break;

        case 'import':
            importDeliveryData();
            break;

        case 'bulk_import':
            bulkImportDeliveryData();
            break;

        case 'export':
            exportDeliveryData();
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Delivery operations error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function listDeliveryRecords()
{
    global $conn;

    $query = "SELECT ld.DeliveryID, ld.BookID, b.Title, b.Author, ld.title_and_grade_level, 
                     ld.quantity_delivered, ld.quantity_allocated, ld.date_of_delivery, 
                     ld.name_of_school_delivery_site, ld.created_at
              FROM library_deliveries ld
              INNER JOIN books b ON ld.BookID = b.BookID
              ORDER BY ld.date_of_delivery DESC, ld.created_at DESC";

    $result = $conn->query($query);
    $records = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }

    echo json_encode(['success' => true, 'records' => $records]);
}

function listBooks()
{
    global $conn;

    $query = "SELECT BookID, Title, Author FROM books ORDER BY Title ASC";
    $result = $conn->query($query);
    $books = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }

    echo json_encode(['success' => true, 'books' => $books]);
}

function getDeliveryRecord()
{
    global $conn;

    $deliveryId = (int)($_GET['delivery_id'] ?? 0);
    if ($deliveryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid delivery ID']);
        return;
    }

    $stmt = $conn->prepare("SELECT ld.DeliveryID, ld.BookID, b.Title, b.Author, ld.title_and_grade_level, 
                                   ld.quantity_delivered, ld.quantity_allocated, ld.date_of_delivery, 
                                   ld.name_of_school_delivery_site 
                            FROM library_deliveries ld
                            INNER JOIN books b ON ld.BookID = b.BookID
                            WHERE ld.DeliveryID = ?");
    $stmt->bind_param("i", $deliveryId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $record = $result->fetch_assoc();
        echo json_encode(['success' => true, 'record' => $record]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
    }
}

function addDeliveryRecord()
{
    global $conn;

    $bookId = (int)($_POST['book_id'] ?? 0);
    $titleAndGradeLevel = trim($_POST['title_and_grade_level'] ?? '');
    $quantityDelivered = (int)($_POST['quantity_delivered'] ?? 0);
    $quantityAllocated = (int)($_POST['quantity_allocated'] ?? 0);
    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    $deliverySite = trim($_POST['delivery_site'] ?? 'MAHARLIKA NHS');

    if ($bookId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a book']);
        return;
    }

    if (empty($titleAndGradeLevel)) {
        echo json_encode(['success' => false, 'message' => 'Title and Grade Level is required']);
        return;
    }

    if ($quantityDelivered < 0 || $quantityAllocated < 0) {
        echo json_encode(['success' => false, 'message' => 'Quantities cannot be negative']);
        return;
    }

    try {
        $conn->begin_transaction();

        // Check for duplicate delivery records
        $checkStmt = $conn->prepare("SELECT DeliveryID FROM library_deliveries WHERE BookID = ? AND title_and_grade_level = ? AND date_of_delivery = ?");
        $checkStmt->bind_param("iss", $bookId, $titleAndGradeLevel, $deliveryDate);
        $checkStmt->execute();
        $duplicateResult = $checkStmt->get_result();

        if ($duplicateResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'A delivery record with the same book, grade level, and delivery date already exists']);
            return;
        }

        $stmt = $conn->prepare("INSERT INTO library_deliveries (BookID, title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiiss", $bookId, $titleAndGradeLevel, $quantityDelivered, $quantityAllocated, $deliveryDate, $deliverySite);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Delivery record added successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to add delivery record']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function updateDeliveryRecord()
{
    global $conn;

    $deliveryId = (int)($_POST['delivery_id'] ?? 0);
    $titleAndGradeLevel = trim($_POST['title_and_grade_level'] ?? '');
    $quantityDelivered = (int)($_POST['quantity_delivered'] ?? 0);
    $quantityAllocated = (int)($_POST['quantity_allocated'] ?? 0);
    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    $deliverySite = trim($_POST['delivery_site'] ?? 'MAHARLIKA NHS');

    if ($deliveryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid delivery ID']);
        return;
    }

    if (empty($titleAndGradeLevel)) {
        echo json_encode(['success' => false, 'message' => 'Title and Grade Level is required']);
        return;
    }

    if ($quantityDelivered < 0 || $quantityAllocated < 0) {
        echo json_encode(['success' => false, 'message' => 'Quantities cannot be negative']);
        return;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("UPDATE library_deliveries SET title_and_grade_level = ?, quantity_delivered = ?, quantity_allocated = ?, date_of_delivery = ?, name_of_school_delivery_site = ? WHERE DeliveryID = ?");
        $stmt->bind_param("siissi", $titleAndGradeLevel, $quantityDelivered, $quantityAllocated, $deliveryDate, $deliverySite, $deliveryId);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Delivery record updated successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to update delivery record']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function deleteDeliveryRecord()
{
    global $conn;

    $deliveryId = (int)($_POST['delivery_id'] ?? 0);
    if ($deliveryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid delivery ID']);
        return;
    }

    try {
        $conn->begin_transaction();

        // Delete the delivery record completely
        $stmt = $conn->prepare("DELETE FROM library_deliveries WHERE DeliveryID = ?");
        $stmt->bind_param("i", $deliveryId);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Delivery record deleted successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to delete delivery record']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function importDeliveryData()
{
    global $conn;

    if (!isset($_FILES['delivery_file']) || $_FILES['delivery_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        return;
    }

    $fileTmp = $_FILES['delivery_file']['tmp_name'];
    $fileName = $_FILES['delivery_file']['name'];
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

    if (!in_array(strtolower($fileExt), ['xlsx', 'xls'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Please upload .xlsx or .xls file']);
        return;
    }

    try {
        $spreadsheet = IOFactory::load($fileTmp);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        array_shift($rows);

        $imported = 0;
        $errors = [];

        $conn->begin_transaction();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because we removed header and arrays are 0-indexed

            // Expected columns: BookID, Grade Level, Quantity Delivered, Quantity Allocated, Delivery Date, Delivery Site
            if (count($row) < 6) {
                $errors[] = "Row $rowNum: Insufficient columns";
                continue;
            }

            $bookId = (int)($row[0] ?? 0);
            $gradeLevel = trim($row[1] ?? '');
            $quantityDelivered = (int)($row[2] ?? 0);
            $quantityAllocated = (int)($row[3] ?? 0);
            $deliveryDate = !empty($row[4]) ? date('Y-m-d', strtotime($row[4])) : null;
            $deliverySite = trim($row[5] ?? 'MAHARLIKA NHS');

            if ($bookId <= 0) {
                $errors[] = "Row $rowNum: Invalid Book ID";
                continue;
            }

            if ($quantityDelivered < 0 || $quantityAllocated < 0) {
                $errors[] = "Row $rowNum: Quantities cannot be negative";
                continue;
            }

            // Check if book exists
            $checkStmt = $conn->prepare("SELECT BookID FROM books WHERE BookID = ?");
            $checkStmt->bind_param("i", $bookId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows === 0) {
                $errors[] = "Row $rowNum: Book ID $bookId not found";
                continue;
            }


            $imported++;
        }

        $conn->commit();

        $message = "Import completed. $imported records imported.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " errors occurred.";
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'imported_count' => $imported,
            'errors' => $errors
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function exportDeliveryData()
{
    global $conn;

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="delivery_records_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $includeEmpty = isset($_GET['include_empty']) && $_GET['include_empty'] == '1';

    $query = "SELECT ld.DeliveryID, ld.BookID, ld.title_and_grade_level,
                     ld.quantity_delivered, ld.quantity_allocated,
                     ld.date_of_delivery, ld.name_of_school_delivery_site,
                     b.Title, b.Author
              FROM library_deliveries ld
              INNER JOIN books b ON ld.BookID = b.BookID";

    if (!$includeEmpty) {
        $query .= " WHERE ld.quantity_delivered > 0 OR ld.quantity_allocated > 0";
    }

    $query .= " ORDER BY ld.date_of_delivery DESC, ld.created_at DESC";

    $result = $conn->query($query);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setCellValue('A1', '📚 Library Delivery Records');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

    // District and School info
    $sheet->setCellValue('A3', '🏢 District: BISLIG 2A');
    $sheet->setCellValue('A4', '🏫 School: MAHARLIKA NATIONAL HIGH SCHOOL');
    $sheet->setCellValue('A5', '📅 Export Date: ' . date('Y-m-d H:i:s'));

    // Headers
    $headers = ['Delivery ID', 'Title and Grade Level', 'Quantity Delivered', 'Quantity Allocated', 'Date of Delivery', 'Name of School/Delivery Site'];
    $sheet->fromArray([$headers], NULL, 'A7');
    $sheet->getStyle('A7:F7')->getFont()->setBold(true);

    // Data
    $rowIndex = 8;
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sheet->fromArray([
                $row['DeliveryID'],
                $row['title_and_grade_level'],
                $row['quantity_delivered'] ?: '',
                $row['quantity_allocated'] ?: '',
                $row['date_of_delivery'] ? date('m-d-Y', strtotime($row['date_of_delivery'])) : '',
                $row['name_of_school_delivery_site'] ?: ''
            ], NULL, "A$rowIndex");
            $rowIndex++;
        }
    }

    // Auto-size columns
    foreach (range('A', 'F') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function quickAddDeliveryRecord()
{
    global $conn;

    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? 'Unknown Author');
    $subject = trim($_POST['subject'] ?? 'General');
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $quantityDelivered = (int)($_POST['quantity_delivered'] ?? 0);
    $quantityAllocated = (int)($_POST['quantity_allocated'] ?? 0);
    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    $deliverySite = trim($_POST['delivery_site'] ?? 'MAHARLIKA NHS');

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }

    if ($quantityDelivered <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity delivered must be greater than 0']);
        return;
    }

    if ($quantityDelivered < 0 || $quantityAllocated < 0) {
        echo json_encode(['success' => false, 'message' => 'Quantities cannot be negative']);
        return;
    }

    try {
        $conn->begin_transaction();

        // Check if book already exists
        $checkStmt = $conn->prepare("SELECT BookID FROM books WHERE LOWER(Title) = LOWER(?) AND LOWER(Author) = LOWER(?)");
        $checkStmt->bind_param("ss", $title, $author);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // 📚 Use existing book
            $existingBook = $checkResult->fetch_assoc();
            $bookId = $existingBook['BookID'];
        } else {
            // 📖 Create new book first
            $insertBookStmt = $conn->prepare("INSERT INTO books (Title, Author, Publisher, `Source of Acquisition`, PublishedDate, Subject, Stock) VALUES (?, ?, 'Unknown', 'Manual Entry', CURDATE(), ?, ?)");
            $insertBookStmt->bind_param("sssi", $title, $author, $subject, $quantityDelivered);

            if (!$insertBookStmt->execute()) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to create book record']);
                return;
            }
            $bookId = $conn->insert_id;
        }

        // 📝 Create title and grade level combination
        $titleAndGradeLevel = $title . ($gradeLevel ? ' - ' . $gradeLevel : '');

        // 🚚 Check for duplicate delivery records
        $checkDeliveryStmt = $conn->prepare("SELECT DeliveryID FROM library_deliveries WHERE BookID = ? AND title_and_grade_level = ? AND date_of_delivery = ?");
        $checkDeliveryStmt->bind_param("iss", $bookId, $titleAndGradeLevel, $deliveryDate);
        $checkDeliveryStmt->execute();
        $duplicateDeliveryResult = $checkDeliveryStmt->get_result();

        if ($duplicateDeliveryResult->num_rows > 0) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => '⚠️ A delivery record with the same details already exists']);
            return;
        }

        // 🎯 Insert delivery record
        $insertDeliveryStmt = $conn->prepare("INSERT INTO library_deliveries (BookID, title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site) VALUES (?, ?, ?, ?, ?, ?)");
        $insertDeliveryStmt->bind_param("isiiss", $bookId, $titleAndGradeLevel, $quantityDelivered, $quantityAllocated, $deliveryDate, $deliverySite);

        if ($insertDeliveryStmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => '✅ Delivery record added successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => '❌ Failed to add delivery record']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function bulkImportDeliveryData()
{
    global $conn;

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        return;
    }

    $fileTmp = $_FILES['excel_file']['tmp_name'];
    $fileName = $_FILES['excel_file']['name'];
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

    if (!in_array(strtolower($fileExt), ['xlsx', 'xls'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Please upload .xlsx or .xls file']);
        return;
    }

    $skipDuplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] == 'on';
    $updateExisting = isset($_POST['update_existing']) && $_POST['update_existing'] == 'on';
    $defaultDeliverySite = trim($_POST['default_delivery_site'] ?? 'MAHARLIKA NHS');

    try {
        $spreadsheet = IOFactory::load($fileTmp);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        array_shift($rows);

        $processed = 0;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $conn->begin_transaction();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because we removed header and arrays are 0-indexed
            $processed++;

            // Expected columns: Title, Grade Level, Quantity Delivered, Quantity Allocated, Delivery Date, Delivery Site, Author, Subject
            if (count($row) < 3) {
                $errors++;
                continue;
            }

            $title = trim($row[0] ?? '');
            $gradeLevel = trim($row[1] ?? '');
            $quantityDelivered = (int)($row[2] ?? 0);
            $quantityAllocated = (int)($row[3] ?? 0);
            $deliveryDate = !empty($row[4]) ? date('Y-m-d', strtotime($row[4])) : null;
            $deliverySite = trim($row[5] ?? $defaultDeliverySite);
            $author = trim($row[6] ?? 'Unknown Author');
            $subject = trim($row[7] ?? 'General');

            if (empty($title)) {
                $errors++;
                continue;
            }

            if ($quantityDelivered <= 0) {
                $errors++;
                continue;
            }

            // 📚 Create title and grade level combination
            $titleAndGradeLevel = $title . ($gradeLevel ? ' - ' . $gradeLevel : '');

            // Check if book already exists
            $checkStmt = $conn->prepare("SELECT BookID FROM books WHERE LOWER(Title) = LOWER(?) AND LOWER(Author) = LOWER(?)");
            $checkStmt->bind_param("ss", $title, $author);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // 📚 Use existing book
                $existingBook = $checkResult->fetch_assoc();
                $bookId = $existingBook['BookID'];
            } else {
                // 📖 Create new book first
                $insertBookStmt = $conn->prepare("INSERT INTO books (Title, Author, Publisher, `Source of Acquisition`, PublishedDate, Subject, Stock) VALUES (?, ?, 'Unknown', 'Bulk Import', CURDATE(), ?, ?)");
                $insertBookStmt->bind_param("sssi", $title, $author, $subject, $quantityDelivered);

                if (!$insertBookStmt->execute()) {
                    $errors++;
                    continue;
                }
                $bookId = $conn->insert_id;
            }

            // 🚚 Check for duplicate delivery records
            $checkDeliveryStmt = $conn->prepare("SELECT DeliveryID FROM Library_deliveries WHERE BookID = ? AND title_and_grade_level = ? AND date_of_delivery = ?");
            $checkDeliveryStmt->bind_param("iss", $bookId, $titleAndGradeLevel, $deliveryDate);
            $checkDeliveryStmt->execute();
            $duplicateDeliveryResult = $checkDeliveryStmt->get_result();

            if ($duplicateDeliveryResult->num_rows > 0) {
                if ($skipDuplicates && !$updateExisting) {
                    $skipped++;
                    continue;
                }

                if ($updateExisting) {
                    // Update existing delivery record
                    $existingDelivery = $duplicateDeliveryResult->fetch_assoc();
                    $deliveryId = $existingDelivery['DeliveryID'];

                    $updateDeliveryStmt = $conn->prepare("UPDATE library_deliveries SET quantity_delivered = ?, quantity_allocated = ?, date_of_delivery = ?, name_of_school_delivery_site = ? WHERE DeliveryID = ?");
                    $updateDeliveryStmt->bind_param("iissi", $quantityDelivered, $quantityAllocated, $deliveryDate, $deliverySite, $deliveryId);

                    if ($updateDeliveryStmt->execute()) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                } else {
                    $skipped++;
                }
            } else {
                // 🎯 Insert new delivery record
                $insertDeliveryStmt = $conn->prepare("INSERT INTO library_deliveries (BookID, title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site) VALUES (?, ?, ?, ?, ?, ?)");
                $insertDeliveryStmt->bind_param("isiiss", $bookId, $titleAndGradeLevel, $quantityDelivered, $quantityAllocated, $deliveryDate, $deliverySite);

                if ($insertDeliveryStmt->execute()) {
                    $imported++;
                } else {
                    $errors++;
                }
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "Bulk import completed successfully",
            'processed' => $processed,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
