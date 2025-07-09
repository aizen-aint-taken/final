<?php

include '../includes/header.php';
include '../includes/sidebar.php';



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Scan QR Code - Return Books</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link rel="stylesheet" href="../public/assets/css/scan_qr.css">
</head>

<body>
    <div class="container">
        <div class="card qr-card">
            <div class="card-header">
                <i class="fas fa-qrcode"></i>
                <div class="card-title">Scan Student QR Code to Return Books</div>
            </div>
            <div class="card-body">
                <div id="qr-reader"></div>
                <div class="text-center text-tip">Tip: Hold the QR code steady. The scanner works even if the code is slightly tilted or rotated.</div>
                <div id="qr-result" class="mt-4"></div>
            </div>
        </div>
    </div>
    <script src="../public/assets/js/scan_qr.js"></script>
</body>

</html>