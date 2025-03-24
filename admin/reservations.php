<?php
session_start();
include("../config/conn.php");

// if ($_SESSION['usertype'] == 'a') {
//     header("Location: ../index.php");
// } elseif ($_SESSION['usertype'] == 'sa') {
//     header("Location: ../index.php");
//     exit;
// }


$studentId = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;
$selectedUserId = isset($_GET['user_id']) ? $_GET['user_id'] : 'all';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

$users = $conn->query("SELECT DISTINCT U.id, U.name FROM users U INNER JOIN reservations R ON U.id = R.StudentID");

$query = "
    SELECT
        R.id AS ReserveID,  
        U.name AS USERNAME,
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
    <title>Reservation Management</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
            margin-left: 250px;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            background: #fff;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .filter-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-container label {
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 0.5rem;
        }

        .form-select {
            border-radius: 10px;
            padding: 0.8rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        .btn-primary {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #1e3c72;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .status-dropdown {
            width: 140px;
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .status-dropdown:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .due-date-input {
            width: 140px;
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .due-date-input:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
        }

        .alert-info {
            background: linear-gradient(45deg, #0dcaf0, #0d6efd);
            color: white;
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }

            .container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .filter-container {
                padding: 1rem;
            }

            .table-responsive {
                border-radius: 15px;
                overflow: hidden;
            }

            .status-dropdown,
            .due-date-input {
                width: 100%;
            }

            .table td {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
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
                            <i class="fas fa-filter me-2"></i>Apply
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="status-filter">Filter by Status</label>
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

        <?php if (!empty($selectedUserName) && $selectedUserId != 'all'): ?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-user me-2"></i>Showing reservations for: <strong><?= htmlspecialchars($selectedUserName) ?></strong>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <!-- <th colspan="5" class="text-end">
                                <button id="sendNotificationsBtn" class="btn btn-danger">
                                    <i class="fas fa-bell me-2"></i>Send Due Notifications
                                </button>
                            </th> -->
                        </tr>
                        <tr>
                            <th><i class="fas fa-user me-2"></i>Student Name</th>
                            <th><i class="fas fa-calendar me-2"></i>Borrowed Date</th>
                            <th><i class="fas fa-book me-2"></i>Book Title</th>
                            <th><i class="fas fa-tasks me-2"></i>Status</th>
                            <th><i class="fas fa-calendar-check me-2"></i>Return Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reserve): ?>
                            <tr id="row-<?= $reserve['ReserveID'] ?>">
                                <td data-label="Student Name"><?= htmlspecialchars($reserve['USERNAME']) ?></td>
                                <td data-label="Borrowed Date"><?= htmlspecialchars($reserve['RESERVEDATE']) ?></td>
                                <td data-label="Book Title"><?= htmlspecialchars($reserve['BOOK_TITLE']) ?></td>
                                <td data-label="Status">
                                    <select class="form-select status-dropdown" data-id="<?= $reserve['ReserveID'] ?>" data-previous="<?= $reserve['STATUS'] ?>">
                                        <option value="Pending" <?= $reserve['STATUS'] == 'Pending' ? 'selected' : '' ?>
                                            <?= in_array($reserve['STATUS'], ['Approved', 'Returned']) ? 'disabled' : '' ?>>
                                            Pending
                                        </option>
                                        <option value="Approved" <?= $reserve['STATUS'] == 'Approved' ? 'selected' : '' ?>
                                            <?= in_array($reserve['STATUS'], ['Returned']) ? 'disabled' : '' ?>>
                                            Approved
                                        </option>
                                        <option value="Rejected" <?= $reserve['STATUS'] == 'Rejected' ? 'selected' : '' ?>
                                            <?= in_array($reserve['STATUS'], ['Approved', 'Returned']) ? 'disabled' : '' ?>>
                                            Rejected
                                        </option>
                                        <option value="Returned" <?= $reserve['STATUS'] == 'Returned' ? 'selected' : '' ?>
                                            <?= !in_array($reserve['STATUS'], ['Approved']) ? 'disabled' : '' ?>>
                                            Returned
                                        </option>
                                    </select>

                                    <?php if ($reserve['STATUS'] == 'Approved'): ?>
                                        <input type="date"
                                            class="form-control due-date-input mt-2"
                                            data-id="<?= $reserve['ReserveID'] ?>"
                                            value="<?= $reserve['DueDate'] ?? '' ?>"
                                            min="<?= date('Y-m-d') ?>">
                                    <?php endif; ?>
                                </td>
                                <td data-label="Return Date">
                                    <?php
                                    if ($reserve['DueDate']) {
                                        $formattedDueDate = date('m-d-Y', strtotime($reserve['DueDate']));
                                        echo htmlspecialchars($formattedDueDate);

                                        if ($reserve['DaysLeft'] <= 3 && $reserve['DaysLeft'] > 0) {
                                            echo '<span class="badge bg-warning ms-2">⚠ Due in ' . $reserve['DaysLeft'] . ' days</span>';
                                        } elseif ($reserve['DaysLeft'] <= 0) {
                                            echo '<span class="badge bg-danger ms-2">❌ Overdue</span>';
                                        }
                                    } else {
                                        echo 'Not Set';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
                    let previousStatus = dropdownElement.dataset.previous;

                    // Status transition validation rules
                    const invalidTransitions = {
                        'Approved': ['Pending', 'Rejected'],
                        'Returned': ['Pending', 'Approved', 'Rejected']
                    };

                    // Check if the transition is invalid
                    if (invalidTransitions[previousStatus] && invalidTransitions[previousStatus].includes(newStatus)) {
                        alert(`❌ Cannot change status from "${previousStatus}" to "${newStatus}"`);
                        dropdownElement.value = previousStatus;
                        return;
                    }

                    // Special validation for Returned status
                    if (newStatus === 'Returned' && previousStatus !== 'Approved') {
                        alert("❌ Can only mark approved books as returned.");
                        dropdownElement.value = previousStatus;
                        return;
                    }

                    if (!confirm(`Are you sure you want to change the status to "${newStatus}"?`)) {
                        dropdownElement.value = previousStatus;
                        return;
                    }

                    let formData = new FormData();
                    formData.append("reservation_id", reservationId);
                    formData.append("status", newStatus);
                    formData.append("previous_status", previousStatus);

                    fetch("update_status.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(`✅ ${data.message}`);
                                dropdownElement.dataset.previous = newStatus;

                                // Update the row display
                                const row = dropdownElement.closest('tr');

                                // If marking as Returned, hide the due date input
                                if (newStatus === 'Returned') {
                                    const dueDateInput = row.querySelector('.due-date-input');
                                    if (dueDateInput) {
                                        dueDateInput.style.display = 'none';
                                    }
                                }

                                // Update due date display if status is Approved
                                if (newStatus === 'Approved' && data.dueDate) {
                                    const dueDateCell = row.querySelector('[data-label="Return Date"]');
                                    if (dueDateCell) {
                                        const formattedDate = new Date(data.dueDate).toLocaleDateString('en-US', {
                                            month: '2-digit',
                                            day: '2-digit',
                                            year: 'numeric'
                                        });
                                        dueDateCell.innerHTML = formattedDate;

                                        // Add the "Due in 7 days" badge
                                        const badge = document.createElement('span');
                                        badge.className = 'badge bg-warning ms-2';
                                        badge.textContent = '⚠ Due in 7 days';
                                        dueDateCell.appendChild(badge);
                                    }
                                }

                                // Refresh the page to update all statuses
                                location.reload();
                            } else {
                                alert(`❌ ${data.message}`);
                                dropdownElement.value = previousStatus;
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            alert("❌ Error updating status.");
                            dropdownElement.value = previousStatus;
                        });
                });

                // Store initial status
                dropdown.dataset.previous = dropdown.value;
            });
        });

        document.getElementById("sendNotificationsBtn").addEventListener("click", function() {
            fetch('notification.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`✅ ${data.message}`);
                    } else {
                        alert("❌ Error sending notifications.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("❌ Error sending notifications.");
                });
        });
    </script>
</body>

</html>