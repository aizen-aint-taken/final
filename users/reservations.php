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
    <link rel="stylesheet" href="reservations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Reservations</title>

</head>

<body>
    <?php include("../users/sidebar.php"); ?>
    <?php include("../users/header.php"); ?>

    <div class="content-wrapper" style="margin-left: 250px;">
        <div class="container mt-5">
            <div class="filter-container mb-5">
                <label for="form-select" class="filter-label mb-2">
                    <i class="fas fa-filter me-2"></i>Filter by Status
                </label>
                <select class="select form-select" name="status" id="form-select" onchange="filterStatus()">
                    <option value="All">All Reservations</option>
                    <option value="Approved">
                        <i class="fas fa-check-circle"></i> Approved
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
            </div>

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