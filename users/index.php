<?php

include("../config/conn.php");
session_start();


if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('location: ../index.php');
    exit;
}

if ($_SESSION['usertype'] !== 'u') {
    header("Location: ../index.php");
    exit;
}

$resultsPerPage = 5;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $resultsPerPage;

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $searchPattern = "%{$searchTerm}%";

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE Stock > 0 AND (Title LIKE ? OR Author LIKE ? OR Publisher LIKE ? OR `Source of Acquisition` LIKE ? OR Subject LIKE ?)");
    $stmt->bind_param("sssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $totalBooks = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT * FROM books WHERE Stock > 0 AND (Title LIKE ? OR Author LIKE ? OR Publisher LIKE ? OR `Source of Acquisition` LIKE ? OR Subject LIKE ?) ORDER BY BookID LIMIT ? OFFSET ?");
    $stmt->bind_param("sssssii", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $resultsPerPage, $offset);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE Stock > 0");
    $stmt->execute();
    $totalBooks = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT * FROM books WHERE Stock > 0 ORDER BY BookID LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $resultsPerPage, $offset);
    $stmt->execute();
    $books = $stmt->get_result();
}

$totalPages = ceil($totalBooks / $resultsPerPage);

$stmt = $conn->prepare("SELECT DISTINCT Subject FROM books");
$stmt->execute();
$filterBooks = $stmt->get_result();

$studentId = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT U.name AS USERNAME, R.ReserveDate AS RESERVEDATE, B.Title AS BOOK_TITLE 
                       FROM reservations AS R
                       INNER JOIN users AS U ON R.StudentID = U.id
                       INNER JOIN books AS B ON R.BookID = B.BookID 
                       WHERE U.id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$reservations = $stmt->get_result();

if (isset($_POST['filter'])) {
    $booksFilter = $_POST['booksFilter'];
    $stmt = $conn->prepare("SELECT * FROM books WHERE Subject = ? LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $booksFilter, $resultsPerPage, $offset);
    $stmt->execute();
    $books = $stmt->get_result();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Ely Gian Ga">
    <meta name="description" content="Student Book Reservation System">
    <title>Book Reservation</title>
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/assets/css/jquery.dataTables.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../public/assets/css/datatables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../public/assets/css/users_main.css">


</head>

<body>
    <?php include("sidebar.php") ?>
    <div class="main-content w-80">
        <?php include('header.php') ?>
        <div class="container-fluid mt-5 w-100" style="margin-right: 250px;">
            <div class="row">
                <div class="col-12">
                    <h1 class="text-center mb-4 primary">List of Library Collection</h1>
                    <!-- Filter and Search Container -->
                    <div class="search-filter-container mb-5">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <!-- Filter Form -->
                                <div class="card filter-card mb-4">
                                    <div class="card-body">
                                        <h5 class="filter-title mb-4">
                                            <i class="fas fa-book-open me-2"></i>Find Your Books
                                        </h5>

                                        <form action="index.php" method="post" class="mb-4">
                                            <label for="booksFilter" class="form-label">Filter by Subject</label>
                                            <div class="input-group input-group-lg filter-group">
                                                <span class="input-group-text bg-transparent border-end-0">
                                                    <i class="fas fa-filter text-primary"></i>
                                                </span>
                                                <select name="booksFilter" id="booksFilter" class="form-select border-start-0 ps-0">
                                                    <option selected disabled hidden>Choose a subject...</option>
                                                    <?php foreach ($filterBooks as $book): ?>
                                                        <option value="<?= htmlspecialchars($book['Subject']) ?>">
                                                            <?= htmlspecialchars($book['Subject']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="filter" class="btn btn-primary px-4">
                                                    <i class="fas fa-check me-2"></i>Apply
                                                </button>
                                            </div>
                                        </form>

                                        <!-- Search Bar -->
                                        <div class="search-section">
                                            <label for="searchBar" class="form-label">Search Books</label>
                                            <form action="index.php" method="GET" class="input-group input-group-lg">
                                                <span class="input-group-text bg-transparent border-end-0">
                                                    <i class="fas fa-search text-primary"></i>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="searchBar"
                                                    name="search"
                                                    class="form-control border-start-0 ps-0"
                                                    placeholder="Search by title, author, or subject..."
                                                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                                <button type="submit" class="btn btn-primary px-4">
                                                    <i class="fas fa-search me-2"></i>Search
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Add this after your search bar -->
                                        <div id="noResultsMessage" class="alert alert-warning mt-3" style="display: none;">
                                            No books found matching your search.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-striped text-center" id="booksTable">
                            <thead class="table-dark">
                                <tr>
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
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalId"
                                                data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                                data-title="<?= htmlspecialchars($book['Title']) ?>"
                                                data-author="<?= htmlspecialchars($book['Author']) ?>">
                                                Borrow
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="row row-cols-1 row-cols-md-2 g-4 d-lg-none text-center">
                        <?php foreach ($books as $book): ?>
                            <div class="col">
                                <div class="card h-100 book-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary"><?= htmlspecialchars($book['Title']) ?></h5>
                                        <div class="card-text">
                                            <p><strong>Author:</strong> <?= htmlspecialchars($book['Author']) ?></p>
                                            <p><strong>Publisher:</strong> <?= htmlspecialchars($book['Publisher']) ?></p>
                                            <p><strong>Subject:</strong> <?= htmlspecialchars($book['Subject']) ?></p>
                                            <p><strong>Available:</strong> <?= htmlspecialchars($book['Stock']) ?></p>
                                        </div>
                                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalId"
                                            data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                            data-title="<?= htmlspecialchars($book['Title']) ?>"
                                            data-author="<?= htmlspecialchars($book['Author']) ?>">
                                            Borrow
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($totalPages > 1): ?>
                                <!-- Previous page link -->
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="index.php?page=<?= ($currentPage - 1) ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                // Show limited page numbers with ellipsis
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);

                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="index.php?page=1' . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="index.php?page=<?= $i ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php
                                endfor;

                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="index.php?page=' . $totalPages . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '">' . $totalPages . '</a></li>';
                                }
                                ?>

                                <!-- Next page link -->
                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="index.php?page=<?= ($currentPage + 1) ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modal for Reservation -->
        <div class="modal fade" id="modalId" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reserve Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="reserve.php" method="POST">
                            <input type="hidden" name="book_id" id="reserveBookId">
                            <div class="form-group">
                                <label for="reserveBookTitle">Book Title</label>
                                <input type="text" name="book_title" class="form-control" id="reserveBookTitle" readonly>
                            </div>
                            <div class="form-group">
                                <label for="reserveBookAuthor">Author</label>
                                <input type="text" name="book_author" class="form-control" id="reserveBookAuthor" readonly>
                            </div>

                            <button type="submit" name="reserve" class="btn btn-success">Borrow</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout Modal -->
        <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to log out?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="../logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>


        <script src="../public/assets/js/jquery-3.5.1.min.js"></script>
        <script src="../public/assets/js/bootstrap.bundle.min.js"></script>
        <script src="../public/assets/js/index.js"></script>
    </div>
</body>

</html>