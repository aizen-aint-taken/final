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
    <title>Maharlika Library - Book Reservation</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../public/assets/css/users_main.css">
    <link rel="stylesheet" href="../public/assets/css/users_header.css">
    <link rel="stylesheet" href="../public/assets/css/users_sidebar.css">


    <style>
        /* ULTIMATE backdrop fix - highest priority */
        .modal-backdrop,
        .modal-backdrop.show,
        .modal-backdrop.fade,
        .modal-backdrop.fade.show,
        div[class*="modal-backdrop"],
        [class*="modal-backdrop"],
        body>.modal-backdrop {
            background-color: rgba(0, 0, 0, 0.1) !important;
            background: rgba(0, 0, 0, 0.1) !important;
            opacity: 0.1 !important;
            z-index: 1050 !important;
            pointer-events: none !important;
        }

        /* Force CSS variables */
        :root,
        html,
        body {
            --bs-modal-backdrop-bg: rgba(0, 0, 0, 0.1) !important;
            --bs-modal-backdrop-opacity: 0.1 !important;
        }

        /* Ensure modals are clickable */
        .modal,
        .modal-dialog,
        .modal-content {
            pointer-events: auto !important;
            z-index: 1055 !important;
        }

        /* Fix specific to Bootstrap 5 */
        .modal.show {
            z-index: 1055 !important;
        }

        .modal.show~.modal-backdrop {
            background-color: rgba(0, 0, 0, 0.1) !important;
            opacity: 0.1 !important;
        }
    </style>
</head>

<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-logo">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="loading-text">Loading Library...</div>
            <div class="loading-spinner">
                <div class="spinner-ring"></div>
            </div>
        </div>
    </div>

    <?php include("sidebar.php") ?>

    <div class="main-content">
        <?php include('header.php') ?>

        <div class="container-fluid main-container">
            <!-- Welcome Banner -->

            <div class="row">
                <div class="col-12">
                    <!-- Search and Filter Section -->
                    <div class="search-filter-container mb-5">
                        <div class="row justify-content-center">
                            <div class="col-md-10">
                                <div class="card filter-card modern-card mb-4">
                                    <div class="card-body">
                                        <!-- Filter Form -->
                                        <div class="filter-row mb-4">
                                            <form action="index.php" method="post" class="mb-0">
                                                <label for="booksFilter" class="form-label">Select by Subject</label>
                                                <div class="input-group input-group-lg modern-input-group">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-filter text-primary"></i>
                                                    </span>
                                                    <select name="booksFilter" id="booksFilter" class="form-control">
                                                        <option selected disabled hidden>Choose a subject...</option>
                                                        <?php foreach ($filterBooks as $book): ?>
                                                            <option value="<?= htmlspecialchars($book['Subject']) ?>">
                                                                <?= htmlspecialchars($book['Subject']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" name="filter" class="btn btn-primary px-4">
                                                        <i class="fas fa-check me-2"></i>Select Subject
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Search Form -->
                                        <div class="search-section">
                                            <label for="searchBar" class="form-label">Search Books</label>
                                            <form action="index.php" method="GET" class="search-form">
                                                <div class="input-group input-group-lg modern-search-group">
                                                    <span class="input-group-text search-icon">
                                                        <i class="fas fa-search text-primary"></i>
                                                    </span>
                                                    <input type="text" id="searchBar" name="search" class="form-control search-input"
                                                        placeholder="Search by title, author, publisher, or subject..."
                                                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                                    <button type="submit" class="btn btn-primary search-btn px-4">
                                                        <i class="fas fa-search me-2"></i>Search
                                                    </button>
                                                </div>
                                            </form>

                                            <div class="search-suggestions mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-lightbulb me-1"></i>
                                                    Try searching for: "Science", "Mathematics", "English", or any author name
                                                </small>
                                            </div>
                                        </div>

                                        <!-- No Results Message -->
                                        <div id="noResultsMessage" class="alert alert-info mt-3" style="display: none;">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <div>
                                                    <strong>No books found</strong><br>
                                                    <small>Try adjusting your search terms or browse all available books</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="table-responsive d-none d-lg-block modern-table-container">
                        <table class="table table-striped text-center modern-table" id="booksTable">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>No.</th>
                                    <th><i class="fas fa-book me-1"></i>Title</th>
                                    <th><i class="fas fa-user me-1"></i>Author</th>
                                    <th><i class="fas fa-building me-1"></i>Publisher</th>
                                    <th><i class="fas fa-source me-1"></i>Source</th>
                                    <th><i class="fas fa-calendar me-1"></i>Published</th>
                                    <th><i class="fas fa-tag me-1"></i>Subject</th>
                                    <th><i class="fas fa-check-circle me-1"></i>Available</th>
                                    <th><i class="fas fa-tools me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rowNumber = ($currentPage - 1) * $resultsPerPage + 1;
                                foreach ($books as $book):
                                ?>
                                    <tr>
                                        <td><?= $rowNumber++ ?></td>
                                        <td><?= htmlspecialchars($book['Title']) ?></td>
                                        <td><?= htmlspecialchars($book['Author']) ?></td>
                                        <td><?= htmlspecialchars($book['Publisher']) ?></td>
                                        <td><?= htmlspecialchars($book['Source of Acquisition']) ?></td>
                                        <td><?= htmlspecialchars($book['PublishedDate']) ?></td>
                                        <td><?= htmlspecialchars($book['Subject']) ?></td>
                                        <td class="book-stock">
                                            <span class="availability-badge available">
                                                <i class="fas fa-check-circle me-1"></i>
                                                <?= htmlspecialchars($book['Stock']) ?> copies
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm reserve-btn"
                                                data-bs-toggle="modal" data-bs-target="#modalId"
                                                data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                                data-title="<?= htmlspecialchars($book['Title']) ?>"
                                                data-author="<?= htmlspecialchars($book['Author']) ?>">
                                                <i class="fas fa-bookmark me-1"></i>Borrow
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="row row-cols-1 row-cols-md-2 g-4 d-lg-none mobile-cards-container">
                        <?php
                        $cardRowNumber = ($currentPage - 1) * $resultsPerPage + 1;
                        foreach ($books as $book):
                        ?>
                            <div class="col">
                                <div class="card h-100 book-card modern-book-card">
                                    <div class="card-ribbon">
                                        <span class="ribbon-text text">Book #<?= $cardRowNumber++ ?></span>
                                    </div>
                                    <div class="card-body">
                                        <div class="book-header">

                                            <div class="availability-status">
                                                <span class="availability-badge available">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?= htmlspecialchars($book['Stock']) ?> available
                                                </span>
                                            </div>
                                        </div>

                                        <h5 class="card-title book-title"><?= htmlspecialchars($book['Title']) ?></h5>

                                        <div class="book-details">
                                            <div class="detail-item">
                                                <i class="fas fa-user text-primary"></i>
                                                <span><strong>Author:</strong> <?= htmlspecialchars($book['Author']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-building text-success"></i>
                                                <span><strong>Publisher:</strong> <?= htmlspecialchars($book['Publisher']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-tag text-info"></i>
                                                <span><strong>Subject:</strong> <?= htmlspecialchars($book['Subject']) ?></span>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary w-100 reserve-btn-mobile"
                                            data-bs-toggle="modal" data-bs-target="#modalId"
                                            data-id="<?= htmlspecialchars($book['BookID']) ?>"
                                            data-title="<?= htmlspecialchars($book['Title']) ?>"
                                            data-author="<?= htmlspecialchars($book['Author']) ?>">
                                            <i class="fas fa-bookmark me-2"></i>Borrow This Book
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
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="index.php?page=<?= ($currentPage - 1) ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php
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

        <!-- Enhanced Modal for Reservation -->
        <div class="modal fade modern-modal" id="modalId" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title-container">
                            <i class="fas fa-bookmark modal-icon"></i>
                            <h5 class="modal-title">Borrow Book</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="reservation-info">
                            <div class="info-icon">
                                <i class="fas fa-info-circle text-primary"></i>
                            </div>
                            <p class="mb-3">You are about to reserve the following book:</p>
                        </div>

                        <form action="reserve.php" method="POST" class="reservation-form">
                            <input type="hidden" name="book_id" id="reserveBookId">

                            <div class="form-group mb-3">
                                <label for="reserveBookTitle" class="form-label">
                                    <i class="fas fa-book me-2"></i>Book Title
                                </label>
                                <input type="text" name="book_title" class="form-control" id="reserveBookTitle" readonly>
                            </div>

                            <div class="form-group mb-4">
                                <label for="reserveBookAuthor" class="form-label">
                                    <i class="fas fa-user me-2"></i>Author
                                </label>
                                <input type="text" name="book_author" class="form-control" id="reserveBookAuthor" readonly>
                            </div>

                            <div class="reservation-note">
                                <div class="note-icon">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                </div>
                                <div>
                                    <strong>Note:</strong> Please review your reservation details before confirming.
                                </div>
                            </div>

                            <div class="modal-actions mt-4">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                                <button type="submit" name="reserve" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Confirm Borrow
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout Modal -->
        <div class="modal fade modern-modal" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title-container">
                            <i class="fas fa-sign-out-alt modal-icon"></i>
                            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="reservation-info">
                            <div class="info-icon">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                            </div>
                            <p class="mb-0">Are you sure you want to log out?</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <a href="../logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap 5 JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Custom JS -->
        <script src="../public/assets/js/index.js"></script>
        <script>
            // Loading screen
            window.addEventListener('load', function() {
                const loadingScreen = document.getElementById('loadingScreen');
                if (loadingScreen) {
                    loadingScreen.style.opacity = '0';
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }
            });

            // ULTIMATE Modal Backdrop Fix - Inline JavaScript
            (function() {
                function ultimateBackdropFix() {
                    // Target every possible backdrop element
                    const selectors = [
                        '.modal-backdrop',
                        '[class*="modal-backdrop"]',
                        'div[class*="backdrop"]',
                        '.fade.modal-backdrop',
                        '.show.modal-backdrop'
                    ];

                    selectors.forEach(selector => {
                        const elements = document.querySelectorAll(selector);
                        elements.forEach(el => {
                            el.style.setProperty('background-color', 'rgba(0, 0, 0, 0.1)', 'important');
                            el.style.setProperty('background', 'rgba(0, 0, 0, 0.1)', 'important');
                            el.style.setProperty('opacity', '0.1', 'important');
                            el.style.setProperty('z-index', '1050', 'important');
                            el.style.setProperty('pointer-events', 'none', 'important');
                        });
                    });

                    // Force CSS variables
                    document.documentElement.style.setProperty('--bs-modal-backdrop-bg', 'rgba(0, 0, 0, 0.1)', 'important');
                    document.documentElement.style.setProperty('--bs-modal-backdrop-opacity', '0.1', 'important');
                }

                // Run immediately
                ultimateBackdropFix();

                // Run when DOM is ready
                document.addEventListener('DOMContentLoaded', ultimateBackdropFix);

                // Run continuously
                setInterval(ultimateBackdropFix, 50);

                // Override Bootstrap's modal events
                document.addEventListener('DOMContentLoaded', function() {
                    // Listen for all modal events
                    ['show.bs.modal', 'shown.bs.modal', 'hide.bs.modal', 'hidden.bs.modal'].forEach(eventType => {
                        document.addEventListener(eventType, function(e) {
                            setTimeout(ultimateBackdropFix, 10);
                            setTimeout(ultimateBackdropFix, 100);
                            setTimeout(ultimateBackdropFix, 300);
                        });
                    });

                    // Monitor DOM changes
                    const observer = new MutationObserver(function(mutations) {
                        let needsFix = false;
                        mutations.forEach(function(mutation) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1 &&
                                    (node.classList && (node.classList.contains('modal-backdrop') ||
                                        node.className.includes('backdrop')))) {
                                    needsFix = true;
                                }
                            });
                        });
                        if (needsFix) {
                            setTimeout(ultimateBackdropFix, 10);
                        }
                    });

                    observer.observe(document.body, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['class', 'style']
                    });
                });
            })();
        </script>
    </div>
</body>

</html>