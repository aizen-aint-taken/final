<?php
session_start();

if ($_SESSION['usertype'] !== 'u') {
    header("Location: ../index.php"); // Redirect to a safe page
    exit;
}

include("../config/conn.php");
$studentId = $_SESSION['student_id'];

// First, get the notifications
$notificationsQuery = "
    SELECT 
        B.Title as BOOK_TITLE,
        R.DueDate,
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

// Add this after $notifications = $notificationStmt->get_result();
echo "<!-- Debug information: -->";
echo "<!-- Number of notifications: " . $notifications->num_rows . " -->";
if ($notifications->num_rows == 0) {
    $debug_query = "
        SELECT 
            B.Title as BOOK_TITLE,
            R.DueDate,
            R.STATUS,
            DATEDIFF(R.DueDate, CURDATE()) as days_diff
        FROM reservations R
        INNER JOIN books B ON R.BookID = B.BookID
        WHERE R.StudentID = $studentId
    ";
    $debug_result = $conn->query($debug_query);
    echo "<!-- All reservations for this user: -->";
    while ($row = $debug_result->fetch_assoc()) {
        echo "<!-- 
            Book: {$row['BOOK_TITLE']},
            Due: {$row['DueDate']},
            Status: {$row['STATUS']},
            Days diff: {$row['days_diff']}
        -->";
    }
}

// Then, get the reservations
$reservationsQuery = "
    SELECT
    U.name AS USERNAME,
    U.email AS EMAIL,
    R.ReserveDate AS RESERVEDATE,
    DATE_ADD(R.ReserveDate, INTERVAL 7 DAY) AS DUEDATE,
    B.Title AS BOOK_TITLE,
    R.STATUS AS STATUS
FROM `reservations` AS R
INNER JOIN users AS U ON R.StudentID = U.id
INNER JOIN books AS B ON R.BookID = B.BookID
    WHERE U.id = ?
";

if (isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] != 'All') {
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

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    $reservationData = [];
    while ($row = $reservations->fetch_assoc()) {
        $reservationData[] = $row;
    }
    echo json_encode($reservationData);
    exit;
}

// Add this function after your includes
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Approved':
            return 'badge-success';
        case 'Rejected':
            return 'badge-danger';
        case 'Returned':
            return 'badge-warning';
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
    <link rel="stylesheet" href="../public/assets/css/reservations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Reservations</title>
    <style>
        /* Wrapper for main content */
        .content-wrapper {
            margin-left: 250px;
            /* Matches the sidebar's width */
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Adjustments for smaller screens */
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                /* Sidebar collapses, so no margin */
                padding: 15px;
            }
        }

        /* Table and card adjustments */
        .card {
            border-radius: 10px;
            overflow: hidden;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease-in-out;
        }

        /* Badge Styling */
        .badge-danger {
            background-color: #dc3545 !important;
            color: #fff !important;
        }

        .badge-success {
            background-color: #28a745 !important;
            color: #fff !important;
        }

        .badge-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .badge-secondary {
            background-color: #6c757d !important;
            color: #fff !important;
        }

        .alert {
            display: none;
        }

        .select {
            display: inline-block;
            padding: 10px 20px;
            margin: 15px;
            border: 2px solid #4CAF50;
            font-size: 18px;
            border-radius: 8px;
            background-color: #fff;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .reservation-card {
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .reservation-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
        }

        .reservation-card .card-body {
            padding: 1rem;
        }

        .badge {
            font-size: 0.9rem !important;
            padding: 0.5em 1em;
        }

        @media (max-width: 991.98px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }

            .reservation-card {
                margin-left: -5px;
                margin-right: -5px;
            }
        }

        .card-header.bg-warning {
            background-color: #fff3cd !important;
            border-bottom: 1px solid #ffeeba;
        }

        .badge {
            padding: 8px 12px;
            font-size: 0.875rem;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #000;
        }
    </style>
</head>

<body>
    <?php include("../users/sidebar.php"); ?>

    <div class="content-wrapper" style="margin-left: 250px;">
        <div class="container mt-5">
            <select class="select form-select mb-4" name="status" id="form-select" onchange="filterStatus()">
                <option value="All">All</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Returned">Returned</option>
                <option value="Pending">Pending</option>
            </select>

            <!-- Notifications Section -->
            <?php if ($notifications->num_rows > 0): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-bell"></i> Due Date Notifications</h5>
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
                    <h2 class="fw-bold">Reservation List</h2>
                </div>
                <div class="card-body">
                    <table id="reservationTable" class="table table-striped table-hover text-center align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th scope="col">Reserved Date</th>
                                <th scope="col">Book Title</th>
                                <th scope="col">Approval Status</th>
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
            <div class="d-lg-none">
                <?php
                // Reset the result pointer again for mobile view
                $reservations->data_seek(0);
                while ($row = $reservations->fetch_assoc()):
                ?>
                    <div class="card reservation-card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($row['BOOK_TITLE']) ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <strong>Reserved Date:</strong><br>
                                <?= htmlspecialchars($row['RESERVEDATE']) ?>
                            </p>
                            <span class="badge <?= getStatusBadgeClass($row['STATUS']) ?>">
                                <?= htmlspecialchars($row['STATUS']) ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div id="noStatusMessage" class="alert alert-warning mt-3" style="display: none;">
                No reservations found for the selected filter.
            </div>
        </div>
    </div>

    <div class="content-wrapper" style="margin-left: 250px;">
        <div class="container mt-5">
            <h2>Notifications</h2>
            <table id="notificationsTable" class="table table-striped table-hover text-center align-middle">
                <thead class="table-primary">
                    <tr>
                        <th scope="col">Book Title</th>
                        <th scope="col">Due Date</th>
                        <th scope="col">Days Remaining</th>
                    </tr>
                </thead>
                <tbody id="notificationsTableBody"></tbody>
            </table>
        </div>
    </div>

    <?php if ($notifications->num_rows > 0): ?>
        <div class="container mt-3">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Due Date Reminders</h4>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <p class="mb-0">
                        <strong><?= htmlspecialchars($notification['BOOK_TITLE']) ?></strong> is due in
                        <?= $notification['DAYS_REMAINING'] ?> day(s)
                        (<?= $notification['DueDate'] ?>)
                    </p>
                <?php endwhile; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <script src="../public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function filterStatus() {
            const status = document.getElementById('form-select').value;
            $.ajax({
                url: 'reservations.php',
                type: 'get',
                data: {
                    status: status
                },
                success: function(data) {
                    const reservations = JSON.parse(data);

                    // Update desktop view
                    const tableBody = document.querySelector('#reservationTable tbody');
                    if (tableBody) {
                        tableBody.innerHTML = reservations.map(reserve => `
                            <tr>
                                <td>${reserve.RESERVEDATE}</td>
                                <td>${reserve.BOOK_TITLE}</td>
                                <td><span class="badge ${getStatusClass(reserve.STATUS)}">${reserve.STATUS}</span></td>
                            </tr>
                        `).join('');
                    }

                    // Update mobile view
                    const mobileContainer = document.querySelector('.d-lg-none');
                    if (mobileContainer) {
                        mobileContainer.innerHTML = reservations.map(reserve => `
                            <div class="card reservation-card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">${reserve.BOOK_TITLE}</h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <strong>Reserved Date:</strong><br>
                                        ${reserve.RESERVEDATE}
                                    </p>
                                    <span class="badge ${getStatusClass(reserve.STATUS)}">${reserve.STATUS}</span>
                                </div>
                            </div>
                        `).join('');
                    }

                    // Show/hide no results message
                    const noStatusMessage = document.getElementById('noStatusMessage');
                    if (noStatusMessage) {
                        noStatusMessage.style.display = reservations.length === 0 ? 'block' : 'none';
                    }
                }
            });
        }

        function getStatusClass(status) {
            switch (status) {
                case 'Approved':
                    return 'badge-success';
                case 'Rejected':
                    return 'badge-danger';
                case 'Returned':
                    return 'badge-warning';
                default:
                    return 'badge-secondary';
            }
        }

        // Initialize the filter when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Only attach the event listener once
            document.getElementById('form-select').addEventListener('change', filterStatus);
        });
    </script>
</body>

</html>