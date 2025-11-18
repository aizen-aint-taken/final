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

// Check how many books the user currently has borrowed
$borrowedStmt = $conn->prepare("SELECT COUNT(*) as borrowed_count FROM reservations WHERE StudentID = ? AND STATUS = 'Borrowed'");
$borrowedStmt->bind_param("i", $studentId);
$borrowedStmt->execute();
$borrowedResult = $borrowedStmt->get_result();
$borrowedCount = $borrowedResult->fetch_assoc()['borrowed_count'];
$borrowedStmt->close();

// Handle AJAX request for reservations
if (isset($_GET['status']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $query = "SELECT 
                DATE(R.ReserveDate) AS RESERVEDATE,
                DATE(R.ReturnedDate) AS RETURNEDDATE,  -- ✅ only date
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
        echo json_encode(['success' => false, 'error', $e->getMessage()]);
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
    AND R.STATUS = 'Borrowed'
    AND DATEDIFF(R.DueDate, CURDATE()) <= 7
    AND DATEDIFF(R.DueDate, CURDATE()) > 0
    ORDER BY R.DueDate ASC
";

$notificationStmt = $conn->prepare($notificationsQuery);
$notificationStmt->bind_param("i", $studentId);
$notificationStmt->execute();
$notifications = $notificationStmt->get_result();
$notificationStmt->close();

// Check for overdue books
$overdueQuery = "
    SELECT 
        B.Title as BOOK_TITLE,
        DATE(R.DueDate) AS DueDate
    FROM reservations R
    INNER JOIN books B ON R.BookID = B.BookID
    WHERE R.StudentID = ?
    AND R.STATUS = 'Borrowed'
    AND DATEDIFF(CURDATE(), R.DueDate) > 0
    ORDER BY R.DueDate ASC
";

$overdueStmt = $conn->prepare($overdueQuery);
$overdueStmt->bind_param("i", $studentId);
$overdueStmt->execute();
$overdueBooks = $overdueStmt->get_result();
$overdueStmt->close();

// Main reservation records
$reservationsQuery = "
    SELECT
        U.name AS USERNAME,
        U.email AS EMAIL,
        DATE(R.ReserveDate) AS RESERVEDATE,         -- only date
        DATE_ADD(DATE(R.ReserveDate), INTERVAL 7 DAY) AS DUEDATE,  -- only date
        DATE(R.ReturnedDate) AS RETURNEDDATE,      -- new column
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
            <!-- Add borrowed books counter -->
            <div class="alert alert-info">
                <strong>Borrowed Books:</strong> You currently have <strong><?php echo $borrowedCount; ?>/8</strong> books borrowed.
                <?php if ($borrowedCount >= 8): ?>
                    <span class="text-danger">You have reached the maximum borrowing limit.</span>
                <?php endif; ?>
            </div>

            <div class="filter-container mb-4">
                <label for="form-select" class="filter-label mb-2">
                    <i class="fas fa-filter me-2"></i>Select by Status
                </label>
                <div class="d-flex gap-2">
                    <select class="select form-select" name="status" id="form-select">
                        <option value="All">All Status</option>
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
                No status found for the selected filter.
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
                                        <th>DUE DATE | YYYY-MM-DD </th>
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


            <!-- Overdue Books Notification -->
            <?php if ($overdueBooks->num_rows > 0): ?>
                <div class="alert alert-danger mt-3">
                    <h5><i class="fas fa-exclamation-triangle"></i> Overdue Books</h5>
                    <p>You have <?= $overdueBooks->num_rows ?> book(s) that are past their due date. You're Books are on/over Due Date librarian won let you borrow unless you return your book.</p>
                    <div class="table-responsive">
                        <table class="table table-danger">
                            <thead>
                                <tr>
                                    <th>BOOK TITLE</th>
                                    <th>OVERDUE DATE | YYYY-MM-DD</th>
                                    <th>DAYS OVERDUE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $overdueBooks->data_seek(0); // Reset pointer
                                while ($overdue = $overdueBooks->fetch_assoc()):
                                    $daysOverdue = (new DateTime())->diff(new DateTime($overdue['DueDate']))->days;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($overdue['BOOK_TITLE']) ?></td>
                                        <td><?= htmlspecialchars($overdue['DueDate']) ?></td>
                                        <td><?= $daysOverdue ?> days</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
                                    || YYYY-MM-DD
                                </th>
                                <th>Returned Date || YYYY-MM-DD </th>
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
                                    <td>
                                        <?php if (!empty($row['RETURNEDDATE'])): ?>
                                            <?= htmlspecialchars($row['RETURNEDDATE']) ?>
                                        <?php else: ?>
                                            <span style="color: red; font-weight: bold;">Not yet returned</span>
                                        <?php endif; ?>
                                    </td>


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


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <script src="../public/assets/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {


            $(document).on('click', '#filterButton', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const status = $('#form-select').val();
                const $button = $(this);
                const originalHtml = $button.html();


                $button.html('<span class="spinner-border spinner-border-sm me-2"></span>Filtering...');

                $.ajax({
                    url: window.location.pathname,
                    method: 'GET',
                    data: {
                        status: status
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (!response.data) {
                            console.error('Invalid response:', response);
                            return;
                        }

                        $('#reservationTable tbody').empty();
                        $('#mobileReservations').empty();
                        $('#noStatusMessage').hide();


                        updateViews(response.data);
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('An error occurred while filtering. Please try again.');
                    },
                    complete: function() {

                        $button.html(originalHtml);
                    }
                });
            });

            function updateViews(data) {
                const tableBody = $('#reservationTable tbody');
                const mobileContainer = $('#mobileReservations');
                const noStatusMessage = $('#noStatusMessage');

                if (data.length === 0) {

                    tableBody.html('<tr><td colspan="3" class="text-center">No active status found</td></tr>');
                    mobileContainer.empty();
                    noStatusMessage.show();
                } else {

                    noStatusMessage.hide();

                    const rows = data.map(item => `
                <tr>
                    <td>${item.RESERVEDATE}</td>
                    <td>${item.BOOK_TITLE}</td>
                    <td>${item.RETURNEDDATE}</td>
                    
                    <td><span class="badge ${getStatusBadgeClass(item.STATUS)}">${item.STATUS}</span></td>
                </tr>
            `).join('');
                    tableBody.html(rows);


                    const mobileContent = data.map(item => `
                <div class="card reservation-card mb-3 text-center">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title reservation-title mb-0">${item.BOOK_TITLE}</h5>
                    </div>
                    <div class="card-body">
                            <p class="card-text">
                                <strong>Borrowed Date:</strong><br>
                                ${item.RESERVEDATE}
                            </p>
                     <p class="card-text">
                            <strong>Returned Date:</strong><br>
                             ${item.RETURNEDDATE ? item.RETURNEDDATE : '<em>Not yet returned</em>'}
                    </p>


                        <p class="card-text">Status: </p>
                        <span class="badge ${getStatusBadgeClass(item.STATUS)}">
                            ${item.STATUS}
                        </span>
                    </div>
                </div>
            `).join('');


                    mobileContainer.html(mobileContent);
                }
            }
        });

        function getStatusBadgeClass(status) {
            switch (status) {
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
    </script>




</body>

</html>