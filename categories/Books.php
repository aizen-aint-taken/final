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

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('location: ../index.php');
    exit;
}


$booksPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $booksPerPage;


// ... existing code ...
$stmt = $conn->prepare("SELECT b.*, 
    COALESCE((SELECT COUNT(*) FROM reservations r WHERE r.BookID = b.BookID AND r.STATUS = 'Borrowed'), 0) as currently_borrowed 
FROM books b ORDER BY b.Stock DESC, b.Title ASC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $booksPerPage, $offset);
$stmt->execute();
$books = $stmt->get_result();


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM books");
$stmt->execute();
$totalBooks = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalBooks / $booksPerPage);


$stmt = $conn->prepare("SELECT DISTINCT Subject FROM books");
$stmt->execute();
$filterBooks = $stmt->get_result();


if (isset($_POST['filter']) && !empty($_POST['booksFilter'])) {
    $booksFilter = trim($_POST['booksFilter']);
    $stmt = $conn->prepare("SELECT b.*, 
        COALESCE((SELECT COUNT(*) FROM reservations r WHERE r.BookID = b.BookID AND r.STATUS = 'Borrowed'), 0) as currently_borrowed 
    FROM books b WHERE b.Subject = ? ORDER BY b.Stock DESC, b.Title ASC");
    $stmt->bind_param("s", $booksFilter);
    $stmt->execute();
    $books = $stmt->get_result();
}
// ... existing code ...
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
    <link rel="stylesheet" href="../public/assets/css/books.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>
    <div class="container-fluid py-4">
        <!-- Message Alerts Section -->
        <div class="message-holder mb-4">
            <?php if (isset($_SESSION['exists']) && !empty($_SESSION['exists'])) : ?>
                <div class="alert alert-danger">
                    <span class="close-btn" onclick="this.parentElement.style.display='none';" style="cursor:pointer;">&times;</span>
                    <div class="handler-message">
                        <div style='color: red;'>
                            <h2>Record Already Exists</h2>
                            <h2>If existed you can just edit </h2>
                            <?php
                            // Ensure $_SESSION['exists'] is always treated as an array
                            $exists = is_array($_SESSION['exists']) ? $_SESSION['exists'] : [$_SESSION['exists']];
                            foreach ($exists as $message) : ?>
                                <p><?= $message ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['exists']); ?>
            <?php endif; ?> <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])) : ?>
                <div class="alert alert-success">
                    <span class="close-btn" onclick="this.parentElement.style.display='none';" style="cursor:pointer;">&times;</span>
                    <div class=" handler-message-success">
                        <div style='color: green;'>
                            <h2>Successfully Imported</h2>
                            <?php
                                // Ensure $_SESSION['success'] is always treated as an array
                                $successes = is_array($_SESSION['success']) ? $_SESSION['success'] : [$_SESSION['success']];
                                foreach ($successes as $message) : ?>
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
                            <?php
                            // Ensure $_SESSION['error'] is always treated as an array
                            $errors = is_array($_SESSION['error']) ? $_SESSION['error'] : [$_SESSION['error']];
                            foreach ($errors as $error) : ?>
                                <p><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>

        <!-- Control Panel Section -->
        <div class="row mb-4">
            <!-- Left Column -->
            <div class="col-md-6">
                <!-- Search Bar -->
                <div class="mb-4">
                    <h5 class="form-label">Search Books</h5>
                    <div class="input-group">
                        <input type="text"
                            id="Search"
                            class="form-control"
                            placeholder="Search books by title, author, publisher, or subject..."
                            aria-label="Search" />
                        <button type="button" class="btn btn-outline-primary" onclick="clearSearch()">
                            <i class="bi bi-x-lg"></i> Clear
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="mb-4">
                    <form action="index.php" method="post" id="filterForm">
                        <label for="booksFilter" class="form-label">Filter By Subject</label>
                        <div class="input-group">
                            <select name="booksFilter" id="booksFilter" class="form-select" required>
                                <option value="" selected disabled>Select Subject</option>
                                <?php foreach ($filterBooks as $subject): ?>
                                    <option value="<?= htmlspecialchars($subject['Subject']) ?>">
                                        <?= htmlspecialchars($subject['Subject']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="filter" class="btn btn-primary">
                                <i class="fa-solid fa-magnifying-glass"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <!-- Import Section -->
                <div class="mb-4">
                    <form action="import-excel.php" method="post" enctype="multipart/form-data">
                        <label for="bookUpload" class="form-label">Import Books</label>
                        <div class="input-group">
                            <input type="file" name="books" id="bookUpload" accept=".xls, .xlsx" class="form-control" required>
                            <button type="submit" name="import" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel"></i> Import
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Export Section -->
                <div class="mb-4">
                    <form action="export-excel.php" method="post" id="exportForm">
                        <label for="exportBtn" class="form-label">Export Books</label>
                        <div class="input-group">
                            <button type="submit" id="exportBtn" name="import" class="btn btn-primary">
                                <i class="bi bi-download"></i> Export to Excel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Add Book Button -->
                <div class="text-end">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="bi bi-plus-lg"></i> Add New Book
                    </button>
                </div>
            </div>
        </div>

        <!-- Books Table -->
        <div class="table-responsive">
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
                        <th>No. of Copies</th>
                        <th>Currently Borrowed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($books && $books->num_rows > 0):
                        $rowNumber = ($page - 1) * $booksPerPage + 1;
                        foreach ($books as $book):
                            $currentRowNumber = $rowNumber++;
                    ?>
                            <tr>
                                <td><?= $currentRowNumber ?></td>
                                <td><?= htmlspecialchars($book['Title']) ?></td>
                                <td><?= htmlspecialchars($book['Author']) ?></td>
                                <td><?= htmlspecialchars($book['Publisher']) ?></td>
                                <td><?= htmlspecialchars($book['Source of Acquisition']) ?></td>
                                <td><?= htmlspecialchars($book['PublishedDate']) ?></td>
                                <td><?= htmlspecialchars($book['Subject']) ?></td>
                                <td>
                                    <?php if (isset($book['stock_update']) && $book['stock_update'] !== null): ?>
                                        <?php if ($book['stock_update'] > 0): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($book['stock_update']) ?>/<?= htmlspecialchars($book['Stock']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">0/<?= htmlspecialchars($book['Stock']) ?></span>
                                            <br><small class="text-muted fst-italic">Book not currently available</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($book['Stock'] > 0): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($book['Stock']) ?>/<?= htmlspecialchars($book['Stock']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">0/<?= htmlspecialchars($book['Stock']) ?></span>
                                            <br><small class="text-muted fst-italic">Book not currently available</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $book['currently_borrowed'] > 0 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars($book['currently_borrowed']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <button class="btn btn-success btn-sm mb-2 edit-btn"
                                            data-bs-toggle="modal"
                                            style="opacity: 0.8;"
                                            data-bs-target="#editBookModal"
                                            data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                            data-title="<?= htmlspecialchars($book['Title']) ?>"
                                            data-author="<?= htmlspecialchars($book['Author']) ?>"
                                            data-publisher="<?= htmlspecialchars($book['Publisher']) ?>"
                                            data-source="<?= htmlspecialchars($book['Source of Acquisition']) ?>"
                                            data-published="<?= htmlspecialchars($book['PublishedDate']) ?>"
                                            data-language="<?= htmlspecialchars($book['Subject']) ?>"
                                            data-stock="<?= htmlspecialchars(isset($book['stock_update']) ? $book['stock_update'] : $book['Stock']) ?>">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </button>

                                        <button class="btn btn-danger btn-sm delete-btn"
                                            data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteBookModal"
                                            title="Delete Book #<?= $currentRowNumber ?>: <?= htmlspecialchars($book['Title']) ?>">
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>

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
            <?php
            // Reset the result pointer to use the data again for cards
            if ($books && $books->num_rows > 0) {
                $books->data_seek(0); // Reset to beginning
                $cardRowNumber = ($page - 1) * $booksPerPage + 1; // Reset counter for cards
                foreach ($books as $book):
            ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="badge badge-primary mb-2">Book #<?= $cardRowNumber++ ?></div>
                            <h5 class="card-title text-center"><?= htmlspecialchars($book['Title']) ?></h5>
                            <p class="card-text">
                                <strong>Author:</strong> <?= htmlspecialchars($book['Author']) ?><br>
                                <strong>Publisher:</strong> <?= htmlspecialchars($book['Publisher']) ?><br>
                                <strong>Source of Acquisition</strong> <?= htmlspecialchars($book['Source of Acquisition']) ?><br>
                                <strong>Published Date:</strong> <?= htmlspecialchars($book['PublishedDate']) ?><br>
                                <strong>Subject:</strong> <?= htmlspecialchars($book['Subject']) ?><br>
                                <strong>Stock:</strong>
                                <?php if (isset($book['stock_update']) && $book['stock_update'] !== null): ?>
                                    <?php if ($book['stock_update'] > 0): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($book['stock_update']) ?>/<?= htmlspecialchars($book['Stock']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">0/<?= htmlspecialchars($book['Stock']) ?></span>
                                        <br><small class="text-muted fst-italic">Book not currently available</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($book['Stock'] > 0): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($book['Stock']) ?>/<?= htmlspecialchars($book['Stock']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">0/<?= htmlspecialchars($book['Stock']) ?></span>
                                        <br><small class="text-muted fst-italic">Book not currently available</small>
                                    <?php endif; ?>
                                <?php endif; ?><br>
                                <strong>Currently Borrowed:</strong>
                                <span class="badge <?= $book['currently_borrowed'] > 0 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars($book['currently_borrowed']) ?>
                                </span>
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
                                    data-stock="<?= htmlspecialchars(isset($book['stock_update']) ? $book['stock_update'] : $book['Stock']) ?>">
                                    <i class="bi bi-pencil-square fs-5"></i>
                                </button>
                                <button class="btn btn-danger btn-sm mb-2 delete-btn" data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                    data-bs-toggle="modal" data-bs-target="#deleteBookModal"
                                    title="Delete Book #<?= $cardRowNumber - 1 ?>: <?= htmlspecialchars($book['Title']) ?>">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
            <?php
                endforeach;
            } // Close the if statement
            ?>
        </div>
    </div>

    <script src="../public/assets/js/jquery-3.5.1.min.js"></script>
    <script src="../public/assets/js/popper.min.js"></script>
    <script src="../public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/Books.js"></script>
    <!-- <script src="../public/assets/js/inputFile.js"></script> -->
    <script src="../public/assets/js/excel.js"></script>

    <style>
        /* Add these styles */
        .alert {
            transition: all 0.3s ease;
        }

        .alert.alert-info {
            background-color: #e8f4f8;
            border-color: #bee5eb;
            color: #0c5460;
        }

        .table {
            transition: all 0.3s ease;
        }

        .table tr {
            transition: opacity 0.3s ease;
        }
    </style>
</body>

</html>