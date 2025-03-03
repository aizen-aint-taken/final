<?php
session_start();
require_once '../config/conn.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('location: ../index.php');
    exit;
}

// elseif (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
//     // header('location: ../index.php');
// }

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$student_id = intval($_GET['id']);

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Student not found.";
    exit;
}

$student = $result->fetch_assoc();

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PROFILE PAGE">
    <meta name="author" content="Ely Gian Ga">
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Poppins', sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        /* .card-header {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            border-radius: 15px 15px 0 0;
        } */

        .card-body {
            padding: 2rem;
        }



        .btn-secondary {
            background: #6a11cb;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
        }

        .btn-secondary:hover {
            background: #2575fc;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 30rem;">
            <div class="card-header text-center">
                <h3>Student Profile</h3>
            </div>
            <div class="card-body text-center">

                <p class="card-text"><i class="fa-solid fa-user"></i><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                <p class="card-text"><i class="fas fa-birthday-cake"></i> <strong>Age:</strong> <?= htmlspecialchars($student['age']) ?></p>
                <p class="card-text"><i class="fas fa-graduation-cap"></i> <strong>Year Level:</strong> <?= htmlspecialchars($student['year']) ?></p>
                <p class="card-text"><i class="fas fa-users"></i> <strong>Section:</strong> <?= htmlspecialchars($student['sect']) ?></p>
                <p class="card-text"><i class="fas fa-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                <a href="student.php" class="btn btn-secondary">Back</a>
            </div>

            <!-- card sa  admin -->
            <!-- <div class="card-body text-center">

                <p class="card-text"><i class="fa-solid fa-user"></i><strong>Name:</strong> <?= htmlspecialchars($student['email']) ?></p>
                <a href="student.php" class="btn btn-secondary">Back</a>
            </div> -->
        </div>
    </div>


    <!-- Books reserved -->







    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>