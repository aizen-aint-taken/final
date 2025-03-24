$(document).ready(() => {

    $("#Search").on("input", function () {
        let query = $(this).val().toLowerCase();
        console.log(query);

        $("table tbody tr, .card").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(query) > -1);
        });
    });

    $("#Search").on("focus", function () {
        if ($(this).val() === "") {
            $("table tbody tr, .card").show();
        }
    });

   
    function clearSearch() {
        $("#Search").val("");
        $("table tbody tr, .card").show();
    }

  
    $("#filterForm").on("submit", function (event) {
        var select = $("#booksFilter");
        if (select.val() === "Select Subject" || select.val() === "") {
            event.preventDefault();
            alert("Please select a subject.");
        }
    });


    $(document).on("click", ".delete-btn", function () {
        $("#deleteBookId").val($(this).data("id"));
    });

 
    $(document).on("click", ".edit-btn", function () {
        $("#editBookId").val($(this).data("id"));
        $("#editBookTitle").val($(this).data("title"));
        $("#editBookAuthor").val($(this).data("author"));
        $("#editBookPublisher").val($(this).data("publisher"));
        $("#editBookSourceOfAcquisition").val($(this).data("source"));
        $("#editBookPublishedDate").val($(this).data("published"));
        $("#editBookLanguage").val($(this).data("language"));
        $("#editBookStock").val($(this).data("stock"));
    });


 $(document).ready(function () {
    $("#exportForm").on("submit", function (event) {
        var confirmExport = confirm("Are you sure you want to export the Excel file?");
        if (!confirmExport) {
            event.preventDefault(); 
        } else {
            alert("Exporting Excel file... Please wait.");
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('Search');
    const tableBody = document.querySelector('table tbody');
    
  
    const noResultsDiv = document.createElement('div');
    noResultsDiv.className = 'alert alert-info mt-3 mb-3 text-center';
    noResultsDiv.style.display = 'none';
    noResultsDiv.innerHTML = '<i class="bi bi-info-circle"></i> No books found matching your search criteria';
    
    // Insert the message before the table
    const table = document.querySelector('.table');
    table.parentNode.insertBefore(noResultsDiv, table);

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const rows = Array.from(tableBody.getElementsByTagName('tr'));
        let hasVisibleRows = false;

        rows.forEach(row => {
            const cells = row.getElementsByTagName('td');
            let rowText = '';
            
            // Combine text from title, author, publisher, and subject cells
            if(cells.length > 0) {
                rowText = [
                    cells[1].textContent, // Title
                    cells[2].textContent, // Author
                    cells[3].textContent, // Publisher
                    cells[6].textContent  // Subject
                ].join(' ').toLowerCase();
            }

            // Show row if it matches search term (using includes for partial matches)
            if (searchTerm === '' || rowText.includes(searchTerm)) {
                row.style.display = '';
                hasVisibleRows = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide no results message
        noResultsDiv.style.display = hasVisibleRows ? 'none' : 'block';
    });
});

function clearSearch() {
    const searchInput = document.getElementById('Search');
    const noResultsDiv = document.querySelector('.alert.alert-info');
    
    searchInput.value = '';
    // Trigger the input event to reset the table
    searchInput.dispatchEvent(new Event('input'));
  
    if (noResultsDiv) {
        noResultsDiv.style.display = 'none';
    }
}

});
