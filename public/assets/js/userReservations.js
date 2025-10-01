document.addEventListener('DOMContentLoaded', function() {

    $(document).on('click', '#filterButton', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const status = $('#form-select').val();
        const $button = $(this);
        const originalHtml = $button.html();


        $button.html('<span class="spinner-border spinner-border-sm me-2"></span>Filtering...');

        $.ajax({
            url: window.location.pathname,
            method: 'GET',
            data: {
                status: status
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (!response.data) {
                    console.error('Invalid response:', response);
                    return;
                }

                $('#reservationTable tbody').empty();
                $('#mobileReservations').empty();
                $('#noStatusMessage').hide();


                updateViews(response.data);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('An error occurred while filtering. Please try again.');
            },
            complete: function() {

                $button.html(originalHtml);
            }
        });
    });

    function updateViews(data) {
        const tableBody = $('#reservationTable tbody');
        const mobileContainer = $('#mobileReservations');
        const noStatusMessage = $('#noStatusMessage');

        if (data.length === 0) {

            tableBody.html('<tr><td colspan="3" class="text-center">No reservations found</td></tr>');
            mobileContainer.empty();
            noStatusMessage.show();
        } else {

            noStatusMessage.hide();

            const rows = data.map(item => `
                <tr>
                    <td>${item.RESERVEDATE}</td>
                    <td>${item.BOOK_TITLE}</td>
                    <td><span class="badge ${getStatusBadgeClass(item.STATUS)}">${item.STATUS}</span></td>
                </tr>
            `).join('');
            tableBody.html(rows);


            const mobileContent = data.map(item => `
                <div class="card reservation-card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title reservation-title mb-0">${item.BOOK_TITLE}</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <strong>Reserved Date:</strong><br>
                            ${item.RESERVEDATE}
                        </p>
                        <span class="badge ${getStatusBadgeClass(item.STATUS)}">
                            ${item.STATUS}
                        </span>
                    </div>
                </div>
            `).join('');


            mobileContainer.html(mobileContent);
        }
    }
});

function getStatusBadgeClass(status) {
    switch (status) {
        case 'Borrowed':
            return 'badge-success';
        case 'Rejected':
            return 'badge-danger';
        case 'Returned':
            return 'badge-warning';
        case 'Pending':
            return 'badge-secondary';
        default:
            return 'badge-secondary';
    }
}