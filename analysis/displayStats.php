<?php
session_start();
require_once '../config/conn.php';

// Add session check if needed
if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('location: ../index.php');
    exit;
}

// Include header and sidebar
include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="stats.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

</head>

<body>
    <div id="loading" class="loading"></div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="text-center">Library Analytics Dashboard</h1>
        </div>

        <div class="charts-grid">

            <div class="chart-card borrowed-card">
                <h2 class="chart-title">Top 5 Most Borrowed Books</h2>
                <div class="chart-container">
                    <canvas id="topBorrowedChart"></canvas>
                </div>
            </div>


            <div class="chart-card stock-card">
                <h2 class="chart-title">Book Stock per Subject</h2>
                <div class="chart-container">
                    <canvas id="bookStockChart"></canvas>
                </div>
            </div>
            <div class="chart-card status-card">
                <h2 class="chart-title">Borrowing Status Distribution</h2>
                <div class="chart-container">
                    <canvas id="reservationStatusChart"></canvas>
                </div>
            </div>

            <div class="chart-card trends-card">
                <h2 class="chart-title">Monthly Borrowing Trends</h2>
                <div class="chart-container">
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>
            </div>

            <div class="chart-card borrow-stats-card" style="border-top: 7px solid black">
                <h2 class="chart-title">Returned vs Borrowed Books</h2>
                <div class="chart-container">
                    <canvas id="borrowStatsChart"></canvas>
                </div>
            </div>


        </div>
    </div>

    <script src="stats.js"></script>
</body>

</html>