<?php
session_start();
require_once '../config/conn.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['usertype'] !== 'a' && $_SESSION['usertype'] !== 'sa')) {
    header('location: ../index.php');
    exit;
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - MNHS Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../maharlika/logo.jpg') no-repeat center center;
            background-size: cover;
            opacity: 0.1;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-5px);
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #1e3c72;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }

        .feature-list li:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .feature-list li i {
            color: #1e3c72;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .hero-section {
                padding: 2rem 1rem;
                margin-top: 60px;
                border-top: 10px solid violet;
            }

            .section-card {
                padding: 1.5rem;

            }
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .social-links a {
            color: #1e3c72;
            font-size: 1.5rem;
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .social-links a:hover {
            color: #2a5298;
            transform: translateY(-3px);
        }

        */
    </style>
</head>

<body>
    <div class="main-content">
        <div class="hero-section">
            <div class="hero-content text-center">
                <h1 class="display-4 fw-bold mb-3">Maharlika National High School </h1>
                <p class="lead">Empowering Minds, Enriching Lives through Knowledge</p>
            </div>
        </div>

        <div class="section-card" style="border-top: 10px solid violet;">
            <h2 class="text-primary mb-4">About Our Library</h2>
            <p class="lead">
                Welcome to MNHS Library, your premier destination for academic excellence and intellectual growth.
                We provide a dynamic learning environment that supports both traditional and digital learning needs.
            </p>
            <p>
                Our mission is to foster a culture of continuous learning and innovation, providing resources that
                empower students and faculty alike in their academic pursuits.
            </p>
        </div>

        <div class="section-card" style="border-top: 10px solid violet;">
            <h2 class="text-primary mb-4 text-center">Our Services</h2>
            <ul class="feature-list">
                <li><i class="fas fa-book"></i> Extensive Collection of Academic Resources</li>
                <li><i class="fas fa-laptop"></i> Digital Learning Materials</li>
                <li><i class="fas fa-users"></i> Study Areas and Group Discussion Rooms</li>
                <li><i class="fas fa-chalkboard-teacher"></i> Research Assistance</li>
                <li><i class="fas fa-wifi"></i> Free Wi-Fi Access</li>
            </ul>
        </div>

        <div class="section-card" style="border-top: 10px solid violet;">
            <h2 class="text-primary mb-4 text-center">Connect With Us</h2>
            <p>
                Stay updated with our latest resources, events, and announcements through our social media channels.
            </p>
            <div class="social-links">
                <a href="https://www.facebook.com/profile.php?id=100054664334834" title="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" title="Email"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>