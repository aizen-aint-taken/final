<?php
session_start();
include("../config/conn.php");


if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['sa', 'a'])) {
    $_SESSION['error'] = "You must be an admin to access that page.";
    header('Location: ../index.php');
    exit;
}

$studentId = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;
$selectedUserId = isset($_GET['user_id']) ? $_GET['user_id'] : 'all';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

$users = $conn->query("SELECT DISTINCT U.id, U.name FROM users U INNER JOIN reservations R ON U.id = R.StudentID");

$query = "
    SELECT
        R.id AS ReserveID,  
        U.name AS USERNAME,
        U.advicer AS ADVICER,
        DATE_FORMAT(R.ReserveDate, '%m-%d-%Y') AS RESERVEDATE,
       
        B.Title AS BOOK_TITLE,
        COALESCE(R.STATUS, 'Pending') AS STATUS,
        R.DueDate,
        DATEDIFF(R.DueDate, CURDATE()) AS DaysLeft
    FROM `reservations` AS R
    INNER JOIN users AS U ON R.StudentID = U.id
    INNER JOIN books AS B ON R.BookID = B.BookID";

$conditions = [];
$params = [];
$types = "";

if ($selectedUserId !== 'all') {
    $conditions[] = "R.StudentID = ?";
    $params[] = $selectedUserId;
    $types .= "i";
}

if ($selectedStatus !== 'all') {
    $conditions[] = "R.STATUS = ?";
    $params[] = $selectedStatus;
    $types .= "s";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reservations = $stmt->get_result();

$selectedUserName = '';
if ($selectedUserId !== 'all') {
    $stmtUser = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $selectedUserId);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($row = $resultUser->fetch_assoc()) {
        $selectedUserName = $row['name'];
    }
    $stmtUser->close();
}

$header = true;
if ($header) {
    include('../includes/header.php');
}
include('../includes/sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RESERVATION PAGE">
    <meta name="author" content="Ely Gian Ga">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../public/assets/css/reservationAdmin.css">
    <title>Reservation Management</title>

</head>

<body>
    <div class="content-wrapper">
        <div class="container-fluid" style="padding-top: 40px;">
            <div class="page-header">
                <h1 class="h3 mb-0 text-center">Borrowed Books </h1>
                <p class="mb-0 text-center">Track and manage borrowed books</p>
            </div>

            <div class="filter-container">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="user-filter">Filter by Student</label>
                        <div class="d-flex gap-2">
                            <select id="user-filter" class="form-select">
                                <option value="all" <?= $selectedUserId === 'all' ? 'selected' : '' ?>>All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $user['id'] == $selectedUserId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button id="filter-btn" class="btn btn-primary">
                                <i class="fa-solid fa-magnifying-glass"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="status-filter">Filter by Status</label>
                        <select id="status-filter" class="form-select">
                            <option value="all" <?= isset($_GET['status']) && $_GET['status'] === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="Pending" <?= isset($_GET['status']) && $_GET['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Borrowed" <?= isset($_GET['status']) && $_GET['status'] === 'Borrowed' ? 'selected' : '' ?>>Borrowed</option>
                            <option value="Rejected" <?= isset($_GET['status']) && $_GET['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Returned" <?= isset($_GET['status']) && $_GET['status'] === 'Returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions Section -->
            <div class="bulk-actions-container mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" id="selectAllBulk" class="form-check-input">
                            <label class="form-check-label fw-bold" for="selectAllBulk">
                                <i class="fas fa-check-square me-2"></i>Select All for Bulk Actions
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="bulkActionButtons" class="btn-group" style="display: none;">
                            <button id="bulkApprove" class="btn btn-success btn-sm">
                                <i class="fas fa-check me-2"></i>Approve Selected
                            </button>
                            <button id="bulkReject" class="btn btn-warning btn-sm">
                                <i class="fas fa-times me-2"></i>Reject Selected
                            </button>
                            <button id="bulkReturn" class="btn btn-info btn-sm">
                                <i class="fas fa-undo me-2"></i>Return Selected
                            </button>
                            <button id="deleteSelected" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash me-2"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
                <div id="bulkActionInfo" class="alert alert-info mt-3" style="display: none;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="selectedCount">0</span> reservation(s) selected for bulk action.
                    <div class="mt-2 small">
                        <strong>Available actions:</strong>
                        <span id="availableActions">Select items to see available bulk operations</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($selectedUserName) && $selectedUserId != 'all'): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-user me-2"></i>Showing reservations for: <strong><?= htmlspecialchars($selectedUserName) ?></strong>
                </div>
            <?php endif; ?>

            <?php

            if ($selectedStatus !== 'all' && $reservations->num_rows === 0): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>No reservations with status "<strong><?= htmlspecialchars($selectedStatus) ?></strong>" found.
                </div>
            <?php endif; ?>

            <div class="table-container">
                <!-- Desktop View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th id="selectAllColumn" style="display: none;">
                                    <input type="checkbox" id="selectAllDesktop" class="form-check-input">
                                    <label for="selectAllDesktop" class="form-check-label ms-1">Select All</label>
                                </th>
                                <th><i class="fas fa-user me-2"></i>Student Name</th>
                                <th><i class="fa-solid fa-chalkboard-user"></i>Advicer</th>
                                <th><i class="fas fa-calendar me-2"></i>Borrowed Date | DD-MM-YY</th>
                                <th><i class="fas fa-book me-2"></i>Book Title</th>
                                <th><i class="fas fa-tasks me-2"></i>Status</th>
                                <th><i class="fas fa-calendar-check me-2"></i>Return Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reserve): ?>
                                <tr id="row-<?= $reserve['ReserveID'] ?>">
                                    <td class="checkbox-cell" style="display: none;">
                                        <input type="checkbox"
                                            class="form-check-input reservation-checkbox bulk-checkbox"
                                            data-id="<?= $reserve['ReserveID'] ?>"
                                            data-status="<?= $reserve['STATUS'] ?>">
                                    </td>
                                    <td data-label="Student Name"><?= htmlspecialchars($reserve['USERNAME']) ?></td>
                                    <td data-label="Advicer"><?= htmlspecialchars($reserve['ADVICER']) ?></td>
                                    <td data-label="Date Borrowed"><?= htmlspecialchars($reserve['RESERVEDATE']) ?></td>
                                    <td data-label="Book Title"><?= htmlspecialchars($reserve['BOOK_TITLE']) ?></td>
                                    <td data-label="Status">
                                        <select class="form-select status-dropdown" data-id="<?= $reserve['ReserveID'] ?>" data-previous="<?= $reserve['STATUS'] ?>">
                                            <option value="Pending" <?= $reserve['STATUS'] == 'Pending' ? 'selected' : '' ?>
                                                <?= in_array($reserve['STATUS'], ['Borrowed', 'Returned']) ? 'disabled' : '' ?>>
                                                Pending
                                            </option>
                                            <option value="Borrowed" <?= $reserve['STATUS'] == 'Borrowed' ? 'selected' : '' ?>
                                                <?= in_array($reserve['STATUS'], ['Returned']) ? 'disabled' : '' ?>>
                                                <?= $reserve['STATUS'] == 'Borrowed' ? 'Borrowed' : 'Approve' ?>
                                            </option>
                                            <option value="Rejected" <?= $reserve['STATUS'] == 'Rejected' ? 'selected' : '' ?>
                                                <?= in_array($reserve['STATUS'], ['Borrowed', 'Returned']) ? 'disabled' : '' ?>>
                                                <?= $reserve['STATUS'] == 'Rejected' ? 'Rejected' : 'Reject' ?>
                                            </option>
                                            <option value="Returned" <?= $reserve['STATUS'] == 'Returned' ? 'selected' : '' ?>
                                                <?= !in_array($reserve['STATUS'], ['Borrowed']) ? 'disabled' : '' ?>>
                                                <?= $reserve['STATUS'] == 'Returned' ? 'Returned' : 'Return' ?>
                                            </option>
                                        </select>

                                        <?php if ($reserve['STATUS'] == 'Borrowed'): ?>
                                            <input type="date"
                                                class="form-control due-date-input mt-2"
                                                data-id="<?= $reserve['ReserveID'] ?>"
                                                value="<?= $reserve['DueDate'] ?? '' ?>"
                                                min="<?= date('Y-m-d') ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Date to Return">
                                        <?php
                                        if ($reserve['DueDate']) {
                                            $formattedDueDate = date('m-d-Y', strtotime($reserve['DueDate']));
                                            echo htmlspecialchars($formattedDueDate);


                                            if ($reserve['STATUS'] !== 'Returned' && $reserve['STATUS'] !== 'Rejected') {
                                                if ($reserve['DaysLeft'] <= 3 && $reserve['DaysLeft'] > 0) {
                                                    echo '<span class="badge bg-warning ms-2">⚠ Due in ' . $reserve['DaysLeft'] . ' days</span>';
                                                } elseif ($reserve['DaysLeft'] <= 0) {
                                                    echo '<span class="badge bg-danger ms-2">❌ Overdue</span>';
                                                }
                                            }
                                        } else {
                                            echo 'Not Set';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-danger btn-sm delete-single"
                                            data-id="<?= $reserve['ReserveID'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>


                <div class="d-block d-md-none">

                    <div class="mb-3 d-flex align-items-center" id="mobileSelectAllContainer" style="display: none;">
                        <div class="form-check">
                            <input type="checkbox" id="selectAllMobile" class="form-check-input">
                            <label class="form-check-label" for="selectAllMobile">Select All</label>
                        </div>
                    </div>

                    <?php foreach ($reservations as $reserve): ?>
                        <div class="card mb-3 reservation-card" id="card-<?= $reserve['ReserveID'] ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check mobile-checkbox-container" style="display: none;">
                                        <input type="checkbox"
                                            class="form-check-input reservation-checkbox bulk-checkbox"
                                            data-id="<?= $reserve['ReserveID'] ?>"
                                            data-status="<?= $reserve['STATUS'] ?>">
                                    </div>
                                    <button class="btn btn-danger btn-sm delete-single"
                                        data-id="<?= $reserve['ReserveID'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <h5 class="card-title text-primary">
                                    <i class="fas fa-book me-2"></i><?= htmlspecialchars($reserve['BOOK_TITLE']) ?>
                                </h5>

                                <div class="card-info mb-2">
                                    <div class="info-item">
                                        <small class="text-muted"><i class="fas fa-user me-2"></i>Student:</small>
                                        <span class="fw-bold"><?= htmlspecialchars($reserve['USERNAME']) ?></span>
                                    </div>

                                    <div class="info-item mt-2">
                                        <small class="text-muted"><i class="fas fa-calendar me-2"></i>Borrowed:</small>
                                        <span><?= htmlspecialchars($reserve['RESERVEDATE']) ?></span>
                                    </div>
                                </div>

                                <div class="status-section mb-3">
                                    <small class="text-muted d-block mb-2"><i class="fas fa-tasks me-2"></i>Status:</small>
                                    <select class="form-select status-dropdown" data-id="<?= $reserve['ReserveID'] ?>" data-previous="<?= $reserve['STATUS'] ?>">
                                        <option value="Pending" <?= $reserve['STATUS'] == 'Pending' ? 'selected' : '' ?>
                                            <?= in_array($reserve['STATUS'], ['Borrowed', 'Returned']) ? 'disabled' : '' ?>>Pending</option>
                                        <option value="Borrowed" <?= $reserve['STATUS'] == 'Borrowed' ? 'selected' : '' ?>
                                            <?= in_array($reserve['STATUS'], ['Returned']) ? 'disabled' : '' ?>>
                                            <?= $reserve['STATUS'] == 'Borrowed' ? 'Borrowed' : 'Approve' ?>
                                        </option>
                                        <option value="Rejected" <?= $reserve['STATUS'] == 'Rejected' ? 'selected' : '' ?>
                                            <?= in_array($reserve['STATUS'], ['Borrowed', 'Returned']) ? 'disabled' : '' ?>>
                                            <?= $reserve['STATUS'] == 'Rejected' ? 'Rejected' : 'Reject' ?>
                                        </option>
                                        <option value="Returned" <?= $reserve['STATUS'] == 'Returned' ? 'selected' : '' ?>
                                            <?= !in_array($reserve['STATUS'], ['Borrowed']) ? 'disabled' : '' ?>>
                                            <?= $reserve['STATUS'] == 'Returned' ? 'Returned' : 'Return' ?>
                                        </option>
                                    </select>

                                    <?php if ($reserve['STATUS'] == 'Borrowed'): ?>
                                        <input type="date"
                                            class="form-control due-date-input mt-2"
                                            data-id="<?= $reserve['ReserveID'] ?>"
                                            value="<?= $reserve['DueDate'] ?? '' ?>"
                                            min="<?= date('Y-m-d') ?>">
                                    <?php endif; ?>
                                </div>

                                <div class="return-date-section">
                                    <small class="text-muted"><i class="fas fa-calendar-check me-2"></i>Return Date:</small>
                                    <div class="mt-1">
                                        <?php
                                        if ($reserve['DueDate']) {
                                            $formattedDueDate = date('m-d-Y', strtotime($reserve['DueDate']));
                                            echo htmlspecialchars($formattedDueDate);


                                            if ($reserve['STATUS'] !== 'Returned' && $reserve['STATUS'] !== 'Rejected') {
                                                if ($reserve['DaysLeft'] <= 3 && $reserve['DaysLeft'] > 0) {
                                                    echo '<span class="badge bg-warning ms-2">⚠ Due in ' . $reserve['DaysLeft'] . ' days</span>';
                                                } elseif ($reserve['DaysLeft'] <= 0) {
                                                    echo '<span class="badge bg-danger ms-2">❌ Overdue</span>';
                                                }
                                            }
                                        } else {
                                            echo 'Not Set';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/reservationAdmin.js?v=<?= time() ?>"></script>
</body>

</html>