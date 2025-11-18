document.addEventListener('DOMContentLoaded', function() {
console.log("✅ userReservations.js loaded and running");

    // Add custom styles for enhanced mobile view
    const customStyles = `
        <style>
            .reservation-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .reservation-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            
            .detail-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            
            .detail-item:last-child {
                border-bottom: none;
            }
            
            .detail-label {
                flex: 1;
            }
            
            .detail-value {
                flex: 1;
                text-align: right;
            }
            
            .card-footer {
                border-top: 1px solid rgba(0,0,0,0.125);
            }
            
            @media (max-width: 767.98px) {
                .reservation-card {
                    margin-bottom: 1rem;
                }
                
                .detail-item {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .detail-value {
                    text-align: left;
                    margin-top: 0.25rem;
                }
            }
        </style>
    `;
    
    // Append custom styles to head
    if (!$('#reservation-custom-styles').length) {
        $('head').append(`<div id="reservation-custom-styles">${customStyles}</div>`);
    }

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

            tableBody.html('<tr><td colspan="4" class="text-center">No active status found</td></tr>');
            mobileContainer.empty();
            noStatusMessage.show();
        } else {

            noStatusMessage.hide();

            const rows = data.map(item => `
                <tr>
                    <td>${item.RESERVEDATE || 'N/A'}</td>
                    <td>${item.RETURNEDDATE ? item.RETURNEDDATE : '<span class="text-danger fw-bold">Not yet returned</span>'}</td>
                    <td>${item.BOOK_TITLE || 'Unknown Book'}</td>
                    <td><span class="badge ${getStatusBadgeClass(item.STATUS)}">${item.STATUS || 'Unknown'}</span></td>
                </tr>
            `).join('');
            tableBody.html(rows);


            const mobileContent = data.map(item => `
                <div class="card reservation-card mb-3 shadow-sm border-0 rounded-3">
                    <div class="card-header bg-primary text-white rounded-top-3">
                        <h5 class="card-title reservation-title mb-0 text-truncate">${item.BOOK_TITLE || 'Unknown Book'}</h5>
                    </div>
                    <div class="card-body">
                        <div class="reservation-details">
                            <div class="detail-item mb-2">
                                <div class="detail-label fw-bold text-muted small">Borrowed Date</div>
                                <div class="detail-value">${item.RESERVEDATE || 'N/A'}</div>
                            </div>
                            
                            <div class="detail-item mb-2">
                                <div class="detail-label fw-bold text-muted small">Returned Date</div>
                                <div class="detail-value">
                                    ${item.RETURNEDDATE ? item.RETURNEDDATE : '<span class="text-danger">Not yet returned</span>'}
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label fw-bold text-muted small">Status</div>
                                <div class="detail-value">
                                    <span class="badge ${getStatusBadgeClass(item.STATUS)} fs-6">${item.STATUS || 'Unknown'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light rounded-bottom-3">
                        <small class="text-muted">Reservation Details</small>
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