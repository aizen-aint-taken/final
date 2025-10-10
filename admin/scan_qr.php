<?php
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

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
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
        }

        .qr-card {
            max-width: 430px;
            margin: 48px auto 0 auto;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.13);
            border-radius: 1.25rem;
            border: 1.5px solid #e3e6f0;
            background: #fff;
            overflow: hidden;
        }

        .qr-card .card-header {
            background: linear-gradient(90deg, rgb(225, 229, 232) 0%, rgb(198, 202, 204) 100%);
            color: #fff;
            border-bottom: none;
            padding: 1.5rem 1rem 1rem 1rem;
            text-align: center;
        }

        .qr-card .card-header i {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .qr-card .card-title {
            font-size: 1.35rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        #qr-reader {
            width: 100% !important;
            margin: 0 auto;
            border-radius: 0.75rem;
            border: 1.5px solid #e3e6f0;
            background: #f8fafc;
            padding: 0.5rem 0.5rem 0.2rem 0.5rem;
        }

        .success-icon,
        .error-icon,
        .info-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            animation: pop 0.4s cubic-bezier(.68, -0.55, .27, 1.55);
        }

        .success-icon {
            color: #28a745;
        }

        .error-icon {
            color: #dc3545;
        }

        .info-icon {
            color: #0d6efd;
        }

        @keyframes pop {
            0% {
                transform: scale(0.7);
                opacity: 0.2;
            }

            80% {
                transform: scale(1.15);
                opacity: 1;
            }

            100% {
                transform: scale(1);
            }
        }

        .returned-list {
            margin-top: 1rem;
        }

        .returned-list ul {
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.07);
        }

        .alert {
            border-radius: 0.5rem;
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.07);
        }

        .text-tip {
            color: #6c757d;
            font-size: 0.98rem;
            margin: 0.5rem 0 1.2rem 0;
        }

        @media (max-width: 576px) {
            .qr-card {
                margin: 10px;
            }

            .qr-card .card-header {
                padding: 1.2rem 0.5rem 0.8rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card qr-card">
            <div class="card-header">
                <i class="fas fa-qrcode"></i>
                <div class="card-title">Scan Student QR Code to Return Books</div>
            </div>
            <div class="card-body" style="text-align: center;">
                <div id="qr-reader"></div>
                <div class="text-center text-tip">Tip: Hold the QR code steady. The scanner works even if the code is slightly tilted or rotated.</div>
                <div id="qr-result" class="mt-4"></div>
            </div>
        </div>
    </div>
    <script>
        let lastResult = null;
        let processing = false;
        const qrResult = document.getElementById('qr-result');
        const html5QrCode = new Html5Qrcode("qr-reader");

        function showSpinner() {
            qrResult.innerHTML = `
        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;
        }

        function processQRData(decodedText) {
            if (processing) return;
            processing = true;
            showSpinner();

            let data;
            try {
                data = JSON.parse(decodedText);
            } catch (e) {
                qrResult.innerHTML = `
            <div class="error-icon text-center"><i class="fas fa-times-circle"></i></div>
            <div class="alert alert-danger text-center">Invalid QR code format.</div>`;
                processing = false;
                restartScanner();
                return;
            }

            fetch('process_qr_return.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(res => {
                    let nameHtml = res.student_name ? `<div class='mb-2 text-center'><strong>Student Name:</strong> ${res.student_name}</div>` : '';
                    let booksHtml = res.returned_titles?.length ?
                        `<div class='returned-list'><strong>Returned Book(s):</strong><ul class='list-group mt-2'>
                ${res.returned_titles.map(title => `<li class='list-group-item'>${title}</li>`).join('')}
                </ul></div>` : '';

                    if (res.success) {
                        qrResult.innerHTML = `
                <div class='success-icon text-center'><i class='fas fa-check-circle'></i></div>
                ${nameHtml}<div class='alert alert-success text-center'>${res.message}</div>${booksHtml}`;
                    } else {
                        qrResult.innerHTML = `
                <div class='error-icon text-center'><i class='fas fa-times-circle'></i></div>
                ${nameHtml}<div class='alert alert-primary text-center'>${res.message}</div>${booksHtml}`;
                    }


                    setTimeout(restartScanner, 3000);
                })
                .catch(() => {
                    qrResult.innerHTML = `
            <div class='error-icon text-center'><i class='fas fa-times-circle'></i></div>
            <div class="alert alert-danger text-center">Error processing QR code.</div>`;
                    setTimeout(restartScanner, 3000);
                });
        }

        function restartScanner() {
            processing = false;
            html5QrCode.stop().then(() => {
                html5QrCode.start({
                    facingMode: "environment"
                }, {
                    fps: 10,
                    qrbox: 400
                }, qrCodeMessage => {
                    if (qrCodeMessage !== lastResult) {
                        lastResult = qrCodeMessage;
                        processQRData(qrCodeMessage);
                    }
                });
            }).catch(err => {
                qrResult.innerHTML = `<div class='alert alert-danger text-center'>Camera restart error: ${err}</div>`;
            });
        }

        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length) {
                html5QrCode.start(
                    cameras[0].id, {
                        fps: 10,
                        qrbox: 400
                    },
                    qrCodeMessage => {
                        if (!processing && qrCodeMessage !== lastResult) {
                            lastResult = qrCodeMessage;
                            processQRData(qrCodeMessage);
                        }
                    }
                );
            }
        }).catch(err => {
            qrResult.innerHTML = `<div class='alert alert-danger text-center'>Camera error: ${err}</div>`;
        });
    </script>
</body>

</html>