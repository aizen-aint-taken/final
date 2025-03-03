<?php
session_start();
include("../config/conn.php");

$studentId = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;
$selectedUserId = isset($_GET['user_id']) ? $_GET['user_id'] : 'all';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

$users = $conn->query("SELECT DISTINCT U.id, U.name FROM users U INNER JOIN reservations R ON U.id = R.StudentID");

$query = "
    SELECT
        R.id AS ReserveID,  
        U.name AS USERNAME,
        R.ReserveDate AS RESERVEDATE,
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

$header = false;
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
    <title>Reservation List</title>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            margin-left: 250px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #343a40;
            font-weight: 700;
        }

        .filter-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-container label {
            font-weight: 600;
            color: #495057;
        }

        .filter-container select,
        .filter-container button {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-container select:focus,
        .filter-container button:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 5px rgba(106, 17, 203, 0.5);
        }

        .filter-container button {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            border: none;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .filter-container button:hover {
            opacity: 0.9;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            font-size: 14px;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table thead {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(106, 17, 203, 0.05);
            transition: 0.3s;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(106, 17, 203, 0.02);
        }

        .status-dropdown {
            width: 130px;
            font-size: 14px;
            border-radius: 8px;
            padding: 5px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }

        .status-dropdown:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 5px rgba(106, 17, 203, 0.5);
        }

        .badge {
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 8px;
        }

        .badge.bg-warning {
            background-color: #ffc107 !important;
        }

        .badge.bg-danger {
            background-color: #dc3545 !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>📚 Borrowed Book List</h1>
        <div class="filter-container">
            <div class="row">
                <div class="col-md-6">
                    <label for="user-filter" class="form-label fw-bold">Filter by Student</label>
                    <div class="d-flex gap-2">
                        <select id="user-filter" class="form-select">
                            <option value="all" <?= $selectedUserId === 'all' ? 'selected' : '' ?>>All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $user['id'] == $selectedUserId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button id="filter-btn" class="btn btn-primary">Apply</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="status-filter" class="form-label fw-bold">Filter by Status</label>
                    <div class="d-flex gap-2">
                        <select id="status-filter" class="form-select">
                            <option value="all" <?= isset($_GET['status']) && $_GET['status'] === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="Pending" <?= isset($_GET['status']) && $_GET['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Approved" <?= isset($_GET['status']) && $_GET['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= isset($_GET['status']) && $_GET['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Returned" <?= isset($_GET['status']) && $_GET['status'] === 'Returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <hr style="border: 2px solid #6a11cb; opacity: 0.2;">

        <?php if (!empty($selectedUserName) && $selectedUserId != 'all'): ?>
            <h3 style="color: #6a11cb;" class="text-center">Showing Reservations for: <?= htmlspecialchars($selectedUserName) ?></h3>
        <?php elseif ($selectedUserId == 'all'): ?>
            <h3 class="text-center" style="color: #6a11cb;">Showing All Reservations</h3>
        <?php elseif (empty($selectedUserName)): ?>
            <h3 class="text-center" style="color: #dc3545;">Username <?= $selectedStatus ?> has no status yet </h3>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover text-center">
                <thead>
                    <tr>
                        <th colspan="5" class="text-end">
                            <button id="sendNotificationsBtn" class="btn btn-danger">Send Due Notifications</button>
                        </th>
                    </tr>
                    <tr>
                        <th>👤 Student Name</th>
                        <th>📅 Borrowed Date</th>
                        <th>📖 Book Title</th>
                        <th>🔖 Status</th>
                        <th>📅 Return Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reserve): ?>
                        <tr id="row-<?= $reserve['ReserveID'] ?>">
                            <td><?= htmlspecialchars($reserve['USERNAME']) ?></td>
                            <td><?= htmlspecialchars($reserve['RESERVEDATE']) ?></td>
                            <td><?= htmlspecialchars($reserve['BOOK_TITLE']) ?></td>
                            <td>
                                <select class="form-select status-dropdown" data-id="<?= $reserve['ReserveID'] ?>" data-previous="<?= $reserve['STATUS'] ?>">
                                    <option style="color: orange;" value="Pending" <?= $reserve['STATUS'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option style="color: cyan;" value="Approved" <?= $reserve['STATUS'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                    <option style="color: red" value="Rejected" <?= $reserve['STATUS'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option style="color: blue;" value="Returned" <?= $reserve['STATUS'] == 'Returned' ? 'selected' : '' ?>>Returned</option>
                                </select>

                                <!-- Add a date input for DueDate (only visible when status is "Approved") -->
                                <input type="date" class="form-control due-date-input" data-id="<?= $reserve['ReserveID'] ?>" style="display: none;">
                            </td>
                            <td>
                                <?= $reserve['DueDate'] ? htmlspecialchars($reserve['DueDate']) : 'Not Set' ?>
                                <?php if ($reserve['DueDate'] && $reserve['DaysLeft'] <= 3 && $reserve['DaysLeft'] > 0): ?>
                                    <span class="badge bg-warning">⚠ Due in <?= $reserve['DaysLeft'] ?> days</span>
                                <?php elseif ($reserve['DueDate'] && $reserve['DaysLeft'] <= 0): ?>
                                    <!-- <span class="badge bg-danger">❌ Overdue</span> -->
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("filter-btn").addEventListener("click", function() {
                let userId = document.getElementById("user-filter").value;
                let status = document.getElementById("status-filter").value;

                let url = new URL(window.location.href);
                url.searchParams.set("user_id", userId);
                url.searchParams.set("status", status);

                window.location.href = url.toString();
            });

            document.querySelectorAll(".status-dropdown").forEach(dropdown => {
                dropdown.addEventListener("change", function(event) {
                    let dropdownElement = event.target;
                    let reservationId = dropdownElement.getAttribute("data-id");
                    let newStatus = dropdownElement.value;

                    if (!confirm(`Are you sure you want to change the status to "${newStatus}"?`)) {
                        dropdownElement.value = dropdownElement.dataset.previous;
                        return;
                    }

                    let formData = new FormData();
                    formData.append("reservation_id", reservationId);
                    formData.append("status", newStatus);

                    fetch("update_status.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("✅ Status updated successfully!");
                                dropdownElement.dataset.previous = newStatus;
                            } else {
                                alert("❌ Error updating status.");
                                dropdownElement.value = dropdownElement.dataset.previous;
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            dropdownElement.value = dropdownElement.dataset.previous;
                        });
                });

                dropdown.dataset.previous = dropdown.value;
            });
        });

        document.getElementById("sendNotificationsBtn").addEventListener("click", function() {
            fetch('notification.php')
                .then(response => response.text())
                .then(data => alert(data))
                .catch(error => alert("Error sending notifications."));
        });

        document.querySelectorAll(".status-dropdown").forEach(dropdown => {
            dropdown.addEventListener("change", function(event) {
                const dueDateInput = document.querySelector(`.due-date-input[data-id="${this.dataset.id}"]`);
                if (this.value === 'Approved') {
                    dueDateInput.style.display = 'block';
                } else {
                    dueDateInput.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>