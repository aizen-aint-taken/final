// Remove all jQuery code and use only vanilla JavaScript for all event listeners and DOM manipulations.

document.addEventListener('DOMContentLoaded', function() {
    // Filter form validation
    var filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(event) {
            var select = document.getElementById('booksFilter');
            if (!select || select.value === 'Select Subject' || select.value === '') {
                event.preventDefault();
                alert('Please select a subject.');
            }
        });
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
                
                // Populate the form fields
                editBookId.value = bookId || '';
                editBookTitle.value = title || '';
                editBookAuthor.value = author || '';
                editBookPublisher.value = publisher || '';
                editBookSourceOfAcquisition.value = source || '';
                editBookPublishedDate.value = published || '';
                editBookLanguage.value = language || '';
                editBookStock.value = stock || '';
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

        searchInput.addEventListener('input', function(e) {
            var searchTerm = e.target.value.toLowerCase().trim();
            var rows = Array.from(tableBody.querySelectorAll('tr:not(.no-results-row)'));
            var hasVisibleRows = false;

            // Remove any previous 'no results' row
            removeNoResultsRow();

            rows.forEach(function(row) {
                var cells = row.getElementsByTagName('td');
                var rowText = '';
                if (cells.length > 0) {
                    rowText = [
                        cells[1] ? cells[1].textContent : '',
                        cells[2] ? cells[2].textContent : '',
                        cells[3] ? cells[3].textContent : '',
                        cells[6] ? cells[6].textContent : ''
                    ].join(' ').toLowerCase();
                }
                if (searchTerm === '' || rowText.includes(searchTerm)) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            if (!hasVisibleRows) {
                var colCount = 9; // Adjust if your table has a different number of columns
                var noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-row';
                var td = document.createElement('td');
                td.colSpan = colCount;
                td.className = 'text-center';
                td.style.background = 'rgba(255,255,255,0.7)';
                td.innerHTML = '<i class="bi bi-info-circle"></i> No books found matching your search criteria';
                noResultsRow.appendChild(td);
                tableBody.appendChild(noResultsRow);
            }
        });
    }
});

function clearSearch() {
    var searchInput = document.getElementById('Search');
    if (searchInput) {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
    }
}
