<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['error'])) {
    $_SESSION['error'] = [];
}



include("../config/conn.php");
include('../modals/addBookModal.php');
include('../modals/editBookModal.php');
include('../modals/deleteBookModal.php');
if (
    !isset($_SESSION['user'])
    || empty($_SESSION['user'])
) {
    header('location: ../index.php');
    exit;
}
$booksPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

$offset = ($page - 1) * $booksPerPage;

$books = $conn->query("SELECT * FROM books WHERE Stock > 0 LIMIT $offset, $booksPerPage");


$totalBooksQuery = $conn->query("SELECT COUNT(*) as total FROM books WHERE Stock > 0");
$totalBooks = $totalBooksQuery->fetch_assoc()['total'];
$totalPages = ceil($totalBooks / $booksPerPage);

// $books = $conn->query("SELECT * FROM books WHERE Stock > 0");
// var_dump($books);
$filterBooks = $conn->query("SELECT * FROM books GROUP BY Subject");
if (isset($_POST['filter'])) {
    $booksFilter = $_POST['booksFilter'];
    $books = $conn->query("SELECT * FROM books WHERE Subject = '$booksFilter' AND Stock > 0");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Ely Gian Ga">
    <meta name="description" content="CORE OF ALL SYSTEMS">
    <title>Book Inventory</title>
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/assets/css/inputFile.css">
    <link rel="stylesheet" href="../public/assets/css/modalFix.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

</head>
<style>
    body {
        background: url('../maharlika/2nd pic.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: Arial, sans-serif;
    }

    .btn {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }


    .container {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px) saturate(150%) brightness(120%);
        -webkit-backdrop-filter: blur(10px) saturate(150%) brightness(120%);
        border-radius: 15px;
        /* border: 1px solid rgba(255, 255, 255, 0.4); */
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        padding: 20px;
    }

    /* Alerts with enhanced glassy feel */
    .alert {
        background: rgba(255, 255, 255, 0.3);
        /* Slightly transparent */
        backdrop-filter: blur(12px);
        /* Softer, noticeable blur */
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        /* Thin, frosty border */
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        /* Makes alerts pop */
    }

    .alert .handler-message,
    .alert .handler-message-success {
        color: #000;

    }


    .card-container .card {
        /* background: rgba(255, 255, 255, 0.25); */

        backdrop-filter: blur(12px) saturate(160%);
        border: 1px solid rgba(255, 255, 255, 0.4);

        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);

        padding: 15px;
        margin: 10px 0;
    }

    .card-container .card-title,
    .card-container .card-text {
        color: #333;

    }


    table {
        background: rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        border-collapse: separate;
        overflow: hidden;
        margin: 20px 0;
        width: 100%;
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    thead {
        background: rgba(0, 0, 0, 0.7);

        color: #fff;
    }

    tbody tr {
        background: rgba(255, 255, 255, 0.4);

        transition: background 0.3s ease;
    }

    tbody tr:hover {
        background: rgba(200, 200, 200, 0.4);

    }

    .input-group {
        background: rgba(255, 255, 255, 0.25);
        /* Transparent white */
        backdrop-filter: blur(10px);
        /* Pronounced blur */
        border-radius: 10px;
        padding: 5px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination a {
        padding: 8px 12px;
        margin: 0 5px;
        text-decoration: none;
        background-color: #f1f1f1;
        border-radius: 4px;
        color: #333;
    }

    .pagination a.active {
        background-color: #007bff;
        color: white;
    }

    .pagination a:hover {
        background-color: #ddd;
    }
</style>

<body>
    <div class="message-holder">
        <?php if (isset($_SESSION['exists']) && !empty($_SESSION['exists'])) : ?>
            <div class="alert alert-danger">
                <span class="close-btn" onclick="this.parentElement.style.display='none';" style="cursor:pointer;">&times;</span>
                <div class="handler-message">
                    <div style='color: red;'>
                        <h2>Record Already Exists</h2>
                        <h2>If existed you can just edit </h2>
                        <?php foreach ($_SESSION['exists'] as $message) : ?>
                            <p><?= $message ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['exists']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])) : ?>
            <div class="alert alert-success">
                <span class="close-btn" onclick="this.parentElement.style.display='none';" style="cursor:pointer;">&times;</span>
                <div class=" handler-message-success">
                    <div style='color: green;'>
                        <h2>Successfully Imported</h2>
                        <?php foreach ($_SESSION['success'] as $message) : ?>
                            <p><?= $message ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <!-- error handling -->
        <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])) : ?>
            <div class="alert alert-danger">
                <span class="close-btn" onclick="this.parentElement.style.display='none';" style="cursor:pointer;">&times;</span>
                <div class="">
                    <div style='color: red;'>
                        <?php foreach ($_SESSION['error'] as $error) : ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>
    <!-- Filter og Import Section -->
    <div class="row align-items-center">
        <div class="col-md-6">
            <form action="index.php" method="post" id="filterForm">
                <label for="booksFilter" class="form-label">Filter By Subject:</label>
                <div class="input-group">
                    <select name="booksFilter" id="booksFilter" class="form-select" required>
                        <option value="" selected disabled>Select Subject</option>
                        <?php foreach ($filterBooks as $subject): ?>
                            <option value="<?= htmlspecialchars($subject['Subject']) ?>">
                                <?= htmlspecialchars($subject['Subject']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="filter" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
        <div class="col-md-6">
            <form action="import-excel.php" method="post" enctype="multipart/form-data">
                <label for="bookUpload" class="form-label">Upload Books Using Excel</label>
                <div class="input-group">
                    <input type="file" name="books" id="bookUpload" accept=".xls, .xlsx" class="form-control" required>
                    <button type="submit" name="import" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div><br>
    <!-- Add Book Button -->
    <div class="d-flex justify-content-end mb-4">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookModal">Add Book</button>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-center my-3 w-100">
        <div class="input-group w-sm-50">
            <input type="text" id="search" data-name="books" id="Search" class="form-control rounded" placeholder="Search" aria-label="Search" aria-describedby="search-addon" />
            <button type="button" class="btn btn-outline-primary" onclick="clearSearch()">Search</button>
        </div>
    </div>

    <!-- Table POV dako na screeen -->
    <table class="table table-striped text-center">
        <thead class="table-dark">
            <tr colspan="2">
                <th>No.</th>
                <th>Title</th>
                <th>Author</th>
                <th>Publisher</th>
                <th>Source of Acquisition</th>
                <th>Published Date</th>
                <th>Subject</th>
                <th>Available</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><?= htmlspecialchars($book['BookID']) ?></td>
                    <td><?= htmlspecialchars($book['Title']) ?></td>
                    <td><?= htmlspecialchars($book['Author']) ?></td>
                    <td><?= htmlspecialchars($book['Publisher']) ?></td>
                    <td><?= htmlspecialchars($book['Source of Acquisition']) ?></td>
                    <td><?= htmlspecialchars($book['PublishedDate']) ?></td>
                    <td><?= htmlspecialchars($book['Subject']) ?></td>
                    <td><?= htmlspecialchars($book['Stock']) ?></td>
                    <td>
                        <div class="d-flex flex-column">
                            <button class="btn btn-success btn-sm mb-2 edit-btn" data-bs-toggle="modal" style="opacity: 0.8;" data-bs-target=" #editBookModal"
                                data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                data-title="<?= htmlspecialchars($book['Title']) ?>"
                                data-author="<?= htmlspecialchars($book['Author']) ?>"
                                data-publisher="<?= htmlspecialchars($book['Publisher']) ?>"
                                data-source="<?= htmlspecialchars($book['Source of Acquisition']) ?>"
                                data-published="<?= htmlspecialchars($book['PublishedDate']) ?>"
                                data-language="<?= htmlspecialchars($book['Subject']) ?>"
                                data-stock="<?= htmlspecialchars($book['Stock']) ?>">
                                <i class="bi bi-pencil-square  fs-5"></i> Edit
                            </button>

                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                data-bs-toggle="modal" data-bs-target="#deleteBookModal">
                                <i class="bi bi-trash fs-5"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1">&laquo; First</a>
            <a href="?page=<?= $page - 1 ?>">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Next</a>
            <a href="?page=<?= $totalPages ?>">Last &raquo;</a>
        <?php endif; ?>
    </div>
    <!-- Card View Cellphone gamay na screen -->
    <div class="card-container text-center">
        <?php foreach ($books as $book): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title text-center"><?= htmlspecialchars($book['Title']) ?></h5>
                    <p class="card-text">
                        <strong>Author:</strong> <?= htmlspecialchars($book['Author']) ?><br>
                        <strong>Publisher:</strong> <?= htmlspecialchars($book['Publisher']) ?><br>
                        <strong>Source of Acquisition</strong> <?= htmlspecialchars($book['Source of Acquisition']) ?><br>
                        <strong>Published Date:</strong> <?= htmlspecialchars($book['PublishedDate']) ?><br>
                        <strong>Subject:</strong> <?= htmlspecialchars($book['Subject']) ?><br>
                        <strong>Stock:</strong> <?= htmlspecialchars($book['Stock']) ?>
                    </p>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-success btn-sm mb-2 edit-btn" data-bs-toggle="modal" data-bs-target="#editBookModal"
                            data-id="<?= htmlspecialchars($book['BookID']) ?>"
                            data-title="<?= htmlspecialchars($book['Title']) ?>"
                            data-author="<?= htmlspecialchars($book['Author']) ?>"
                            data-publisher="<?= htmlspecialchars($book['Publisher']) ?>"
                            data-source="<?= htmlspecialchars($book['Source of Acquisition']) ?>"
                            data-published="<?= htmlspecialchars($book['PublishedDate']) ?>"
                            data-language="<?= htmlspecialchars($book['Subject']) ?>"
                            data-stock="<?= htmlspecialchars($book['Stock']) ?>"><i class="bi bi-pencil-square fs-5"></i> Edit</button>
                        <button class="btn btn-danger btn-sm mb-2 edit-btn" data-id="<?= htmlspecialchars($book['BookID']) ?>"
                            data-bs-toggle="modal" data-bs-target="#deleteBookModal"><i class="bi bi-trash fs-5"></i> Delete</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    </div>
    <script src="../public/assets/js/jquery-3.5.1.min.js"></script>
    <script src="../public/assets/js/popper.min.js"></script>
    <script src="../public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/Books.js"></script>
    <!-- <script src="../public/assets/js/inputFile.js"></script> -->
    <script src="../public/assets/js/excel.js"></script>
    <script>
        $(document).ready(() => {
            $('#filterForm').on('submit', function(event) {
                var select = $('#booksFilter');
                if (select.val() === 'Select Subject' || select.val() === '') {
                    event.preventDefault();
                    alert('Please select a subject.');
                }
            });

            $('#search').on('input', function() {
                var query = $(this).val().toLowerCase();

                $('table tbody tr').each(function() {
                    var rowText = $(this).text().toLowerCase(); //

                    if (rowText.indexOf(query) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });


            function clearSearch() {
                $('#search').val('');
                $('table tbody tr').show();

            }
        })
    </script>
</body>

</html>