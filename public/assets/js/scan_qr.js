let lastResult = null;
let lastStatus = null;
let processing = false;
const qrResult = document.getElementById('qr-result');

function showSpinner() {
    qrResult.innerHTML = '<div class="d-flex justify-content-center align-items-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
}

function processQRData(decodedText) {
    if (processing) return;
    processing = true;
    showSpinner();
    let data;
    try {
        data = JSON.parse(decodedText);
    } catch (e) {
        qrResult.innerHTML = '<div class="error-icon text-center"><i class="fas fa-times-circle"></i></div><div class="alert alert-danger text-center">Invalid QR code format.</div>';
        processing = false;
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
            let booksHtml = '';
            if (res.returned_titles && res.returned_titles.length > 0) {
                booksHtml = `<div class='returned-list'><strong>Returned Book(s):</strong><ul class='list-group mt-2'>` +
                    res.returned_titles.map(title => `<li class='list-group-item'>${title}</li>`).join('') +
                    `</ul></div>`;
            }

            if (decodedText === lastResult && res.message.includes('already returned')) {
                qrResult.innerHTML = `<div class='info-icon text-center'><i class='fas fa-info-circle'></i></div>` + nameHtml + `<div class='alert alert-info text-center'>These books have already been returned. No further action needed.</div>` + booksHtml;
            } else if (res.success) {
                qrResult.innerHTML = `<div class='success-icon text-center'><i class='fas fa-check-circle'></i></div>` + nameHtml + `<div class='alert alert-success text-center'>${res.message}</div>` + booksHtml;
            } else {
                qrResult.innerHTML = `<div class='error-icon text-center'><i class='fas fa-times-circle'></i></div>` + nameHtml + `<div class='alert alert-danger text-center'>${res.message}</div>` + booksHtml;
            }
            lastStatus = res.success;
            processing = false;
        })
        .catch(() => {
            qrResult.innerHTML = `<div class='error-icon text-center'><i class='fas fa-times-circle'></i></div><div class="alert alert-danger text-center">Error processing QR code.</div>`;
            processing = false;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const fa = document.createElement('link');
    fa.rel = 'stylesheet';
    fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
    document.head.appendChild(fa);

    const html5QrCode = new Html5Qrcode("qr-reader");
    Html5Qrcode.getCameras().then(cameras => {
        if (cameras && cameras.length) {
            html5QrCode.start(
                cameras[0].id, {
                    fps: 30,
                    qrbox: 300
                },
                qrCodeMessage => {
                    processQRData(qrCodeMessage);
                    lastResult = qrCodeMessage;
                },
                errorMessage => {}
            );
        }
    }).catch(err => {
        qrResult.innerHTML = `<div class='error-icon text-center'><i class='fas fa-times-circle'></i></div><div class="alert alert-danger text-center">Camera error: ${err}</div>`;
    });
}); 