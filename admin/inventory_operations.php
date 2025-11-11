<?php
session_start();

// Check authentication
if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['a', 'sa'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../config/conn.php');

// Set JSON response header
header('Content-Type: application/json');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'get_analytics_data':
                $data = getAnalyticsData($conn);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'get_activity_feed':
                $activities = getActivityFeed($conn);
                echo json_encode(['success' => true, 'data' => $activities]);
                break;

            case 'get_realtime_stats':
                $stats = getRealtimeStats($conn);
                echo json_encode(['success' => true, 'data' => $stats]);
                break;

            case 'get_top_borrowed_books':
                $books = getTopBorrowedBooks($conn);
                echo json_encode(['success' => true, 'data' => $books]);
                break;

            case 'get_monthly_trends':
                $trends = getMonthlyTrends($conn);
                echo json_encode(['success' => true, 'data' => $trends]);
                break;

            case 'get_user_activity_stats':
                $userStats = getUserActivityStats($conn);
                echo json_encode(['success' => true, 'data' => $userStats]);
                break;

            case 'get_delivery_analytics':
                $deliveryData = getDeliveryAnalytics($conn);
                echo json_encode(['success' => true, 'data' => $deliveryData]);
                break;

            case 'refresh_dashboard':
                $dashboardData = refreshDashboardData($conn);
                echo json_encode(['success' => true, 'data' => $dashboardData]);
                break;

            // New cases for inventory charts and alerts
            case 'get_stock_distribution':
                $data = getStockDistribution($conn);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'get_borrowing_status':
                $data = getBorrowingStatus($conn);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'get_alerts_notifications':
                $data = getAlertsNotifications($conn);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        error_log("Inventory Operations Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Internal server error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

// Analytics Data Function
function getAnalyticsData($conn)
{
    $data = [];

    // Get monthly trends
    $data['trends'] = getMonthlyTrends($conn);

    // Get user activity stats
    $data['activity'] = getUserActivityStats($conn);

    // Get top borrowed books
    $data['top_books'] = getTopBorrowedBooks($conn);

    return $data;
}

// Monthly Trends Function
function getMonthlyTrends($conn)
{
    $query = "SELECT 
                DATE_FORMAT(ReserveDate, '%b') as month,
                DATE_FORMAT(ReserveDate, '%Y-%m') as year_month,
                COUNT(CASE WHEN STATUS IN ('Borrowed', 'Returned') THEN 1 END) as reservations,
                COUNT(CASE WHEN STATUS = 'Returned' THEN 1 END) as returns
              FROM reservations 
              WHERE ReserveDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY year_month, month
              ORDER BY year_month ASC
              LIMIT 6";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return []; // Return empty array instead of static data
}

// User Activity Stats Function
function getUserActivityStats($conn)
{
    $stats = [];

    // Count users by type
    $query = "SELECT usertype, COUNT(*) as count FROM users GROUP BY usertype";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $type = $row['usertype'] === 's' ? 'Students' : ($row['usertype'] === 'a' ? 'Admins' : 'Others');
            $stats[] = [
                'type' => $type,
                'count' => $row['count']
            ];
        }
    }

    // Add active users count
    $activeQuery = "SELECT COUNT(DISTINCT StudentID) as active_users 
                   FROM reservations 
                   WHERE ReserveDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $activeResult = $conn->query($activeQuery);

    if ($activeResult && $activeResult->num_rows > 0) {
        $activeRow = $activeResult->fetch_assoc();
        $stats[] = [
            'type' => 'Active Users',
            'count' => $activeRow['active_users']
        ];
    }

    return $stats;
}

// Top Borrowed Books Function
function getTopBorrowedBooks($conn)
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
              LIMIT 10";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return []; // Return empty array instead of static data
}

// Activity Feed Function
function getActivityFeed($conn)
{
    $activities = [];

    // Get recent reservations
    $reservationQuery = "SELECT 
                           CONCAT('📚 ', u.name, ' borrowed \"', b.Title, '\"') as activity,
                           r.ReserveDate as activity_time,
                           'reservation' as type
                         FROM reservations r
                         JOIN books b ON r.BookID = b.BookID
                         JOIN users u ON r.StudentID = u.id
                         WHERE r.STATUS = 'Borrowed'
                         ORDER BY r.ReserveDate DESC
                         LIMIT 5";

    $result = $conn->query($reservationQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }

    // Get recent returns
    $returnQuery = "SELECT 
                      CONCAT('✅ ', u.name, ' returned \"', b.Title, '\"') as activity,
                      r.ReturnedDate as activity_time,
                      'return' as type
                    FROM reservations r
                    JOIN books b ON r.BookID = b.BookID
                    JOIN users u ON r.StudentID = u.id
                    WHERE r.STATUS = 'Returned' AND r.ReturnedDate IS NOT NULL
                    ORDER BY r.ReturnedDate DESC
                    LIMIT 5";

    $result = $conn->query($returnQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }

    // Sort by activity time
    usort($activities, function ($a, $b) {
        return strtotime($b['activity_time']) - strtotime($a['activity_time']);
    });

    return array_slice($activities, 0, 10);
}

// Real-time Stats Function
function getRealtimeStats($conn)
{
    $stats = [];

    // Active reservations (currently borrowed)
    $result = $conn->query("SELECT COUNT(*) as active_reservations FROM reservations WHERE STATUS = 'Borrowed'");
    $stats['active_reservations'] = $result ? $result->fetch_assoc()['active_reservations'] : 0;

    // Pending returns (overdue)
    $result = $conn->query("SELECT COUNT(*) as pending_returns FROM reservations WHERE STATUS = 'Borrowed' AND DueDate < CURDATE()");
    $stats['pending_returns'] = $result ? $result->fetch_assoc()['pending_returns'] : 0;

    // Today's activities
    $result = $conn->query("SELECT COUNT(*) as today_activities FROM reservations WHERE DATE(ReserveDate) = CURDATE()");
    $stats['today_activities'] = $result ? $result->fetch_assoc()['today_activities'] : 0;

    // Available books
    $result = $conn->query("SELECT COUNT(*) as available_books FROM books WHERE Stock > 0");
    $stats['available_books'] = $result ? $result->fetch_assoc()['available_books'] : 0;

    // Last updated timestamp
    $stats['last_updated'] = date('M j, g:i A');

    return $stats;
}

// Delivery Analytics Function
function getDeliveryAnalytics($conn)
{
    $data = [];

    // Check if library_deliveries table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'library_deliveries'");

    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Monthly delivery trends
        $query = "SELECT 
                    DATE_FORMAT(date_of_delivery, '%b') as month,
                    SUM(quantity_delivered) as total_delivered,
                    COUNT(*) as delivery_count
                  FROM library_deliveries 
                  WHERE date_of_delivery >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(date_of_delivery, '%Y-%m'), month
                  ORDER BY date_of_delivery ASC
                  LIMIT 6";

        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $data['monthly_deliveries'] = $result->fetch_all(MYSQLI_ASSOC);
        }

        // Top delivery sites
        $query = "SELECT 
                    name_of_school_delivery_site,
                    SUM(quantity_delivered) as total_books,
                    COUNT(*) as delivery_count
                  FROM library_deliveries 
                  GROUP BY name_of_school_delivery_site
                  ORDER BY total_books DESC
                  LIMIT 5";

        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $data['top_delivery_sites'] = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    return $data;
}

// Refresh Dashboard Data Function
function refreshDashboardData($conn)
{
    return [
        'stats' => getRealtimeStats($conn),
        'activities' => getActivityFeed($conn),
        'analytics' => getAnalyticsData($conn),
        'timestamp' => time()
    ];
}

// Stock Distribution Function - Fully dynamic
function getStockDistribution($conn)
{
    $query = "SELECT Subject, SUM(Stock) as total_stock FROM books WHERE Subject IS NOT NULL AND Subject != '' GROUP BY Subject ORDER BY total_stock DESC";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return []; // Return empty array instead of static data
}

// Borrowing Status Function - Fully dynamic
function getBorrowingStatus($conn)
{
    $query = "SELECT STATUS, COUNT(*) as total FROM reservations WHERE STATUS IS NOT NULL GROUP BY STATUS";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return []; // Return empty array instead of static data
}

// Alerts and Notifications Function - Fully dynamic
function getAlertsNotifications($conn)
{
    $alerts = [];

    // Low stock books
    $lowStockQuery = "SELECT BookID, Title, Stock FROM books WHERE Stock > 0 AND Stock < 5 ORDER BY Stock ASC LIMIT 5";
    $lowStockResult = $conn->query($lowStockQuery);
    $alerts['low_stock'] = $lowStockResult ? $lowStockResult->fetch_all(MYSQLI_ASSOC) : [];

    // Overdue books
    $overdueQuery = "SELECT b.Title, u.name as student_name, DATEDIFF(CURDATE(), r.DueDate) as days_overdue 
                     FROM reservations r 
                     JOIN books b ON r.BookID = b.BookID 
                     JOIN users u ON r.StudentID = u.id 
                     WHERE r.STATUS = 'Borrowed' AND r.DueDate < CURDATE() 
                     ORDER BY r.DueDate ASC LIMIT 5";
    $overdueResult = $conn->query($overdueQuery);
    $alerts['overdue'] = $overdueResult ? $overdueResult->fetch_all(MYSQLI_ASSOC) : [];

    // Recently added books (last 7 days)
    $recentQuery = "SELECT Title, Stock FROM books WHERE created_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND created_date IS NOT NULL ORDER BY created_date DESC LIMIT 5";
    $recentResult = $conn->query($recentQuery);
    $alerts['recent_additions'] = $recentResult ? $recentResult->fetch_all(MYSQLI_ASSOC) : [];

    return $alerts;
}

// Log activity function (for future use)
function logActivity($conn, $action, $details, $user_id = null)
{
    if (!$user_id && isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
    }

    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

// Error logging function
function logError($message)
{
    error_log("[Inventory Operations] " . date('Y-m-d H:i:s') . " - " . $message);
}
