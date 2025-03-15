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

});
