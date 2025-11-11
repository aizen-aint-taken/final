// Remove all jQuery code and use only vanilla JavaScript for all event listeners and DOM manipulations.

document.addEventListener('DOMContentLoaded', function() {
    // Filter form validation
    var filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(event) {
            
        });
        
        var subjectSelect = document.getElementById('booksFilter');
        if (subjectSelect) {
            subjectSelect.addEventListener('change', function() {
                // Submit form for any selection, including "All Subjects" (empty value)
                filterForm.submit();
            });
        }
    }

    // Delete button modal population
    document.addEventListener('click', function(event) {
        // Find the closest delete button (handles clicks on child elements like icons)
        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton) {
            var deleteBookId = document.getElementById('deleteBookId');
            var deleteBookIdDisplay = document.getElementById('deleteBookIdDisplay');
            if (deleteBookId) {
                const bookId = deleteButton.getAttribute('data-id');
                console.log('Delete button clicked for book ID:', bookId); // Debug log
                deleteBookId.value = bookId;
                if (deleteBookIdDisplay) {
                    deleteBookIdDisplay.textContent = bookId;
                }
                console.log('Hidden input value set to:', deleteBookId.value); // Debug log
            } else {
                console.error('deleteBookId element not found!');
            }
        }
    });

    // Edit button modal population
    document.addEventListener('click', function(event) {
        // Find the closest edit button (handles clicks on child elements like icons)
        const editButton = event.target.closest('.edit-btn');
        if (editButton) {
            var editBookId = document.getElementById('editBookId');
            var editBookTitle = document.getElementById('editBookTitle');
            var editBookAuthor = document.getElementById('editBookAuthor');
            var editBookPublisher = document.getElementById('editBookPublisher');
            var editBookSourceOfAcquisition = document.getElementById('editBookSourceOfAcquisition');
            var editBookPublishedDate = document.getElementById('editBookPublishedDate');
            var editBookLanguage = document.getElementById('editBookLanguage');
            var editBookStock = document.getElementById('editBookStock');
            var editBookGradeLevel = document.getElementById('editBookGradeLevel');
            var editBookQuantityDelivered = document.getElementById('editBookQuantityDelivered');
            var editBookQuantityAllocated = document.getElementById('editBookQuantityAllocated');
            var editBookDeliveryDate = document.getElementById('editBookDeliveryDate');
            var editBookDeliverySite = document.getElementById('editBookDeliverySite');
            
            if (editBookId && editBookTitle && editBookAuthor && editBookPublisher && editBookSourceOfAcquisition && editBookPublishedDate && editBookLanguage && editBookStock) {
                // Get data from the button attributes
                const bookId = editButton.getAttribute('data-id');
                const title = editButton.getAttribute('data-title');
                const author = editButton.getAttribute('data-author');
                const publisher = editButton.getAttribute('data-publisher');
                const source = editButton.getAttribute('data-source');
                const published = editButton.getAttribute('data-published');
                const language = editButton.getAttribute('data-language');
                const stock = editButton.getAttribute('data-stock');
                const gradeLevel = editButton.getAttribute('data-grade-level');
                const quantityDelivered = editButton.getAttribute('data-quantity-delivered');
                const quantityAllocated = editButton.getAttribute('data-quantity-allocated');
                const deliveryDate = editButton.getAttribute('data-delivery-date');
                const deliverySite = editButton.getAttribute('data-delivery-site');
                
                // Populate the form fields
                editBookId.value = bookId || '';
                editBookTitle.value = title || '';
                editBookAuthor.value = author || '';
                editBookPublisher.value = publisher || '';
                editBookSourceOfAcquisition.value = source || '';
                editBookPublishedDate.value = published || '';
                editBookLanguage.value = language || '';
                editBookStock.value = stock || '';
                
                // Populate new fields if they exist
                if (editBookGradeLevel) editBookGradeLevel.value = gradeLevel || '';
                if (editBookQuantityDelivered) editBookQuantityDelivered.value = quantityDelivered || '0';
                if (editBookQuantityAllocated) editBookQuantityAllocated.value = quantityAllocated || '0';
                if (editBookDeliveryDate) editBookDeliveryDate.value = deliveryDate || '';
                if (editBookDeliverySite) editBookDeliverySite.value = deliverySite || 'MAHARLIKA NHS';
            }
        }
    });

    // Export form confirmation
    var exportForm = document.getElementById('exportForm');
    if (exportForm) {
        exportForm.addEventListener('submit', function(event) {
            var confirmExport = confirm('Are you sure you want to export the Excel file?');
            if (!confirmExport) {
                event.preventDefault();
            } else {
                alert('Exporting Excel file... Please wait.');
            }
        });
    }

    // Search logic with no results message inside the table
    var searchInput = document.getElementById('Search');
    var tableBody = document.querySelector('table tbody');
    var table = document.querySelector('.table');
    if (searchInput && tableBody && table) {
        function removeNoResultsRow() {
            var noResultsRow = tableBody.querySelector('.no-results-row');
            if (noResultsRow) {
                tableBody.removeChild(noResultsRow);
            }
        }

        // Handle real-time search with delay
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                var searchTerm = e.target.value.toLowerCase().trim();
                
                // If search term is empty, reload the page to show all books
                if (searchTerm === '') {
                    window.location.href = 'Books.php';
                    return;
                }
                
                // Redirect to search results page
                window.location.href = 'Books.php?search=' + encodeURIComponent(searchTerm);
            }, 500); // 500ms delay to avoid too many requests
        });
    }
});

function clearSearch() {
    var searchInput = document.getElementById('Search');
    if (searchInput) {
        searchInput.value = '';
        // Redirect to main page when clearing search
        window.location.href = 'Books.php';
    }
}