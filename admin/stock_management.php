<?php
session_start();

// Check authentication
if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['a', 'sa'])) {
    header('Location: ../index.php');
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../config/conn.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include('inventory_operations.php');
    exit;
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper" style="padding-top: 40px;">
    <section class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-header-enhanced">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="page-title-section">
                                <h1 class="page-title">📊 Inventory Analytics & Monitoring</h1>
                                <p class="page-subtitle">Monitor book inventory, track usage patterns, and analyze library statistics</p>
                            </div>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-success" onclick="exportInventoryReport()">
                                    <i class="fas fa-file-excel"></i> Export Report
                                </button>
                                <a href="inventory.php" class="btn btn-primary">
                                    <i class="fas fa-chart-bar"></i> Overviews
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Analytics & Monitoring Tools</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="searchBooks">Search Books</label>
                                        <input type="text" class="form-control" id="searchBooks" placeholder="Search by title, author, or ID...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filterCategory">Filter by Subject</label>
                                        <select class="form-control" id="filterCategory">
                                            <option value="all">All Subjects</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filterStock">Stock Level</label>
                                        <select class="form-control" id="filterStock">
                                            <option value="all">All Stock Levels</option>
                                            <option value="out_of_stock">Out of Stock</option>
                                            <option value="low_stock">Low Stock (< 5)</option>
                                            <option value="good_stock">Good Stock (≥ 5)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-primary btn-block" onclick="filterBooks()">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Books Inventory Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-books"></i> Books Inventory Overview</h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-info btn-sm" onclick="refreshData()">
                                        <i class="fas fa-sync"></i> Refresh Data
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="booksInventoryTable">
                                    <thead>
                                        <tr>
                                            <th width="5%">
                                                <input type="checkbox" id="selectAll">
                                            </th>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Subject</th>
                                            <th>Current Stock</th>
                                            <th>Borrowed</th>
                                            <th>Available</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="booksTableBody">
                                        <!-- Data will be loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Book Transactions Modal (Read-Only) -->
<div class="modal fade" id="transactionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">📊 Transaction History</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>User</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <!-- Transactions will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Book Details Modal (Read-Only) -->
<div class="modal fade" id="bookDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">📖 Book Details & Analytics</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="bookDetailsContent">
                    <!-- Book details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<style>
    .page-header-enhanced {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }

    .stock-badge {
        font-size: 0.9rem;
        padding: 0.3rem 0.6rem;
    }

    .stock-good {
        background-color: #28a745;
    }

    .stock-low {
        background-color: #ffc107;
        color: #000;
    }

    .stock-out {
        background-color: #dc3545;
    }

    .action-btn {
        margin: 0 2px;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .table th {
        border-top: none;
        font-weight: 600;
        background-color: #f8f9fa;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
</style>

<script>
    $(document).ready(function() {
        loadBooksInventory();
        loadCategories();

        // Search functionality
        $('#searchBooks').on('keyup', function() {
            filterBooks();
        });

        // Filter change
        $('#filterCategory, #filterStock').on('change', function() {
            filterBooks();
        });

        // Select all functionality
        $('#selectAll').on('change', function() {
            $('.book-checkbox').prop('checked', $(this).is(':checked'));
        });
    });

    let booksData = [];

    function loadBooksInventory() {
        $.post('inventory_operations.php', {
            action: 'get_books_by_category',
            category: 'all'
        }, function(response) {
            if (response.success) {
                booksData = response.data;
                displayBooks(booksData);
            } else {
                showNotification('Failed to load books inventory', 'error');
            }
        }, 'json');
    }

    function loadCategories() {
        $.post('../admin/library_delivery_operations.php', {
            action: 'get_stats'
        }, function(response) {
            // This is a simple way to get categories; you might want a dedicated endpoint
            let categories = ['Literature', 'Fiction', 'Science', 'History', 'Mathematics', 'Graphic Novel'];
            let options = '<option value="all">All Subjects</option>';
            categories.forEach(function(cat) {
                options += `<option value="${cat}">${cat}</option>`;
            });
            $('#filterCategory').html(options);
        }, 'json');
    }

    function displayBooks(books) {
        let html = '';
        books.forEach(function(book) {
            // Calculate borrowed count
            let borrowed = 0; // You might want to get this from a separate query
            let available = parseInt(book.Stock) - borrowed;

            // Determine stock status
            let stockClass, stockText, stockBadge;
            if (book.Stock == 0) {
                stockClass = 'stock-out';
                stockText = 'Out of Stock';
                stockBadge = 'badge-danger';
            } else if (book.Stock < 5) {
                stockClass = 'stock-low';
                stockText = 'Low Stock';
                stockBadge = 'badge-warning';
            } else {
                stockClass = 'stock-good';
                stockText = 'Good Stock';
                stockBadge = 'badge-success';
            }

            html += `
            <tr>
                <td>
                    <input type="checkbox" class="book-checkbox" data-book-id="${book.BookID}">
                </td>
                <td><strong>${book.BookID}</strong></td>
                <td>${book.Title}</td>
                <td>${book.Author || 'N/A'}</td>
                <td>${book.Subject || 'N/A'}</td>
                <td>
                    <span class="badge ${stockBadge} stock-badge">${book.Stock}</span>
                </td>
                <td><span class="badge badge-info">${borrowed}</span></td>
                <td><span class="badge badge-primary">${available}</span></td>
                <td>
                    <span class="badge ${stockBadge}">${stockText}</span>
                </td>
                <td>
                    <button class="btn btn-info btn-sm action-btn" onclick="showTransactionsModal(${book.BookID})">
                        <i class="fas fa-history"></i> History
                    </button>
                    <button class="btn btn-primary btn-sm action-btn" onclick="showBookDetailsModal(${book.BookID})">
                        <i class="fas fa-eye"></i> Details
                    </button>
                    <a href="../admin/reservations.php?book_id=${book.BookID}" class="btn btn-success btn-sm action-btn">
                        <i class="fas fa-book-reader"></i> Reservations
                    </a>
                </td>
            </tr>
        `;
        });

        $('#booksTableBody').html(html);
    }

    function filterBooks() {
        let searchTerm = $('#searchBooks').val().toLowerCase();
        let categoryFilter = $('#filterCategory').val();
        let stockFilter = $('#filterStock').val();

        let filteredBooks = booksData.filter(function(book) {
            // Search filter
            let matchesSearch = searchTerm === '' ||
                book.Title.toLowerCase().includes(searchTerm) ||
                book.Author.toLowerCase().includes(searchTerm) ||
                book.BookID.toString().includes(searchTerm);

            // Category filter
            let matchesCategory = categoryFilter === 'all' || book.Subject === categoryFilter;

            // Stock filter
            let matchesStock = true;
            if (stockFilter === 'out_of_stock') {
                matchesStock = book.Stock == 0;
            } else if (stockFilter === 'low_stock') {
                matchesStock = book.Stock > 0 && book.Stock < 5;
            } else if (stockFilter === 'good_stock') {
                matchesStock = book.Stock >= 5;
            }

            return matchesSearch && matchesCategory && matchesStock;
        });

        displayBooks(filteredBooks);
    }

    function showBookDetailsModal(bookId) {
        // Find book in current data
        let book = booksData.find(b => b.BookID == bookId);
        if (!book) return;

        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-book"></i> Book Information</h6>
                    <table class="table table-sm">
                        <tr><th>Title:</th><td>${book.Title}</td></tr>
                        <tr><th>Author:</th><td>${book.Author || 'N/A'}</td></tr>
                        <tr><th>Publisher:</th><td>${book.Publisher || 'N/A'}</td></tr>
                        <tr><th>Subject:</th><td>${book.Subject || 'N/A'}</td></tr>
                        <tr><th>Current Stock:</th><td><span class="badge badge-primary">${book.Stock}</span></td></tr>
                        <tr><th>Source:</th><td>${book['Source of Acquisition'] || 'N/A'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-chart-bar"></i> Usage Statistics</h6>
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle"></i> Detailed usage statistics would be available here with additional database queries for reservation counts, borrowing patterns, etc.</small>
                    </div>
                    <canvas id="bookUsageChart" width="300" height="200"></canvas>
                </div>
            </div>
        `;

        $('#bookDetailsContent').html(html);
        $('#bookDetailsModal').modal('show');
    }

    function showTransactionsModal(bookId) {
        $.post('inventory_operations.php', {
            action: 'get_book_transactions',
            book_id: bookId
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(transaction) {
                    let typeClass = '';
                    switch (transaction.transaction_type) {
                        case 'IN':
                            typeClass = 'badge-success';
                            break;
                        case 'OUT':
                        case 'LOST':
                        case 'DAMAGED':
                            typeClass = 'badge-danger';
                            break;
                        default:
                            typeClass = 'badge-secondary';
                    }

                    html += `
                    <tr>
                        <td>${new Date(transaction.transaction_date).toLocaleDateString()}</td>
                        <td><span class="badge ${typeClass}">${transaction.transaction_type}</span></td>
                        <td>${transaction.quantity}</td>
                        <td>${transaction.user_name || transaction.created_by || 'System'}</td>
                        <td>${transaction.notes || ''}</td>
                    </tr>
                `;
                });

                $('#transactionsTableBody').html(html);
                $('#transactionsModal').modal('show');
            }
        }, 'json');
    }

    function refreshData() {
        showNotification('Refreshing inventory data...', 'info');
        loadBooksInventory();
        setTimeout(() => {
            showNotification('Data refreshed successfully!', 'success');
        }, 1000);
    }

    function exportInventoryReport() {
        window.open('inventory_operations.php?action=export_inventory_excel', '_blank');
    }

    function showNotification(message, type) {
        let alertClass = type === 'success' ? 'alert-success' :
            type === 'error' ? 'alert-danger' :
            type === 'warning' ? 'alert-warning' : 'alert-info';

        let alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ${message}
        </div>
    `;

        $('.container-fluid').prepend(alertHtml);

        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
</script>