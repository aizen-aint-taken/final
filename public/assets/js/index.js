document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('modalId');
    modal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const bookId = button.getAttribute('data-id');
        const bookTitle = button.getAttribute('data-title');
        const bookAuthor = button.getAttribute('data-author');

        document.getElementById('reserveBookId').value = bookId;
        document.getElementById('reserveBookTitle').value = bookTitle;
        document.getElementById('reserveBookAuthor').value = bookAuthor;
    });

    const searchBar = document.getElementById('searchBar');
    const searchForm = searchBar.closest('form');

    // Handle form submission
    searchForm.addEventListener('submit', function(e) {
        if (!searchBar.value.trim()) {
            e.preventDefault(); // Prevent empty searches
            return;
        }
    });

    // Handle real-time search for current page
    searchBar.addEventListener('input', function() {
        const searchValue = this.value.toLowerCase().trim();
        const tableRows = document.querySelectorAll('#booksTable tbody tr');
        const mobileCards = document.querySelectorAll('.book-card');
        let hasResults = false;

    
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            if (rowText.includes(searchValue)) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });

    
        mobileCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            if (cardText.includes(searchValue)) {
                card.style.display = '';
                hasResults = true;
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide no results message
        const noResultsMsg = document.getElementById('noResultsMessage');
        if (noResultsMsg) {
            noResultsMsg.style.display = hasResults ? 'none' : 'block';
        }
    });


    const booksFilter = document.getElementById('booksFilter');
    if (booksFilter) {
        booksFilter.addEventListener('change', function() {
            this.classList.add('changed');
            setTimeout(() => this.classList.remove('changed'), 300);
        });
    }


    searchBar.addEventListener('input', function() {
        this.classList.add('typing');
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.classList.remove('typing'), 300);
    });
});