<?php

session_start();

require_once '../config/conn.php';

if (
    !isset($_SESSION['user'])
    || empty($_SESSION['user'])
) {
    header('location: ../index.php');
    exit;
}


$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Modify the query to filter by subject if one is selected
if ($subject) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE Genre = :subject");
    $stmt->execute(['subject' => $subject]);
} else {
    $stmt = $conn->query("SELECT * FROM books");
}

$books = $stmt->fetch_all();


include('../includes/header.php');
include('../includes/sidebar.php');
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UI PAGE">
    <meta name="author" content="Ely Gian Ga">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/css/admin_index.css">
    <title>LYR Inventory</title>
</head>

<body>
    <div class="content-wrapper">

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs justify-content-center" id="dashboard-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="literature-tab" data-bs-toggle="tab" href="#literature" role="tab" aria-controls="literature" aria-selected="true">
                            <i class="fas fa-book"></i> Literature
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="math-tab" data-bs-toggle="tab" href="#math" role="tab" aria-controls="math" aria-selected="false">
                            <i class="fas fa-calculator"></i> Math
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="science-tab" data-bs-toggle="tab" href="#science" role="tab" aria-controls="science" aria-selected="false">
                            <i class="fas fa-atom"></i> Science
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" role="tab" aria-controls="history" aria-selected="false">
                            <i class="fas fa-history"></i> History
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-5" id="dashboard-tabContent">
                    <!-- Literature Tab -->
                    <div class="tab-pane fade show active" id="literature" role="tabpanel" aria-labelledby="literature-tab">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-book"></i> Literature
                            </div>
                            <div class="card-body">
                                <?php
                                include("../categories/Books.php");
                                ?>
                            </div>
                        </div>
                    </div>
                    <!-- Math Tab -->
                    <div class="tab-pane fade" id="math" role="tabpanel" aria-labelledby="math-tab">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-calculator"></i> Math
                            </div>
                            <div class="card-body">
                                <?php
                                include("../categories/Math.php");
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Science Tab -->
                    <div class="tab-pane fade" id="science" role="tabpanel" aria-labelledby="science-tab">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-atom"></i> Science
                            </div>
                            <div class="card-body">
                                <?php

                                include("../categories/Science.php");

                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include('../includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>