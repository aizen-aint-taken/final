<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="STATISTICS PAGE">
    <meta name="author" content="Ely Gian Ga">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Library Analytics Dashboard</title>
    <link rel="stylesheet" href="stats.css">
    <style>
        /* Body */
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f4f4f9, #e3e8f2);
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
        }

        /* Navbar */
        .navbar {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            z-index: 10;
        }

        .navbar h1 {
            margin: 0;
            font-size: 1.8em;
            font-weight: 500;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 1em;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: #ecf0f1;
            text-decoration: underline;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background-color: #34495e;
            color: white;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            z-index: 5;
        }

        .sidebar h3 {
            margin-bottom: 30px;
            font-size: 1.4em;
            color: #ecf0f1;
            font-weight: 600;
            text-transform: uppercase;
            display: flex;
            align-items: center;
        }

        .sidebar h3 i {
            margin-right: 10px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            width: 100%;
        }

        .sidebar ul li {
            margin-bottom: 15px;
            width: 100%;
        }

        .sidebar ul li a {
            color: #ecf0f1;
            text-decoration: none;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .sidebar ul li a i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .sidebar ul li a:hover {
            background-color: #3498db;
            color: white;
            font-weight: 500;
        }

        .sidebar ul li a.active {
            background-color: #2980b9;
            font-weight: 600;
        }

        /* Content */
        .content {
            margin-left: 240px;
            padding: 30px;
            flex: 1;
        }

        /* Heading */
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2em;
        }

        /* Cards Layout */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: auto;
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        .card h3 {
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.4em;
            color: #34495e;
        }

        .chart-container {
            width: 100%;
            max-width: 450px;
            height: 320px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #2c3e50;
            color: white;
            margin-top: 40px;
            font-size: 1em;
        }

        /* Responsive Sidebar */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
            }

            .sidebar ul li {
                margin-bottom: 15px;
            }

            .content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- <div class="navbar">
        <h1>Library Analytics Dashboard 📊</h1>
        <div>
            <a href="../admin/index.php">Home</a>
            <a href="#">Reports</a>
            <a href="#">Settings</a>
        </div>
    </div> -->

    <div class="sidebar">
        <h3><i class="fas fa-tachometer-alt"></i> Dashboard</h3>
        <ul>
            <!-- <li><a href="#" class="active"><i class="fas fa-home"></i> Overview</a></li>
            <li><a href="#"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="#"><i class="fas fa-book"></i> Books</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i> Statistics</a></li> -->
            <li><a href="../admin/index.php"><i class="fas fa-house"></i> Home</a></li>
        </ul>
    </div>

    <div class="content">
        <h2>Library Analytics Dashboard 📊</h2>

        <div class="container">
            <div class="card">
                <h3>📅 Monthly Reservations Trend</h3>
                <div class="chart-container">
                    <canvas id="monthlyReservationsChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3>📚 Books Availability (Stock Levels)</h3>
                <div class="chart-container">
                    <canvas id="stockLevelsChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3>📈 User Activity (Daily)</h3>
                <div class="chart-container">
                    <canvas id="userActivityChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3>📖 Top Borrowed Books</h3>
                <div class="chart-container">
                    <canvas id="topBorrowedBooksChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="stats.js"></script>
</body>

</html>