// Reservation counter functionality for sidebar
document.addEventListener('DOMContentLoaded', function() {
    const reservationBadge = document.getElementById('reservationCount');
    
    if (reservationBadge) {
        // Fetch reservation count via AJAX
        fetch('../users/fetch_reservations.php')
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    reservationBadge.textContent = data.count;
                    reservationBadge.style.display = 'flex';
                } else {
                    reservationBadge.style.display = 'none';
                }
            })
            .catch(error => {
                console.log('Reservation count fetch failed:', error);
                reservationBadge.style.display = 'none';
            });
    }
});