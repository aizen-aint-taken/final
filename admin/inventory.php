<?php
session_start();

// Check authentication
if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['a', 'sa'])) {
    header('Location: ../index.php');
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../config/conn.php');

// Handle AJAX requests for data fetching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'get_inventory_stats':
            $stats = getInventoryStatistics($conn);
            echo json_encode(['success' => true, 'data' => $stats]);
            exit;

        case 'get_borrowed_books':
            $borrowedBooks = getBorrowedBooks($conn);
            echo json_encode(['success' => true, 'data' => $borrowedBooks]);
            exit;

        case 'get_low_stock_books':
            $lowStockBooks = getLowStockBooks($conn);
            echo json_encode(['success' => true, 'data' => $lowStockBooks]);
            exit;

        case 'get_delivery_summary':
            $deliverySummary = getDeliverySummary($conn);
            echo json_encode(['success' => true, 'data' => $deliverySummary]);
            exit;

        case 'get_recent_transactions':
            $transactions = getRecentTransactions($conn);
            echo json_encode(['success' => true, 'data' => $transactions]);
            exit;

        case 'get_import_history':
            $importHistory = getImportHistory($conn);
            echo json_encode(['success' => true, 'data' => $importHistory]);
            exit;

        case 'get_books_with_import_details':
            $booksWithImports = getBooksWithImportDetails($conn);
            echo json_encode(['success' => true, 'data' => $booksWithImports]);
            exit;

        case 'get_detailed_borrower_history':
            $borrowerHistory = getDetailedBorrowerHistory($conn);
            echo json_encode(['success' => true, 'data' => $borrowerHistory]);
            exit;

        case 'get_most_borrowed_books':
            $mostBorrowedBooks = getMostBorrowedBooks($conn);
            echo json_encode(['success' => true, 'data' => $mostBorrowedBooks]);
            exit;

        case 'get_import_delivery_stamps':
            $importDeliveryStamps = getImportDeliveryStamps($conn);
            echo json_encode(['success' => true, 'data' => $importDeliveryStamps]);
            exit;
    }
}

// Functions for data retrieval
function getInventoryStatistics($conn)
{
    $stats = [];

    // Total books and stock
    $result = $conn->query("SELECT COUNT(*) as total_books, COALESCE(SUM(Stock), 0) as total_stock FROM books");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_books'] = $row['total_books'] ?? 0;
        $stats['total_stock'] = $row['total_stock'] ?? 0;
    } else {
        $stats['total_books'] = 0;
        $stats['total_stock'] = 0;
    }

    // Books by acquisition source
    $result = $conn->query("SELECT `Source of Acquisition` as source, COUNT(*) as count, COALESCE(SUM(Stock), 0) as total_stock FROM books GROUP BY `Source of Acquisition`");
    $stats['books_by_source'] = [];
    $stats['books_imported_via_excel'] = 0;
    $stats['books_government'] = 0;
    $stats['books_donated'] = 0;
    $stats['books_purchased'] = 0;

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats['books_by_source'][] = $row;
            switch (strtolower($row['source'])) {
                case 'government':
                    $stats['books_government'] = $row['count'];
                    break;
                case 'donated':
                    $stats['books_donated'] = $row['count'];
                    break;
                case 'purchased':
                    $stats['books_purchased'] = $row['count'];
                    break;
            }
        }
    }

    // Books imported via Excel (assuming books added in bulk are from Excel imports)
    // This can be enhanced by adding an import_date column to books table
    $result = $conn->query("SELECT DATE(created_date) as import_date, COUNT(*) as daily_imports FROM books WHERE created_date IS NOT NULL GROUP BY DATE(created_date) HAVING daily_imports > 5 ORDER BY import_date DESC");
    if ($result && $result->num_rows > 0) {
        $stats['excel_imports'] = $result->fetch_all(MYSQLI_ASSOC);
        $totalImported = 0;
        foreach ($stats['excel_imports'] as $import) {
            $totalImported += $import['daily_imports'];
        }
        $stats['books_imported_via_excel'] = $totalImported;
    } else {
        $stats['excel_imports'] = [];
        // Fallback: estimate based on acquisition source patterns
        $result = $conn->query("SELECT COUNT(*) as estimated_imports FROM books WHERE `Source of Acquisition` IN ('Government', 'Purchased')");
        $stats['books_imported_via_excel'] = $result ? $result->fetch_assoc()['estimated_imports'] : 0;
    }

    // Books by status
    $result = $conn->query("SELECT COUNT(*) as available_books FROM books WHERE Stock > 0");
    $stats['available_books'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['available_books'] : 0;

    $result = $conn->query("SELECT COUNT(*) as out_of_stock FROM books WHERE Stock = 0");
    $stats['out_of_stock'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['out_of_stock'] : 0;

    // Low stock books (less than 5)
    $result = $conn->query("SELECT COUNT(*) as low_stock FROM books WHERE Stock > 0 AND Stock < 5");
    $stats['low_stock'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['low_stock'] : 0;

    // Check if reservations table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'reservations'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Reservation statistics
        $result = $conn->query("SELECT COUNT(*) as total_reservations FROM reservations");
        $stats['total_reservations'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['total_reservations'] : 0;

        $result = $conn->query("SELECT COUNT(*) as pending_reservations FROM reservations WHERE STATUS = 'Pending'");
        $stats['pending_reservations'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['pending_reservations'] : 0;

        $result = $conn->query("SELECT COUNT(*) as borrowed_books FROM reservations WHERE STATUS = 'Borrowed'");
        $stats['borrowed_books'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['borrowed_books'] : 0;

        $result = $conn->query("SELECT COUNT(*) as returned_books FROM reservations WHERE STATUS = 'Returned'");
        $stats['returned_books'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['returned_books'] : 0;

        // Overdue books
        $result = $conn->query("SELECT COUNT(*) as overdue_books FROM reservations WHERE STATUS = 'Borrowed' AND DueDate < CURDATE()");
        $stats['overdue_books'] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['overdue_books'] : 0;
    } else {
        // No reservations table, set defaults
        $stats['total_reservations'] = 0;
        $stats['pending_reservations'] = 0;
        $stats['borrowed_books'] = 0;
        $stats['returned_books'] = 0;
        $stats['overdue_books'] = 0;
    }

    // Check if library_deliveries table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'library_deliveries'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Delivery statistics
        $result = $conn->query("SELECT COUNT(*) as total_deliveries, COALESCE(SUM(quantity_delivered), 0) as total_delivered FROM library_deliveries");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['total_deliveries'] = $row['total_deliveries'] ?? 0;
            $stats['total_delivered'] = $row['total_delivered'] ?? 0;
        } else {
            $stats['total_deliveries'] = 0;
            $stats['total_delivered'] = 0;
        }
    } else {
        $stats['total_deliveries'] = 0;
        $stats['total_delivered'] = 0;
    }

    return $stats;
}

function getBorrowedBooks($conn)
{
    // Check if tables exist first
    $reservationsExists = $conn->query("SHOW TABLES LIKE 'reservations'");
    $usersExists = $conn->query("SHOW TABLES LIKE 'users'");

    if (!$reservationsExists || $reservationsExists->num_rows == 0 || !$usersExists || $usersExists->num_rows == 0) {
        return []; // Return empty array if tables don't exist
    }

    $query = "SELECT r.id, b.BookID, b.Title, b.Author, b.Publisher, b.Subject,
                     b.`Source of Acquisition` as acquisition_source,
                     u.name as student_name, u.email as student_email, u.year, u.sect, u.advicer, 
                     r.ReserveDate, r.DueDate, r.STATUS,
                     DATEDIFF(r.DueDate, CURDATE()) as days_left,
                     DATEDIFF(CURDATE(), r.ReserveDate) as days_borrowed,
                     CASE 
                         WHEN r.DueDate < CURDATE() THEN 'Overdue'
                         WHEN DATEDIFF(r.DueDate, CURDATE()) <= 3 THEN 'Due Soon'
                         ELSE 'Active'
                     END as borrow_status
              FROM reservations r 
              JOIN books b ON r.BookID = b.BookID 
              JOIN users u ON r.StudentID = u.id 
              WHERE r.STATUS = 'Borrowed'
              ORDER BY r.DueDate ASC, r.ReserveDate DESC";

    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getLowStockBooks($conn)
{
    $query = "SELECT BookID, Title, Author, Publisher, Subject, Stock, 
                     `Source of Acquisition` as source
              FROM books 
              WHERE Stock > 0 AND Stock < 5
              ORDER BY Stock ASC, Title ASC";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getDeliverySummary($conn)
{
    $query = "SELECT ld.DeliveryID, ld.title_and_grade_level, ld.quantity_delivered, 
                     ld.quantity_allocated, ld.date_of_delivery, ld.name_of_school_delivery_site,
                     b.Title, b.Author
              FROM library_deliveries ld
              LEFT JOIN books b ON ld.BookID = b.BookID
              ORDER BY ld.date_of_delivery DESC
              LIMIT 20";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getRecentTransactions($conn)
{
    $query = "SELECT it.transaction_id, it.transaction_type, it.quantity, it.notes,
                     it.transaction_date, b.Title, b.Author, u.name as user_name
              FROM inventory_transactions it
              JOIN books b ON it.BookID = b.BookID
              LEFT JOIN users u ON it.user_id = u.id
              ORDER BY it.transaction_date DESC
              LIMIT 15";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getImportHistory($conn)
{
    // Check if created_date column exists
    $columnCheck = $conn->query("SHOW COLUMNS FROM books LIKE 'created_date'");
    if (!$columnCheck || $columnCheck->num_rows == 0) {
        return []; // Return empty array if column doesn't exist
    }

    // Get books imported with their details
    $query = "SELECT 
                DATE(created_date) as import_date,
                COUNT(*) as books_imported,
                GROUP_CONCAT(DISTINCT `Source of Acquisition`) as sources,
                SUM(Stock) as total_stock_added,
                MIN(created_date) as first_import_time,
                MAX(created_date) as last_import_time
              FROM books 
              WHERE created_date IS NOT NULL
              GROUP BY DATE(created_date)
              HAVING books_imported > 1
              ORDER BY import_date DESC
              LIMIT 20";

    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getBooksWithImportDetails($conn)
{
    $query = "SELECT 
                b.BookID, b.Title, b.Author, b.Publisher, b.Subject,
                b.Stock, b.`Source of Acquisition` as source,
                b.created_date as import_date,
                CASE 
                    WHEN b.created_date IS NOT NULL THEN 'Imported'
                    ELSE 'Manual Entry'
                END as entry_method,
                COUNT(r.id) as times_borrowed,
                COUNT(CASE WHEN r.STATUS = 'Borrowed' THEN 1 END) as currently_borrowed
              FROM books b
              LEFT JOIN reservations r ON b.BookID = r.BookID
              GROUP BY b.BookID, b.Title, b.Author, b.Publisher, b.Subject, b.Stock, b.`Source of Acquisition`, b.created_date
              ORDER BY b.created_date DESC, b.Title ASC";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getDetailedBorrowerHistory($conn)
{
    $query = "SELECT 
                u.id as student_id, u.name as student_name, u.email, u.year, u.sect, u.advicer,
                COUNT(r.id) as total_borrowings,
                COUNT(CASE WHEN r.STATUS = 'Borrowed' THEN 1 END) as current_borrowings,
                COUNT(CASE WHEN r.STATUS = 'Returned' THEN 1 END) as returned_books,
                COUNT(CASE WHEN r.STATUS = 'Borrowed' AND r.DueDate < CURDATE() THEN 1 END) as overdue_books,
                MIN(r.ReserveDate) as first_borrowing,
                MAX(r.ReserveDate) as last_borrowing,
                GROUP_CONCAT(DISTINCT b.Title ORDER BY r.ReserveDate DESC SEPARATOR '; ') as recent_books
              FROM users u
              JOIN reservations r ON u.id = r.StudentID
              JOIN books b ON r.BookID = b.BookID
              WHERE u.usertype = 's'
              GROUP BY u.id, u.name, u.email, u.year, u.sect, u.advicer
              ORDER BY total_borrowings DESC, last_borrowing DESC";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// New function to get most borrowed books
function getMostBorrowedBooks($conn)
{
    $query = "SELECT 
                b.Title, 
                b.Author, 
                COUNT(r.BookID) as borrow_count,
                b.Stock,
                CASE 
                    WHEN b.Stock > 5 THEN 'Available'
                    WHEN b.Stock > 0 THEN 'Limited'
                    ELSE 'Out of Stock'
                END as status
              FROM books b
              LEFT JOIN reservations r ON b.BookID = r.BookID
              WHERE r.STATUS IN ('Borrowed', 'Returned')
              GROUP BY b.BookID, b.Title, b.Author, b.Stock
              ORDER BY borrow_count DESC, b.Title ASC
              LIMIT 5";

    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// New function to get import/delivery history stamps
function getImportDeliveryStamps($conn)
{
    $stamps = [];

    // Get recent book imports with more details
    $importQuery = "SELECT 
                      'import' as type,
                      DATE(created_date) as date,
                      COUNT(*) as count,
                      GROUP_CONCAT(DISTINCT `Source of Acquisition`) as details,
                      GROUP_CONCAT(DISTINCT Title SEPARATOR '; ') as titles
                    FROM books 
                    WHERE created_date IS NOT NULL
                    GROUP BY DATE(created_date)
                    ORDER BY created_date DESC
                    LIMIT 5";

    $importResult = $conn->query($importQuery);
    if ($importResult) {
        while ($row = $importResult->fetch_assoc()) {
            $stamps['imports'][] = $row;
        }
    }

    // Get recent deliveries with book titles and quantities
    $deliveryQuery = "SELECT 
                        'delivery' as type,
                        ld.date_of_delivery as date,
                        ld.quantity_delivered as count,
                        ld.name_of_school_delivery_site as details,
                        b.Title as titles
                      FROM library_deliveries ld
                      LEFT JOIN books b ON ld.BookID = b.BookID
                      WHERE ld.date_of_delivery IS NOT NULL
                      ORDER BY ld.date_of_delivery DESC
                      LIMIT 5";

    $deliveryResult = $conn->query($deliveryQuery);
    if ($deliveryResult) {
        while ($row = $deliveryResult->fetch_assoc()) {
            $stamps['deliveries'][] = $row;
        }
    }

    return $stamps;
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<link rel="stylesheet" href="../public/assets/css/inventory.css">

<div class="content-wrapper" style="padding-top: 40px;">
    <section class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-header-enhanced">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="page-title-section text-center">
                                <h1 class="page-title">📊 Library Inventory Management</h1>
                                <p class="page-subtitle">Comprehensive overview of books, deliveries, and stock levels</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-5" id="statsCards">
                <!-- Cards will be loaded via JavaScript -->
            </div>

            <!-- Quick Actions Panel -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card quick-actions-panel">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-bolt"></i> ⚡ Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <button class="btn btn-primary btn-block quick-action-btn" onclick="openQuickAddModal()">
                                        <i class="fas fa-plus-circle"></i><br>
                                        📚 Add Book
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-success btn-block quick-action-btn" onclick="window.location='../admin/reservations.php'">
                                        <i class="fas fa-eye"></i><br>
                                        👀 View Borrowed Books
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-warning btn-block quick-action-btn" onclick="showLowStockModal()">
                                        <i class="fas fa-exclamation-triangle"></i><br>
                                        ⚠️ Low Stock Alert
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-info btn-block quick-action-btn" onclick="exportInventoryReport()">
                                        <i class="fas fa-download"></i><br>
                                        📊 Export Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card enhanced-tabs-container">
                        <div class="card-header bg-white">
                            <ul class="nav nav-pills enhanced-tabs" id="inventoryTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active enhanced-tab-link" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button" role="tab">
                                        <div class="tab-content-wrapper">
                                            <i class="fas fa-chart-pie tab-icon"></i>
                                            <span class="tab-text"> Overview</span>

                                        </div>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link enhanced-tab-link" id="borrowed-tab" data-bs-toggle="pill" data-bs-target="#borrowed" type="button" role="tab">
                                        <div class="tab-content-wrapper">
                                            <a href="../admin/reservations.php">
                                                <i class="fas fa-book-reader tab-icon"></i>
                                            </a>
                                            <span class="tab-text">📚 Borrowed Books</span>
                                            <span class="tab-badge badge-danger" id="borrowed-count">0</span>
                                        </div>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link enhanced-tab-link" id="deliveries-tab" data-bs-toggle="pill" data-bs-target="#deliveries" type="button" role="tab">
                                        <div class="tab-content-wrapper">
                                            <a href="../delivery/delivery.php" style="text-decoration: none;" class="tab-link">
                                                <i class="fas fa-truck tab-icon"></i>
                                                <span class="tab-text"> Deliveries</span>
                                            </a>

                                            <span class="tab-badge badge-info" id="delivery-count">0</span>
                                        </div>
                                    </button>
                                </li>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="inventoryTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-bar"></i> Stock Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="stockChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-doughnut"></i> Borrowing Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="reservationChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Most Borrowed Books and Import/Delivery Stamps -->
                    <div class="row">
                        <!-- Most Borrowed Books Card -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-gradient-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-trophy"></i> 🏆 Most Borrowed Books</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="mostBorrowedTable">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Book Title</th>
                                                    <th>Borrows</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Book Imports Card -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-gradient-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-file-import"></i> 📥 Book Imports</h5>
                                </div>
                                <div class="card-body">
                                    <div class="activity-feed" id="bookImportStamps">
                                        <!-- Data will be loaded via JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Library Deliveries Card -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-gradient-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-truck"></i> 🚚 Library Deliveries</h5>
                                </div>
                                <div class="card-body">
                                    <div class="activity-feed" id="libraryDeliveryStamps">
                                        <!-- Data will be loaded via JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Real-time Indicators -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card enhanced-realtime-card">
                                <div class="card-header bg-gradient-info text-white">
                                    <h5 class="mb-0">

                                    </h5>
                                </div>
                                <div class="card-body" id="realtimeIndicators">
                                    <!-- Real-time stats will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card enhanced-alerts-card">
                                <div class="card-header bg-gradient-warning text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-exclamation-triangle"></i> ⚠️ Alerts & Notifications
                                        <button class="btn btn-sm btn-light float-right" onclick="refreshAlerts()">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </h5>
                                </div>
                                <div class="card-body" id="alertsContainer">
                                    <!-- Alerts will be loaded via JavaScript -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card enhanced-activity-card">
                                <div class="card-header bg-gradient-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clock"></i> 🕰️ Recent Activity
                                        <button class="btn btn-sm btn-light float-right" onclick="loadActivityFeed()">
                                            <i class="fas fa-refresh"></i>
                                        </button>
                                    </h5>
                                </div>
                                <div class="card-body" id="activityFeed" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Activity feed will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Borrowed Books Tab -->
                <div class="tab-pane fade" id="borrowed" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-book-reader"></i> Currently Borrowed Books</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="borrowedBooksTable">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Student</th>
                                            <th>Year & Section</th>
                                            <th>Adviser</th>
                                            <th>Borrowed Date</th>
                                            <th>Due Date</th>
                                            <th>Days Borrowed</th>
                                            <th>Status</th>
                                            <th>Acquisition Source</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Management Tab -->
                <div class="tab-pane fade" id="stock" role="tabpanel">
                    <!-- Removed - No longer needed -->
                </div>

                <!-- Deliveries Tab -->
                <div class="tab-pane fade" id="deliveries" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-truck"></i> Recent Deliveries</h5>
                                <a href="displayStats.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Manage Deliveries
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="deliveriesTable">
                                    <thead>
                                        <tr>
                                            <th>Delivery ID</th>
                                            <th>Title & Grade Level</th>
                                            <th>Delivered</th>
                                            <th>Allocated</th>
                                            <th>Date</th>
                                            <th>Delivery Site</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Borrower Details Tab -->
                <div class="tab-pane fade" id="borrowers" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5><i class="fas fa-users"></i> Detailed Borrower History & Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="borrowerHistoryTable">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Year & Section</th>
                                                    <th>Adviser</th>
                                                    <th>Total Borrowings</th>
                                                    <th>Current</th>
                                                    <th>Returned</th>
                                                    <th>Overdue</th>
                                                    <th>Last Activity</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import History Tab -->
                <div class="tab-pane fade" id="imports" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5><i class="fas fa-file-import"></i> Excel Import History</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="importHistoryTable">
                                            <thead>
                                                <tr>
                                                    <th>Import Date</th>
                                                    <th>Books Added</th>
                                                    <th>Total Stock</th>
                                                    <th>Sources</th>
                                                    <th>Time Range</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5><i class="fas fa-chart-pie"></i> Import Statistics</h5>
                                </div>
                                <div class="card-body text-center" id="importStatsContainer">
                                    <!-- Import statistics will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h5><i class="fas fa-books"></i> Books with Import Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="booksImportTable">
                                            <thead>
                                                <tr>
                                                    <th>Book ID</th>
                                                    <th>Title</th>
                                                    <th>Author</th>
                                                    <th>Source</th>
                                                    <th>Import Date</th>
                                                    <th>Entry Method</th>
                                                    <th>Times Borrowed</th>
                                                    <th>Current Stock</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div class="tab-pane fade" id="analytics" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-area"></i> Monthly Trends</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="trendsChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-users"></i> User Activity</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-trophy"></i> Top Borrowed Books</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="topBooksTable">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Book Title</th>
                                                    <th>Author</th>
                                                    <th>Times Borrowed</th>
                                                    <th>Current Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('../includes/footer.php'); ?>

<!-- Custom CSS -->


<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="../public/assets/js/inventory.js"></script>
<script src="../public/assets/js/custom-inventory.js"></script>