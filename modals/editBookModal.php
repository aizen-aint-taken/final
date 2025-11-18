<?php


include("../config/conn.php");
?>
<div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="../BooksCrud/Edit.php" method="POST">
                    <input type="hidden" name="editBook" value="1">
                    <input type="hidden" name="bookID" id="editBookId">
                    <div class="form-group">
                        <label for="editBookTitle">Title</label>
                        <input type="text" class="form-control" name="title" id="editBookTitle" required>
                    </div>
                    <div class="form-group">
                        <label for="editBookAuthor">Author</label>
                        <input type="text" class="form-control" name="author" id="editBookAuthor" required>
                    </div>
                    <div class="form-group">
                        <label for="editBookPublisher">Publisher</label>
                        <input type="text" class="form-control" name="publisher" id="editBookPublisher" required>
                    </div>
                    <div class="form-group">
                        <label for="editBookSourceOfAcquisition">Source of Acquisition</label>

                        <input type="text" class="form-control" name="source" id="editBookSourceOfAcquisition" required>
                    </div>
                    <div class="form-group">
                        <label for="editBookPublishedDate">Published Date</label>
                        <input type="date" class="form-control" name="publishedDate" id="editBookPublishedDate" required>
                    </div>

                    <div class="form-group">
                        <label for="editBookLanguage">Language</label>
                        <input type="text" class="form-control" name="language" id="editBookLanguage" required>
                    </div>
                    <div class="form-group">
                        <label for="editBookStock">Stock</label>
                        <input type="number" class="form-control" min="0" name="stock" id="editBookStock" required>
                    </div>
                    <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>