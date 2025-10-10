<?php
ob_start();
session_start();

if ($_SESSION['usertype'] !== 'u') {
    header("Location: ../index.php");
    exit;
}

include("../config/conn.php");
$studentId = $_SESSION['student_id'];

// Get student name
$nameQuery = $conn->prepare("SELECT name FROM users WHERE id = ?");
$nameQuery->bind_param("i", $studentId);
$nameQuery->execute();
$nameResult = $nameQuery->get_result();
$studentName = $nameResult->fetch_assoc()['name'] ?? 'Student';
$nameQuery->close();

// Handle AJAX request for reservations
if (isset($_GET['status']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $query = "SELECT 
                DATE(R.ReserveDate) AS RESERVEDATE,  -- ✅ only date
                B.Title AS BOOK_TITLE,
                R.STATUS
              FROM reservations R 
              INNER JOIN books B ON R.BookID = B.BookID 
              WHERE R.StudentID = ?";

    if ($_GET['status'] !== 'All') {
        $query .= " AND R.STATUS = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $_SESSION['student_id'], $_GET['status']);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['student_id']);
    }

    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Notifications for due dates
$notificationsQuery = "
    SELECT 
        B.Title as BOOK_TITLE,
        DATE(R.DueDate) AS DueDate,  -- ✅ only date
        DATEDIFF(R.DueDate, CURDATE()) as DAYS_REMAINING
    FROM reservations R
    INNER JOIN books B ON R.BookID = B.BookID
    WHERE R.StudentID = ?
    AND R.STATUS = 'Approved'
    AND DATEDIFF(R.DueDate, CURDATE()) <= 7
    AND DATEDIFF(R.DueDate, CURDATE()) > 0
    ORDER BY R.DueDate ASC
";

$notificationStmt = $conn->prepare($notificationsQuery);
$notificationStmt->bind_param("i", $studentId);
$notificationStmt->execute();
$notifications = $notificationStmt->get_result();
$notificationStmt->close();

// Main reservation records
$reservationsQuery = "
    SELECT
        U.name AS USERNAME,
        U.email AS EMAIL,
        DATE(R.ReserveDate) AS RESERVEDATE,  -- ✅ only date
        DATE_ADD(DATE(R.ReserveDate), INTERVAL 7 DAY) AS DUEDATE,  -- ✅ only date
        B.Title AS BOOK_TITLE,
        R.STATUS AS STATUS
    FROM reservations AS R
    INNER JOIN users AS U ON R.StudentID = U.id
    INNER JOIN books AS B ON R.BookID = B.BookID
    WHERE U.id = ?
";

if (isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] !== 'All') {
    $status = $_GET['status'];
    $reservationsQuery .= " AND R.STATUS = ?";
    $stmt = $conn->prepare($reservationsQuery);
    $stmt->bind_param("is", $studentId, $status);
} else {
    $stmt = $conn->prepare($reservationsQuery);
    $stmt->bind_param("i", $studentId);
}

$stmt->execute();
$reservations = $stmt->get_result();
$stmt->close();

function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Borrowed':
            return 'badge-success';
        case 'Rejected':
            return 'badge-danger';
        case 'Returned':
            return 'badge-warning';
        case 'Pending':
            return 'badge-secondary';
        default:
            return 'badge-secondary';
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RESERVATION USER PAGE">
    <meta name="author" content="Ely Gian Ga">
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/assets/css/users_main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Reservations</title>
</head>

<body>
    <?php include("../users/sidebar.php"); ?>
    <?php include("../users/header.php"); ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="filter-container mb-4">
                <label for="form-select" class="filter-label mb-2">
                    <i class="fas fa-filter me-2"></i>Select by Status
                </label>
                <div class="d-flex gap-2">
                    <select class="select form-select" name="status" id="form-select">
                        <option value="All">All Reservations</option>
                        <option value="Borrowed">
                            <i class="fas fa-check-circle"></i> Borrowed
                        </option>
                        <option value="Pending">
                            <i class="fas fa-clock"></i> Pending
                        </option>
                        <option value="Rejected">
                            <i class="fas fa-times-circle"></i> Rejected
                        </option>
                        <option value="Returned">
                            <i class="fas fa-undo"></i> Returned
                        </option>
                    </select>
                    <button class="btn btn-primary" id="filterButton">
                        <i class="fas fa-search me-2"></i>Select
                    </button>
                </div>
            </div>

            <!-- QR Code na part -->
            <div class="mb-4 text-center qr-section">
                <?php

                $qrStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM reservations WHERE StudentID = ? AND STATUS = 'Borrowed' AND (DueDate IS NOT NULL AND DueDate >= CURDATE())");
                $qrStmt->bind_param("i", $studentId);
                $qrStmt->execute();
                $qrResult = $qrStmt->get_result();
                $qrCount = $qrResult->fetch_assoc()['cnt'];
                $qrStmt->close();
                if ($qrCount >= 1): ?>
                    <div class="alert alert-info">You have an active borrowed book(s). Please present this QR code to the admin when returning your books.</div>
                    <img src="generate_qr.php?<?= time() ?>" alt="Your QR Code" style="max-width: 150px;" />
                <?php else: ?>
                    <div class="alert alert-secondary">QR code will be available when you have an active borrowed book(s).</div>
                <?php endif; ?>
            </div>

            <!-- Add this right after the filter container -->
            <div id="noStatusMessage" class="alert alert-warning mt-3" style="display: none;">
                No reservations found for the selected filter.
            </div>

            <!-- Notifications Section -->
            <?php if ($notifications->num_rows > 0): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-bell"></i>Book(s) Due Date </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>BOOK TITLE</th>
                                        <th>DUE DATE</th>
                                        <th>DAYS REMAINING</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($notification = $notifications->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($notification['BOOK_TITLE']) ?></td>
                                            <td><?= htmlspecialchars($notification['DueDate']) ?></td>
                                            <td>
                                                <span class="badge <?= $notification['DAYS_REMAINING'] <= 3 ? 'bg-danger' : 'bg-warning' ?>">
                                                    <?= $notification['DAYS_REMAINING'] ?> days remaining
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Desktop View -->
            <div class="card shadow-lg d-none d-lg-block">
                <div class="card-header bg-primary text-white text-center">
                    <h2 class="fw-bold"><?= htmlspecialchars($studentName) ?>'s Books List</h2>
                </div>
                <div class="card-body">
                    <table id="reservationTable" class="table table-striped table-hover text-center align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th scope="col">Borrowed Date
                                    || YY-MM-DD
                                </th>
                                <th scope="col">Book Title</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset the result pointer
                            $reservations->data_seek(0);
                            while ($row = $reservations->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['RESERVEDATE']) ?></td>
                                    <td><?= htmlspecialchars($row['BOOK_TITLE']) ?></td>
                                    <td>
                                        <span class="badge <?= getStatusBadgeClass($row['STATUS']) ?>">
                                            <?= htmlspecialchars($row['STATUS']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="d-lg-none" id="mobileReservations">
                <!-- Mobile content will be dynamically updated here -->
            </div>
        </div>
    </div>


    <script src="../public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../public/assets/js/userReservations.js"></script>



</body>

</html>