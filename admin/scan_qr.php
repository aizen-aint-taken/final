<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Scan QR Code - Return Books</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Scan Student QR Code to Return Books</h2>
        <div id="qr-reader" style="width: 350px;"></div>
        <div id="qr-result" class="mt-3"></div>
    </div>
    <script>
        function processQRData(decodedText) {
            let data;
            try {
                data = JSON.parse(decodedText);
            } catch (e) {
                document.getElementById('qr-result').innerHTML = '<div class="alert alert-danger">Invalid QR code format.</div>';
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
                    if (res.success) {
                        document.getElementById('qr-result').innerHTML = '<div class="alert alert-success">' + res.message + '</div>';
                    } else {
                        document.getElementById('qr-result').innerHTML = '<div class="alert alert-danger">' + res.message + '</div>';
                    }
                })
                .catch(() => {
                    document.getElementById('qr-result').innerHTML = '<div class="alert alert-danger">Error processing QR code.</div>';
                });
        }

        let lastResult = null;
        const html5QrCode = new Html5Qrcode("qr-reader");
        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length) {
                html5QrCode.start(
                    cameras[0].id, {
                        fps: 10,
                        qrbox: 250
                    },
                    qrCodeMessage => {
                        if (qrCodeMessage !== lastResult) {
                            lastResult = qrCodeMessage;
                            processQRData(qrCodeMessage);
                        }
                    },
                    errorMessage => {}
                );
            }
        }).catch(err => {
            document.getElementById('qr-result').innerHTML = '<div class="alert alert-danger">Camera error: ' + err + '</div>';
        });
    </script>
</body>

</html>