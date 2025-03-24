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
    <style>
        .main-content {
            padding: 20px;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
            }
        }

        .dashboard-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .charts-grid {
            display: grid;
            gap: 20px;
            padding: 10px;
        }

        @media (min-width: 768px) {
            .charts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-5px);
        }

        .chart-title {
            color: #1e3c72;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .main-content {
                padding-top: 70px;
                /* Space for menu toggle */
            }

            .dashboard-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .chart-card {
                margin-bottom: 15px;
            }

            .chart-container {
                height: 250px;
                /* Smaller height for mobile */
            }

            .chart-title {
                font-size: 1rem;
            }
        }

        /* Custom styles for each chart type */
        .borrowed-card {
            border-top: 5px solid #ff6b6b;
        }

        .stock-card {
            border-top: 5px solid #4ecdc4;
        }

        .status-card {
            border-top: 5px solid #45b7d1;
        }

        .trends-card {
            border-top: 5px solid #96ceb4;
        }

        .borrow-stats-card {
            border-top: 5px solid #6c5ce7;
        }

        /* Loading animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading::after {
            content: '';
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #1e3c72;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div id="loading" class="loading"></div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Library Analytics Dashboard</h1>
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

            <div class="chart-card borrow-stats-card">
                <h2 class="chart-title">Returned vs Borrowed Books</h2>
                <div class="chart-container">
                    <canvas id="borrowStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="stats.js"></script>
    <script>
        // Hide loading animation when page is loaded
        window.addEventListener('load', function() {
            document.getElementById('loading').style.display = 'none';
        });

        // Adjust chart sizes when window is resized
        window.addEventListener('resize', function() {
            Chart.instances.forEach(chart => {
                chart.resize();
            });
        });
    </script>
</body>

</html>