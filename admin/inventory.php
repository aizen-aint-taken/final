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

include('../includes/header.php');
include('../includes/sidebar.php');
?>

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
                                <p class="page-subtitle">Comprehensive overview of books, reservations, deliveries, and stock levels</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4" id="statsCards">
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
                                        👀 View Reservations
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
                                            <i class="fas fa-book-reader tab-icon"></i>
                                            <span class="tab-text">📚 Borrowed Books</span>
                                            <span class="tab-badge badge-danger" id="borrowed-count">0</span>
                                        </div>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link enhanced-tab-link" id="deliveries-tab" data-bs-toggle="pill" data-bs-target="#deliveries" type="button" role="tab">
                                        <div class="tab-content-wrapper">
                                            <a href="../analysis/displayStats.php" style="text-decoration: none;" class="tab-link">
                                                <i class="fas fa-truck tab-icon"></i>
                                                <span class="tab-text"> Deliveries</span>
                                            </a>

                                            <span class="tab-badge badge-info" id="delivery-count">0</span>
                                        </div>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link enhanced-tab-link" id="borrowers-tab" data-bs-toggle="pill" data-bs-target="#borrowers" type="button" role="tab">
                                        <div class="tab-content-wrapper">
                                            <i class="fas fa-users tab-icon"></i>
                                            <span class="tab-text">👥 Borrower Details</span>
                                            <span class="tab-badge badge-primary" id="borrowers-count">📋</span>
                                        </div>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link enhanced-tab-link" id="imports-tab" data-bs-toggle="pill" data-bs-target="#imports" type="button" role="tab">
                                        <div class="tab-content-wrapper">
                                            <i class="fas fa-file-import tab-icon"></i>
                                            <span class="tab-text">📥 Import History</span>
                                            <span class="tab-badge badge-success" id="imports-count">📈</span>
                                        </div>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link enhanced-tab-link" id="analytics-tab" data-bs-toggle="pill" data-bs-target="#analytics" type="button" role="tab">
                                        <div class="tab-content-wrapper">
                                            <i class="fas fa-chart-line tab-icon"></i>
                                            <span class="tab-text">📈 Analytics</span>
                                            <span class="tab-badge badge-success">📊</span>
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
                        <div class="col-md-6">
                            <div class="card">
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
                        <div class="col-md-6">
                            <div class="card">
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
<style>
    /* Enhanced Statistics Cards */
    .stats-card {
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        border-radius: 15px !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .stats-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .stats-card:hover::before {
        left: 100%;
    }

    .stats-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .stats-label {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Quick Actions Panel */
    .quick-actions-panel {
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .quick-action-btn {
        height: 80px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 12px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .quick-action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .quick-action-btn i {
        font-size: 1.5rem;
        margin-bottom: 5px;
    }

    /* Enhanced Tabs */
    .enhanced-tabs-container {
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .enhanced-tabs {
        border-bottom: none;
        gap: 10px;
    }

    .enhanced-tab-link {
        border-radius: 12px !important;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
    }

    .tab-content-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
    }

    .tab-icon {
        font-size: 1.2rem;
        margin-bottom: 5px;
    }

    .tab-text {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .tab-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .enhanced-tab-link.active {
        background: linear-gradient(135deg, #007bff, #0056b3) !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
    }

    .enhanced-tab-link:hover:not(.active) {
        background: rgba(0, 123, 255, 0.1);
        transform: translateY(-1px);
    }

    /* Enhanced Cards */
    .enhanced-realtime-card,
    .enhanced-alerts-card,
    .enhanced-activity-card {
        border-radius: 15px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        border: none;
        overflow: hidden;
    }

    .enhanced-realtime-card .card-header,
    .enhanced-alerts-card .card-header,
    .enhanced-activity-card .card-header {
        border-radius: 15px 15px 0 0;
    }

    /* Live Indicators */
    .live-indicator {
        font-size: 10px;
        padding: 2px 6px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        animation: pulse 2s infinite;
    }

    /* Alert Enhancements */
    .alert-low-stock {
        border-left: 4px solid #ffc107;
        border-radius: 10px;
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        animation: slideInLeft 0.5s ease;
    }

    .alert-overdue {
        border-left: 4px solid #dc3545;
        border-radius: 10px;
        background: linear-gradient(135deg, #f8d7da, #fab1a0);
        animation: slideInLeft 0.5s ease;
    }

    .alert-info {
        border-left: 4px solid #17a2b8;
        border-radius: 10px;
        background: linear-gradient(135deg, #d1ecf1, #74b9ff);
        animation: slideInLeft 0.5s ease;
    }

    .alert-success {
        border-left: 4px solid #28a745;
        border-radius: 10px;
        background: linear-gradient(135deg, #d4edda, #55efc4);
        animation: slideInLeft 0.5s ease;
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Status Badges */
    .status-badge {
        font-size: 0.875rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Enhanced Tables */
    .table {
        border-radius: 10px;
        overflow: hidden;
    }

    .table th {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        padding: 15px;
    }

    .table td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .table tbody tr {
        transition: background-color 0.3s ease;
    }

    .table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
        transform: scale(1.01);
    }

    /* Page Header Enhancement */
    .page-header-enhanced {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .page-header-enhanced::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: repeating-linear-gradient(45deg,
                transparent,
                transparent 2px,
                rgba(255, 255, 255, 0.05) 2px,
                rgba(255, 255, 255, 0.05) 4px);
        animation: float 20s infinite linear;
    }

    @keyframes float {
        0% {
            transform: translate(-50%, -50%) rotate(0deg);
        }

        100% {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    .page-title {
        margin-bottom: 0.5rem;
        font-size: 3rem;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 1;
    }

    .page-subtitle {
        margin-bottom: 0;
        opacity: 0.95;
        font-size: 1.2rem;
        font-weight: 300;
        position: relative;
        z-index: 1;
    }

    /* Real-time Stats */
    .realtime-stat {
        padding: 20px;
        border-radius: 15px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        margin-bottom: 15px;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .realtime-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        border-color: rgba(0, 123, 255, 0.3);
    }

    .realtime-stat h4 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .realtime-stat small {
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Activity Feed */
    .activity-item {
        padding: 15px;
        margin-bottom: 10px;
        border-left: 4px solid;
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.05));
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .activity-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .activity-item.text-primary {
        border-left-color: #007bff;
    }

    .activity-item.text-success {
        border-left-color: #28a745;
    }

    /* Animations */
    .pulse-animation {
        animation: enhancedPulse 1s ease-in-out;
    }

    @keyframes enhancedPulse {
        0% {
            transform: scale(1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(0, 123, 255, 0.3);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    }

    .dynamic-indicator {
        position: relative;
    }

    .dynamic-indicator::after {
        content: '🔄';
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 14px;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .live-badge {
        background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        color: white;
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 12px;
        animation: pulse 2s infinite;
        font-weight: 700;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    /* Responsive Improvements */
    @media (max-width: 768px) {
        .page-title {
            font-size: 2rem;
        }

        .quick-action-btn {
            height: 60px;
            font-size: 12px;
        }

        .stats-number {
            font-size: 2rem;
        }

        .tab-text {
            font-size: 0.8rem;
        }

        .enhanced-tab-link {
            min-height: 50px;
            padding: 8px;
        }

        .tab-content-wrapper {
            gap: 2px;
        }

        .realtime-stat {
            padding: 15px;
            margin-bottom: 10px;
        }

        .realtime-stat h4 {
            font-size: 1.5rem;
        }

        .quick-actions-panel .row>div {
            margin-bottom: 10px;
        }
    }

    @media (max-width: 576px) {
        .page-header-enhanced {
            padding: 2rem 1rem;
        }

        .page-title {
            font-size: 1.8rem;
        }

        .page-subtitle {
            font-size: 1rem;
        }

        .quick-action-btn {
            height: 50px;
            font-size: 11px;
        }

        .quick-action-btn i {
            font-size: 1.2rem;
        }

        .stats-card {
            margin-bottom: 15px;
        }

        .enhanced-tab-link {
            min-height: 45px;
            padding: 6px;
        }

        .tab-text {
            font-size: 0.75rem;
        }

        .tab-icon {
            font-size: 1rem;
        }
    }

    /* Loading States */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        border-radius: 15px;
    }

    .loading-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #007bff;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
    }

    /* Chart Containers */
    .chart-container {
        position: relative;
        height: 350px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .chart-container canvas {
        max-height: 100% !important;
        max-width: 100% !important;
    }

    /* Gradient Backgrounds for Cards */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff, #0056b3) !important;
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #17a2b8, #138496) !important;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffc107, #d39e00) !important;
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #28a745, #1e7e34) !important;
    }
</style>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script>
    $(document).ready(function() {
        loadInventoryData();

        // Tab click handlers
        $('#inventoryTabs button').on('click', function() {
            const target = $(this).data('bs-target');

            switch (target) {
                case '#borrowed':
                    loadBorrowedBooks();
                    break;
                case '#deliveries':
                    loadDeliverySummary();
                    break;
                case '#borrowers':
                    loadBorrowerHistory();
                    break;
                case '#imports':
                    loadImportHistory();
                    break;
                case '#analytics':
                    loadAnalytics();
                    break;
            }
        });
    });

    function loadInventoryData() {
        $.post('', {
            action: 'get_inventory_stats'
        }, function(response) {
            if (response.success) {
                displayStatistics(response.data);
                createCharts(response.data);
                showAlerts(response.data);
            }
        }, 'json');
    }

    function displayStatistics(stats) {
        // If no data, show sample data for demonstration
        if (stats.total_books === 0) {
            showToast('📊 No data found. Displaying sample data for demonstration.', 'info');
            stats = {
                total_books: 150,
                total_stock: 500,
                books_imported_via_excel: 120,
                borrowed_books: 25,
                books_government: 80,
                books_donated: 30,
                books_purchased: 40,
                overdue_books: 5
            };
        }

        const statsHtml = `
        <div class="col-lg-2 col-md-4">
            <div class="card stats-card bg-primary text-white clickable-card" onclick="showBooksModal()" data-toggle="tooltip" title="Click to view all books">
                <div class="card-body text-center">
                    <div class="stats-number dynamic-counter" data-target="${stats.total_books}">${stats.total_books}</div>
                    <div class="stats-label">📚 Total Books</div>
                    <small class="mt-2 d-block">👆 Click to explore</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="card stats-card bg-success text-white clickable-card" onclick="showStockDetails()" data-toggle="tooltip" title="Click to view stock details">
                <div class="card-body text-center">
                    <div class="stats-number dynamic-counter" data-target="${stats.total_stock}">${stats.total_stock}</div>
                    <div class="stats-label">📦 Total Stock</div>
                    <small class="mt-2 d-block">👆 Click for details</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="card stats-card bg-info text-white clickable-card" onclick="showImportDetails()" data-toggle="tooltip" title="Click to view import history">
                <div class="card-body text-center">
                    <div class="stats-number dynamic-counter" data-target="${stats.books_imported_via_excel}">${stats.books_imported_via_excel}</div>
                    <div class="stats-label">📥 Excel Imports</div>
                    <small class="mt-2 d-block">👆 View history</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="card stats-card bg-warning text-white clickable-card" onclick="showBorrowedDetails()" data-toggle="tooltip" title="Click to view borrowed books">
                <div class="card-body text-center">
                    <div class="stats-number dynamic-counter" data-target="${stats.borrowed_books}">${stats.borrowed_books}</div>
                    <div class="stats-label">📚 Borrowed Books</div>
                    <small class="mt-2 d-block">👆 Click to manage</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="card stats-card bg-secondary text-white clickable-card" onclick="showSourceBreakdown()" data-toggle="tooltip" title="Books by acquisition source">
                <div class="card-body text-center">
                    <div class="stats-number dynamic-counter" data-target="${(stats.books_government || 0) + (stats.books_donated || 0) + (stats.books_purchased || 0)}">${(stats.books_government || 0) + (stats.books_donated || 0) + (stats.books_purchased || 0)}</div>
                    <div class="stats-label">🏢 Gov/Donated/Purchased</div>
                    <small class="mt-2 d-block">👆 View breakdown</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="card stats-card bg-danger text-white clickable-card" onclick="showOverdueDetails()" data-toggle="tooltip" title="Click to view overdue books">
                <div class="card-body text-center">
                    <div class="stats-number dynamic-counter" data-target="${stats.overdue_books}">${stats.overdue_books}</div>
                    <div class="stats-label">⚠️ Overdue Books</div>
                    <small class="mt-2 d-block">👆 Click to resolve</small>
                </div>
            </div>
        </div>
    `;

        $('#statsCards').html(statsHtml);

        // Update tab badges
        $('#borrowed-count').text(stats.borrowed_books || 0);
        $('#delivery-count').text(stats.total_deliveries || 0);
        $('#borrowers-count').text('📋');
        $('#imports-count').text(stats.books_imported_via_excel || 0);

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Animate counters
        animateCounters();

        // Add click effects
        $('.clickable-card').on('click', function() {
            $(this).addClass('pulse-animation');
            setTimeout(() => $(this).removeClass('pulse-animation'), 1000);
        });
    }

    function createCharts(stats) {
        // Stock Distribution Chart - Bar chart with consistent sizing
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        new Chart(stockCtx, {
            type: 'bar',
            data: {
                labels: ['Available', 'Out of Stock', 'Low Stock'],
                datasets: [{
                    data: [stats.available_books, stats.out_of_stock, stats.low_stock],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107'],
                    borderWidth: 1,
                    borderColor: ['#1e7e34', '#c82333', '#e0a800']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed.y || 0;
                                return `${label}: ${value} books`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Reservation Status Chart - Doughnut chart
        const reservationCtx = document.getElementById('reservationChart').getContext('2d');
        new Chart(reservationCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Borrowed', 'Returned'],
                datasets: [{
                    data: [stats.pending_reservations, stats.borrowed_books, stats.returned_books],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} reservations (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    function showAlerts(stats) {
        let alerts = '';
        let alertCount = 0;

        if (stats.overdue_books > 0) {
            alerts += `
                <div class="alert alert-danger alert-overdue alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>⚠️ Critical Alert:</strong> ${stats.overdue_books} book(s) are overdue for return.
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="showOverdueDetails()">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>`;
            alertCount++;
        }

        if (stats.low_stock > 0) {
            alerts += `
                <div class="alert alert-warning alert-low-stock alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-boxes"></i> 
                            <strong>📦 Stock Alert:</strong> ${stats.low_stock} book(s) have low stock (less than 5 copies).
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="showLowStockModal()">
                            <i class="fas fa-list"></i> View List
                        </button>
                    </div>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>`;
            alertCount++;
        }

        if (stats.pending_reservations > 0) {
            alerts += `
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-clock"></i> 
                            <strong>🕰️ Pending Action:</strong> ${stats.pending_reservations} reservation(s) are pending approval.
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="window.location='../admin/reservations.php'">
                            <i class="fas fa-check"></i> Approve Now
                        </button>
                    </div>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>`;
            alertCount++;
        }

        if (alerts === '') {
            alerts = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle"></i> 
                            <strong>✅ All Good:</strong> All systems are running smoothly!
                        </div>
                        <div class="text-success">
                            <i class="fas fa-heart"></i> Everything is under control
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>`;
        }

        // Add alert summary
        if (alertCount > 0) {
            alerts = `
                <div class="alert-summary mb-3">
                    <h6 class="text-muted">
                        🚨 Active Alerts: <span class="badge badge-warning">${alertCount}</span>
                        <button class="btn btn-sm btn-secondary float-right" onclick="refreshAlerts()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </h6>
                </div>
            ` + alerts;
        }

        $('#alertsContainer').html(alerts);

        // Add alert sound for critical alerts
        if (stats.overdue_books > 0) {
            playAlertSound();
        }
    }

    function loadBorrowedBooks() {
        $.post('', {
            action: 'get_borrowed_books'
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(book) {
                    const daysLeft = book.days_left;
                    let statusClass = 'badge-success';
                    let statusText = `${daysLeft} days left`;

                    if (daysLeft <= 0) {
                        statusClass = 'badge-danger';
                        statusText = 'Overdue';
                    } else if (daysLeft <= 3) {
                        statusClass = 'badge-warning';
                        statusText = `Due in ${daysLeft} days`;
                    }

                    const yearSection = book.year && book.sect ? `${book.year} - ${book.sect}` : 'N/A';
                    const daysBorrowed = book.days_borrowed || 0;

                    html += `
                    <tr>
                        <td><strong>${book.Title}</strong><br><small class="text-muted">${book.Subject || ''}</small></td>
                        <td>${book.Author}</td>
                        <td>
                            <strong>${book.student_name}</strong><br>
                            <small class="text-muted">${book.student_email || ''}</small>
                        </td>
                        <td><span class="badge badge-info">${yearSection}</span></td>
                        <td>${book.advicer || 'N/A'}</td>
                        <td>${new Date(book.ReserveDate).toLocaleDateString()}</td>
                        <td>${new Date(book.DueDate).toLocaleDateString()}</td>
                        <td><span class="badge badge-light">${daysBorrowed} days</span></td>
                        <td><span class="badge ${statusClass}">${statusText}</span></td>
                        <td><span class="badge badge-secondary">${book.acquisition_source || 'N/A'}</span></td>
                    </tr>
                `;
                });

                $('#borrowedBooksTable tbody').html(html);
            }
        }, 'json');
    }

    function loadDeliverySummary() {
        $.post('', {
            action: 'get_delivery_summary'
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(delivery) {
                    html += `
                    <tr>
                        <td>${delivery.DeliveryID}</td>
                        <td>${delivery.title_and_grade_level}</td>
                        <td><span class="badge badge-success">${delivery.quantity_delivered}</span></td>
                        <td><span class="badge badge-info">${delivery.quantity_allocated}</span></td>
                        <td>${new Date(delivery.date_of_delivery).toLocaleDateString()}</td>
                        <td>${delivery.name_of_school_delivery_site}</td>
                    </tr>
                `;
                });

                $('#deliveriesTable tbody').html(html);
            }
        }, 'json');
    }

    function loadBorrowerHistory() {
        $.post('', {
            action: 'get_detailed_borrower_history'
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(borrower) {
                    const yearSection = borrower.year && borrower.sect ? `${borrower.year} - ${borrower.sect}` : 'N/A';
                    const overdueClass = borrower.overdue_books > 0 ? 'table-warning' : '';

                    html += `
                    <tr class="${overdueClass}">
                        <td>
                            <strong>${borrower.student_name}</strong><br>
                            <small class="text-muted">${borrower.email}</small>
                        </td>
                        <td><span class="badge badge-info">${yearSection}</span></td>
                        <td>${borrower.advicer || 'N/A'}</td>
                        <td><span class="badge badge-primary">${borrower.total_borrowings}</span></td>
                        <td><span class="badge badge-warning">${borrower.current_borrowings}</span></td>
                        <td><span class="badge badge-success">${borrower.returned_books}</span></td>
                        <td><span class="badge badge-danger">${borrower.overdue_books}</span></td>
                        <td>${new Date(borrower.last_borrowing).toLocaleDateString()}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="showBorrowerDetails(${borrower.student_id})" title="View detailed history">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                });

                $('#borrowerHistoryTable tbody').html(html);
            }
        }, 'json');
    }

    function loadImportHistory() {
        // Load import history summary
        $.post('', {
            action: 'get_import_history'
        }, function(response) {
            if (response.success) {
                let html = '';
                let totalImported = 0;
                let totalStock = 0;

                response.data.forEach(function(import_record) {
                    totalImported += parseInt(import_record.books_imported);
                    totalStock += parseInt(import_record.total_stock_added);

                    const timeRange = import_record.first_import_time === import_record.last_import_time ?
                        'Single import' :
                        `${new Date(import_record.first_import_time).toLocaleTimeString()} - ${new Date(import_record.last_import_time).toLocaleTimeString()}`;

                    html += `
                    <tr>
                        <td>${new Date(import_record.import_date).toLocaleDateString()}</td>
                        <td><span class="badge badge-success">${import_record.books_imported}</span></td>
                        <td><span class="badge badge-info">${import_record.total_stock_added}</span></td>
                        <td><span class="badge badge-secondary">${import_record.sources}</span></td>
                        <td><small>${timeRange}</small></td>
                    </tr>
                `;
                });

                $('#importHistoryTable tbody').html(html);

                // Update import statistics
                $('#importStatsContainer').html(`
                    <div class="row">
                        <div class="col-6">
                            <h3 class="text-success">${totalImported}</h3>
                            <p>Total Books Imported</p>
                        </div>
                        <div class="col-6">
                            <h3 class="text-info">${totalStock}</h3>
                            <p>Total Stock Added</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h4 class="text-primary">${response.data.length}</h4>
                            <p>Import Sessions</p>
                        </div>
                    </div>
                `);
            }
        }, 'json');

        // Load books with import details
        $.post('', {
            action: 'get_books_with_import_details'
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(book) {
                    const importDate = book.import_date ? new Date(book.import_date).toLocaleDateString() : 'N/A';
                    const entryMethodClass = book.entry_method === 'Imported' ? 'badge-success' : 'badge-secondary';

                    html += `
                    <tr>
                        <td><span class="badge badge-light">${book.BookID}</span></td>
                        <td><strong>${book.Title}</strong></td>
                        <td>${book.Author}</td>
                        <td><span class="badge badge-info">${book.source}</span></td>
                        <td>${importDate}</td>
                        <td><span class="badge ${entryMethodClass}">${book.entry_method}</span></td>
                        <td><span class="badge badge-primary">${book.times_borrowed}x</span></td>
                        <td><span class="badge badge-warning">${book.Stock}</span></td>
                    </tr>
                `;
                });

                $('#booksImportTable tbody').html(html);
            }
        }, 'json');
    }

    function loadAnalytics() {
        // Load analytics data and create charts
        $.post('inventory_operations.php', {
            action: 'get_analytics_data'
        }, function(response) {
            if (response.success) {
                const data = response.data;
                createTrendsChart(data.trends || []);
                createActivityChart(data.activity || []);
                loadTopBorrowedBooks(data.top_books || []);
            } else {
                // Fallback to sample data if analytics endpoint fails
                createSampleAnalytics();
            }
        }, 'json').fail(function() {
            // Fallback to sample data if analytics endpoint doesn't exist
            createSampleAnalytics();
        });
    }

    function createTrendsChart(trendsData) {
        const ctx = document.getElementById('trendsChart');
        if (!ctx) return;

        let labels, reservationsData, returnsData;

        if (trendsData && trendsData.length > 0) {
            // Use real data
            labels = trendsData.map(item => item.month);
            reservationsData = trendsData.map(item => parseInt(item.reservations));
            returnsData = trendsData.map(item => parseInt(item.returns));
        } else {
            // Fallback data
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            reservationsData = [12, 19, 15, 25, 22, 18];
            returnsData = [8, 15, 12, 20, 18, 16];
        }

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Reservations',
                    data: reservationsData,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Returns',
                    data: returnsData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function createActivityChart(activityData) {
        const ctx = document.getElementById('activityChart');
        if (!ctx) return;

        let labels, data, colors;

        if (activityData && activityData.length > 0) {
            // Use real data
            labels = activityData.map(item => item.type);
            data = activityData.map(item => parseInt(item.count));
            colors = ['#17a2b8', '#dc3545', '#28a745', '#ffc107', '#6f42c1'].slice(0, labels.length);
        } else {
            // Fallback data
            labels = ['Students', 'Admins', 'Active Users'];
            data = [45, 5, 38];
            colors = ['#17a2b8', '#dc3545', '#28a745'];
        }

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    function loadTopBorrowedBooks(topBooksData) {
        let html = '';

        if (topBooksData && topBooksData.length > 0) {
            // Use real data from server
            topBooksData.forEach(function(book, index) {
                let statusClass = book.status === 'Available' ? 'badge-success' :
                    book.status === 'Limited' ? 'badge-warning' : 'badge-danger';

                html += `
                    <tr>
                        <td><span class="badge badge-primary">#${index + 1}</span></td>
                        <td>${book.Title}</td>
                        <td>${book.Author}</td>
                        <td><span class="badge badge-info">${book.borrow_count}x</span></td>
                        <td><span class="badge ${statusClass}">${book.status}</span></td>
                    </tr>
                `;
            });
        } else {
            // Fallback to sample data
            const sampleBooks = [{
                    rank: 1,
                    title: 'Harry Potter',
                    author: 'J.K. Rowling',
                    count: 25,
                    status: 'Available'
                },
                {
                    rank: 2,
                    title: 'To Kill a Mockingbird',
                    author: 'Harper Lee',
                    count: 18,
                    status: 'Limited'
                },
                {
                    rank: 3,
                    title: '1984',
                    author: 'George Orwell',
                    count: 15,
                    status: 'Available'
                },
                {
                    rank: 4,
                    title: 'Pride and Prejudice',
                    author: 'Jane Austen',
                    count: 12,
                    status: 'Available'
                },
                {
                    rank: 5,
                    title: 'The Great Gatsby',
                    author: 'F. Scott Fitzgerald',
                    count: 10,
                    status: 'Out of Stock'
                }
            ];

            sampleBooks.forEach(function(book) {
                let statusClass = book.status === 'Available' ? 'badge-success' :
                    book.status === 'Limited' ? 'badge-warning' : 'badge-danger';

                html += `
                    <tr>
                        <td><span class="badge badge-primary">#${book.rank}</span></td>
                        <td>${book.title}</td>
                        <td>${book.author}</td>
                        <td><span class="badge badge-info">${book.count}x</span></td>
                        <td><span class="badge ${statusClass}">${book.status}</span></td>
                    </tr>
                `;
            });
        }

        $('#topBooksTable tbody').html(html);
    }

    function createSampleAnalytics() {
        createTrendsChart([]);
        createActivityChart([]);
        loadTopBorrowedBooks([]);
    }

    function refreshActiveTab() {
        const activeTab = $('.nav-link.active').data('bs-target');
        switch (activeTab) {
            case '#borrowed':
                loadBorrowedBooks();
                break;
            case '#deliveries':
                loadDeliverySummary();
                break;
            case '#borrowers':
                loadBorrowerHistory();
                break;
            case '#imports':
                loadImportHistory();
                break;
            case '#analytics':
                loadAnalytics();
                break;
        }
    }

    // Add auto-refresh functionality with activity feed
    function loadActivityFeed() {
        $.post('inventory_operations.php', {
            action: 'get_activity_feed'
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(activity) {
                    const timeAgo = typeof moment !== 'undefined' ? moment(activity.activity_time).fromNow() : new Date(activity.activity_time).toLocaleString();
                    const typeClass = activity.type === 'reservation' ? 'text-primary' : 'text-success';
                    html += `
                        <div class="activity-item ${typeClass}">
                            <small class="text-muted">${timeAgo}</small>
                            <p class="mb-1">${activity.activity}</p>
                        </div>
                    `;
                });

                if (html === '') {
                    html = '<p class="text-muted">No recent activities</p>';
                }

                $('#activityFeed').html(html);
            }
        }, 'json');
    }

    function loadRealtimeStats() {
        $.post('inventory_operations.php', {
            action: 'get_realtime_stats'
        }, function(response) {
            if (response.success) {
                const stats = response.data;

                // Update real-time indicators
                $('#realtimeIndicators').html(`
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="realtime-stat">
                                <h4 class="text-primary">${stats.active_reservations}</h4>
                                <small>Active Borrowers</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="realtime-stat">
                                <h4 class="text-warning">${stats.pending_returns}</h4>
                                <small>Overdue Books</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="realtime-stat">
                                <h4 class="text-success">${stats.today_activities}</h4>
                                <small>Today's Activities</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="realtime-stat">
                                <h4 class="text-info">${stats.available_books}</h4>
                                <small>Available Books</small>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Last updated: ${stats.last_updated}</small>
                    </div>
                `);
            }
        }, 'json');
    }

    // Add auto-refresh functionality
    setInterval(function() {
        loadInventoryData();
        loadRealtimeStats();
        loadActivityFeed();
        refreshActiveTab();
    }, 30000); // Refresh every 30 seconds

    // Load additional data on page load
    $(document).ready(function() {
        loadInventoryData();
        loadRealtimeStats();
        loadActivityFeed();

        // Add pulse animation for real-time updates
        $('.stats-card').on('updated', function() {
            $(this).addClass('pulse-animation');
            setTimeout(() => $(this).removeClass('pulse-animation'), 1000);
        });

        // Initialize enhanced features
        initializeEnhancedFeatures();
    });

    // Enhanced Interactive Functions
    function initializeEnhancedFeatures() {
        // Add click sound effect
        $('.clickable-card, .quick-action-btn, .enhanced-tab-link').on('click', function() {
            playClickSound();
        });

        // Initialize refresh buttons
        $(document).on('click', '[onclick*="refresh"]', function() {
            showLoadingOverlay($(this).closest('.card'));
        });

        // Add keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch (e.which) {
                    case 49: // Ctrl+1 - Overview
                        $('#overview-tab').click();
                        e.preventDefault();
                        break;
                    case 50: // Ctrl+2 - Borrowed Books
                        $('#borrowed-tab').click();
                        e.preventDefault();
                        break;
                    case 51: // Ctrl+3 - Deliveries
                        $('#deliveries-tab').click();
                        e.preventDefault();
                        break;
                    case 52: // Ctrl+4 - Analytics
                        $('#analytics-tab').click();
                        e.preventDefault();
                        break;
                    case 82: // Ctrl+R - Refresh
                        location.reload();
                        e.preventDefault();
                        break;
                }
            }
        });
    }

    function animateCounters() {
        $('.dynamic-counter').each(function() {
            const $this = $(this);
            const target = parseInt($this.data('target')) || 0;
            const current = parseInt($this.text()) || 0;

            if (current !== target) {
                $({
                    countNum: current
                }).animate({
                    countNum: target
                }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum));
                    },
                    complete: function() {
                        $this.text(target);
                    }
                });
            }
        });
    }

    function showLoadingOverlay($container) {
        const $overlay = $('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
        $container.css('position', 'relative').append($overlay);

        setTimeout(() => {
            $overlay.fadeOut(300, () => $overlay.remove());
        }, 1000);
    }

    function playClickSound() {
        // Optional: Add click sound effect
        // const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFLIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBjiMzvTejSMFLIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBjiMzvTejSMFLIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBjiMzvTejSMFLIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBjiMzvTejSMFLIHO9N2QQAoUXrTp66hVFApGn+DyvmohBDuMzvTejSMFLIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFLIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFJIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFJIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFJIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFJIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFJIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuMzvTejSMFJIHO9N2QQAoUXrTp66hVFApGn+DyvmwhBDuM');
        // audio.volume = 0.1;
        // audio.play().catch(() => {});
    }

    function playAlertSound() {
        // Optional: Add alert sound for critical issues
        // const audio = new Audio('data:audio/wav;base64,UklGRr4CAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YZoCAAC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4uLi4QEBAuLi4uEBAQLi4uLhAQEC4');
        // audio.volume = 0.05;
        // audio.play().catch(() => {});
    }

    // Enhanced Modal Functions
    function showBooksModal() {
        showToast('📚 Navigating to All Books...', 'info');
        setTimeout(() => {
            window.location.href = '../admin/index.php';
        }, 500);
    }

    function showStockDetails() {
        showToast('📦 Loading stock details...', 'info');
        // Could open a detailed stock modal here
        $('#analytics-tab').click();
    }

    function showBorrowedDetails() {
        showToast('📚 Loading borrowed books...', 'info');
        $('#borrowed-tab').click();
    }

    function showOverdueDetails() {
        showToast('⚠️ Loading overdue books...', 'warning');
        $('#borrowed-tab').click();
        // Filter to show only overdue books
        setTimeout(() => {
            filterOverdueBooks();
        }, 1000);
    }

    function showLowStockModal() {
        $.post('', {
            action: 'get_low_stock_books'
        }, function(response) {
            if (response.success && response.data.length > 0) {
                let modalHtml = `
                    <div class="modal fade" id="lowStockModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-warning text-white">
                                    <h5 class="modal-title">📦 Low Stock Alert</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Author</th>
                                                    <th>Stock</th>
                                                    <th>Subject</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;

                response.data.forEach(book => {
                    modalHtml += `
                        <tr>
                            <td>${book.Title}</td>
                            <td>${book.Author}</td>
                            <td><span class="badge badge-warning">${book.Stock}</span></td>
                            <td>${book.Subject}</td>
                        </tr>`;
                });

                modalHtml += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-warning" onclick="window.location='../admin/index.php'">Manage Stock</button>
                                </div>
                            </div>
                        </div>
                    </div>`;

                $('body').append(modalHtml);
                $('#lowStockModal').modal('show');
                $('#lowStockModal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            } else {
                showToast('✅ No low stock items found!', 'success');
            }
        }, 'json');
    }

    function openQuickAddModal() {
        showToast('📚 Redirecting to Add Book...', 'info');
        setTimeout(() => {
            window.location.href = '../admin/index.php#addBookModal';
        }, 500);
    }

    function exportInventoryReport() {
        showToast('📊 Generating inventory report...', 'info');
        // Simulate export process
        setTimeout(() => {
            window.location.href = '../admin/export-excel.php';
        }, 1000);
    }

    function refreshAlerts() {
        showLoadingOverlay($('#alertsContainer').closest('.card'));
        loadInventoryData();
        showToast('🔄 Alerts refreshed!', 'success');
    }

    function filterOverdueBooks() {
        // Add filtering logic for overdue books in the borrowed books table
        $('#borrowedBooksTable tbody tr').each(function() {
            const statusCell = $(this).find('td:last');
            if (statusCell.text().includes('Overdue')) {
                $(this).addClass('table-danger').show();
            } else {
                $(this).hide();
            }
        });

        showToast('⚠️ Filtered to show overdue books only', 'warning');
    }

    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const bgClass = {
            'info': 'bg-info',
            'success': 'bg-success',
            'warning': 'bg-warning',
            'error': 'bg-danger'
        } [type] || 'bg-info';

        const toast = `
            <div id="${toastId}" class="toast ${bgClass} text-white" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <div class="toast-body">
                    ${message}
                    <button type="button" class="ml-2 mb-1 close text-white" onclick="$('#${toastId}').fadeOut()">
                        <span>&times;</span>
                    </button>
                </div>
            </div>`;

        $('body').append(toast);
        $(`#${toastId}`).fadeIn().delay(3000).fadeOut(() => {
            $(`#${toastId}`).remove();
        });
    }

    // New enhanced functions
    function showImportDetails() {
        showToast('📥 Loading import history...', 'info');
        $('#imports-tab').click();
    }

    function showSourceBreakdown() {
        showToast('🏢 Loading acquisition source breakdown...', 'info');
        $('#analytics-tab').click();
    }

    function showBorrowerDetails(studentId) {
        // Show detailed modal for specific borrower
        $.post('', {
            action: 'get_borrower_details',
            student_id: studentId
        }, function(response) {
            if (response.success) {
                // Create and show detailed borrower modal
                showBorrowerDetailModal(response.data);
            }
        }, 'json');
    }

    function showBorrowerDetailModal(borrowerData) {
        let modalHtml = `
            <div class="modal fade" id="borrowerDetailModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">👥 ${borrowerData.student_name} - Detailed History</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Email:</strong> ${borrowerData.email}<br>
                                    <strong>Year & Section:</strong> ${borrowerData.year} - ${borrowerData.sect}<br>
                                    <strong>Adviser:</strong> ${borrowerData.advicer}
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Borrowings:</strong> <span class="badge badge-primary">${borrowerData.total_borrowings}</span><br>
                                    <strong>Currently Borrowed:</strong> <span class="badge badge-warning">${borrowerData.current_borrowings}</span><br>
                                    <strong>Overdue:</strong> <span class="badge badge-danger">${borrowerData.overdue_books}</span>
                                </div>
                            </div>
                            <h6>Recent Books:</h6>
                            <p class="text-muted">${borrowerData.recent_books || 'No recent borrowings'}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="window.location='reservations.php?student=${borrowerData.student_id}'">View Full History</button>
                        </div>
                    </div>
                </div>
            </div>`;

        $('body').append(modalHtml);
        $('#borrowerDetailModal').modal('show');
        $('#borrowerDetailModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }
</script>