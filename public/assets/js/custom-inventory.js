// ... existing code ...
$(function() {
    // Initialize all required data loads
    loadMostBorrowedBooks();
    loadImportDeliveryStamps();
});

function loadMostBorrowedBooks() {
    $.post('', {
        action: 'get_most_borrowed_books'
    }, function(response) {
        if (response.success) {
            let html = '';
            response.data.forEach(function(book, index) {
                let statusClass = book.status === 'Available' ? 'badge-success' :
                    book.status === 'Limited' ? 'badge-warning' : 'badge-danger';

                html += `
                    <tr>
                        <td><span class="badge badge-primary">#${index + 1}</span></td>
                        <td>${book.Title}</td>
                        <td><span class="badge badge-info">${book.borrow_count}x</span></td>
                    </tr>
                `;
            });
            
            if (html === '') {
                html = '<tr><td colspan="3" class="text-center">No borrowed books found</td></tr>';
            }
            
            $('#mostBorrowedTable tbody').html(html);
        }
    }, 'json');
}

function loadImportDeliveryStamps() {
    $.post('', {
        action: 'get_import_delivery_stamps'
    }, function(response) {
        if (response.success) {
            // Handle book imports
            let importHtml = '';
            if (response.data.imports && response.data.imports.length > 0) {
                // Limit to 5 imports max
                const importsToShow = response.data.imports.slice(0, 5);
                importsToShow.forEach(function(stamp) {
                    const date = new Date(stamp.date).toLocaleDateString();
                    // Truncate titles if too long
                    let titles = stamp.titles || 'No titles';
                    if (titles.length > 100) {
                        titles = titles.substring(0, 100) + '...';
                    }

                    importHtml += `
                        <div class="activity-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <span class="badge badge-success">📥 Book Import</span>
                                </div>
                                <small class="text-muted">${date}</small>
                            </div>
                            <p class="mb-1"><strong>Books:</strong> ${titles}</p>
                            <p class="mb-0"><strong>Quantity:</strong> ${stamp.count} books | <strong>Sources:</strong> ${stamp.details || 'N/A'}</p>
                        </div>
                        <hr class="my-2">
                    `;
                });
            } else {
                importHtml = '<p class="text-muted text-center">No book import history found</p>';
            }
            $('#bookImportStamps').html(importHtml);

            // Handle library deliveries
            let deliveryHtml = '';
            if (response.data.deliveries && response.data.deliveries.length > 0) {
                // Limit to 5 deliveries max
                const deliveriesToShow = response.data.deliveries.slice(0, 5);
                deliveriesToShow.forEach(function(stamp) {
                    const date = new Date(stamp.date).toLocaleDateString();
                    // Truncate titles if too long
                    let titles = stamp.titles || 'Multiple Title';
                    if (titles && titles.length > 100) {
                        titles = titles.substring(0, 100) + '...';
                    }

                    deliveryHtml += `
                        <div class="activity-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <span class="badge badge-info">🚚 Library Delivery</span>
                                </div>
                                <small class="text-muted">${date}</small>
                            </div>
                            <p class="mb-1"><strong>Destination:</strong> ${stamp.details || 'N/A'}</p>
                            <p class="mb-0"><strong>Book:</strong> ${titles || 'N/A'} | <strong>Quantity:</strong> ${stamp.count} copies</p>
                        </div>
                        <hr class="my-2">
                    `;
                });
            } else {
                deliveryHtml = '<p class="text-muted text-center">No delivery history found</p>';
            }
            $('#libraryDeliveryStamps').html(deliveryHtml);
        } else {
            // Handle error case
            $('#bookImportStamps').html('<p class="text-muted text-center">Error loading import history</p>');
            $('#libraryDeliveryStamps').html('<p class="text-muted text-center">Error loading delivery history</p>');
        }
    }, 'json').fail(function() {
        // Handle AJAX failure
        $('#bookImportStamps').html('<p class="text-muted text-center">Failed to load import history</p>');
        $('#libraryDeliveryStamps').html('<p class="text-muted text-center">Failed to load delivery history</p>');
    });
}