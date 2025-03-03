<?php
session_start();
include("../config/conn.php");
$studentId = $_SESSION['student_id'];


$reservationsQuery = "SELECT
    U.name AS USERNAME,
    U.email AS EMAIL,
    R.ReserveDate AS RESERVEDATE,
    DATE_ADD(R.ReserveDate, INTERVAL 7 DAY) AS DUEDATE,
    B.Title AS BOOK_TITLE,
    R.STATUS AS STATUS
FROM `reservations` AS R
INNER JOIN users AS U ON R.StudentID = U.id
INNER JOIN books AS B ON R.BookID = B.BookID
WHERE U.id = '$studentId'";



if (isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] != 'All') {
    $status = $_GET['status'];
    $reservationsQuery .= " AND R.STATUS = '$status'";
} else {
    $status = "no status found";
}

$reservations = $conn->query($reservationsQuery);


if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    $reservationData = [];
    while ($row = $reservations->fetch_assoc()) {
        $reservationData[] = $row;
    }
    echo json_encode($reservationData);
    exit;
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
    </style>
</head>

<body>
    <?php include("../users/sidebar.php"); ?>

    <div class="content-wrapper" style="margin-left: 250px;">
        <div class="container mt-5">
            <select class="select" name="status" id="form-select" onchange="filterStatus()">
                <option value="All">All</option>
                <!-- <option value="Reserved">Reserved</option> -->
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Returned">Returned</option>
                <option value="Pending">Pending</option>
            </select>

            <div class="card shadow-lg">
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
                        <tbody id="reservationTableBody">

                        </tbody>
                    </table>
                </div>
            </div>
            <div id="noStatusMessage" class="alert alert-warning mt-3">
                No Status found for the selected filter.
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
                    // console.log(reservations);
                    const tableBody = document.getElementById('reservationTableBody');
                    const noStatusMessage = document.getElementById('noStatusMessage');
                    tableBody.innerHTML = '';

                    reservations.length === 0 ? noStatusMessage.style.display = 'block' : noStatusMessage.style.display = 'none';


                    reservations.forEach(function(reserve) {
                        let statusText = reserve.STATUS;
                        let statusClass = 'badge-secondary';
                        if (reserve.STATUS === 'Approved') {
                            statusClass = 'badge-success';
                        } else if (reserve.STATUS === 'Rejected') {
                            statusClass = 'badge-danger';
                        } else if (reserve.STATUS === 'Returned') {
                            statusClass = 'badge-warning';
                        } else {
                            statusText = "No Status found";
                            statusClass = 'badge-secondary';

                        }

                        const row = `
                            <tr>
                                <td>${reserve.RESERVEDATE}</td>
                                <td>${reserve.BOOK_TITLE}</td>
                                <td><span class="badge ${statusClass} fs-6">${reserve.STATUS}</span></td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                        console.log(row)
                    });
                }
            });
        }


        $(document).ready(function() {
            filterStatus();
        });
    </script>
</body>

</html>