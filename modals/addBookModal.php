<div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBookModalLabel">Add Book</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="../BooksCrud/Add.php" method="POST">
                    <div class="form-group">
                        <label for="addBookTitle">Title</label>
                        <input type="text" class="form-control" name="title" id="addBookTitle" required>
                    </div>
                    <div class="form-group">
                        <label for="addBookAuthor">Author</label>
                        <input type="text" class="form-control" name="author" id="addBookAuthor" required>
                    </div>
                    <div class="form-group">
                        <label for="addBookPublisher">Publisher</label>
                        <input type="text" class="form-control" name="publisher" id="addBookPublisher" required>
                    </div>
                    <div class="form-group">
                        <label for="addBookGenre">Source of Acquisition</label>
                        <input type="text" class="form-control" name="sourceOfAcquisition" id="addBookGenre" required>
                    </div>
                    <div class="form-group">
                        <label for="addBookPublishedDate">Published Date</label>
                        <input type="date" class="form-control" name="published_date" id="addBookPublishedDate" required>
                    </div>
                    <div class="form-group">
                        <label for="addBookLanguage">Subject</label>
                        <input type="text" class="form-control" name="language" id="addBookLanguage" required>
                    </div>
                    <div class="form-group">
                        <label for="addBookStock">Stock</label>
                        <input type="number" class="form-control" name="stock" id="addBookStock" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-success">Add Book</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // form.addEventListener('submit', async function(e) {
    //     e.preventDefault();

    //     const formData = new FormData(form);

    //     try {
    //         let response = await fetch(form.action, {
    //             method: 'POST',
    //             body: formData
    //         });

    //         let result = await response.text();
    //         console.log('Response:', result); // Debugging

    //         if (result.trim() === 'success') {
    //             await Swal.fire({
    //                 icon: 'success',
    //                 title: 'Book Added!',
    //                 text: 'The book has been added successfully.',
    //             });

    //             // Close modal after SweetAlert
    //             let modal = document.getElementById('addBookModal');
    //             let modalInstance = bootstrap.Modal.getInstance(modal);
    //             modalInstance.hide();

    //             form.reset();
    //         } else {
    //             Swal.fire({
    //                 icon: 'error',
    //                 title: 'Error!',
    //                 text: 'There was an issue adding the book. Please try again.',
    //             });
    //         }
    //     } catch (error) {
    //         console.error('Fetch Error:', error);
    //         Swal.fire({
    //             icon: 'error',
    //             title: 'Error!',
    //             text: 'Something went wrong. Please try again.',
    //         });
    //     }
    // });
</script>