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
include('../includes/header.php');
include('../includes/sidebar.php');
?>

<link rel="stylesheet" href="../public/assets/css/inventory.css">

<div class="content-wrapper" style="padding-top: 40px;">
    <section class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-header-enhanced">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="page-title-section text-center">
                                <h1 class="page-title">📅 Yearly Inventory Report</h1>
                                <p class="page-subtitle">Comprehensive overview of books inventory with borrowing and return statistics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year Selection and Quick Actions Panel -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card quick-actions-panel">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-bolt"></i> ⚡ Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="yearSelector">Select Year:</label>
                                        <select class="form-control" id="yearSelector">
                                            <?php
                                            // Generate options from current year down to 2025
                                            $currentYear = date('Y');
                                            $startYear = 2025;

                                            // If current year is before 2025, start from 2025
                                            $actualStartYear = ($currentYear >= $startYear) ? $currentYear : $startYear;

                                            for ($i = $actualStartYear; $i >= $startYear; $i--) {
                                                echo "<option value='$i'>$i</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="searchInput">Search Books:</label>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Search by title or author...">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-block quick-action-btn" onclick="refreshYearlyInventory()">
                                        <i class="fas fa-sync-alt"></i><br>
                                        🔄 Refresh Data
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-success btn-block quick-action-btn" onclick="exportYearlyInventory()">
                                        <i class="fas fa-file-excel"></i><br>
                                        📊 Export to Excel
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-info btn-block quick-action-btn" onclick="printYearlyInventory()">
                                        <i class="fas fa-print"></i><br>
                                        🖨️ Print Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yearly Inventory Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header text-center">
                            <h5><i class="fas fa-book"></i> Yearly Inventory Details for <span id="selectedYearDisplay"><?php echo date('Y'); ?></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="yearlyInventoryTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Preview Total</th>
                                            <th>Borrowed</th>
                                            <th>Returned</th>
                                            <th>Current Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="yearlyInventoryTableBody">
                                        <!-- Data will be loaded via JavaScript -->
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

<?php include('../includes/footer.php'); ?>

<!-- JavaScript -->
<script>
    // Store the full dataset for search functionality
    let fullInventoryData = [];

    $(document).ready(function() {
        // Load yearly inventory data when page loads
        loadYearlyInventory();

        // Reload data when year is changed
        $('#yearSelector').on('change', function() {
            loadYearlyInventory();
        });

        // Search functionality
        $('#searchInput').on('keyup', function() {
            filterInventoryData();
        });
    });

    // Load yearly inventory data
    function loadYearlyInventory() {
        var selectedYear = $('#yearSelector').val();
        $('#selectedYearDisplay').text(selectedYear);

        $.post('inventory_operations.php', {
            action: 'get_yearly_inventory',
            year: selectedYear
        }, function(response) {
            if (response.success) {
                fullInventoryData = response.data;
                displayYearlyInventory(fullInventoryData);
            } else {
                showNotification('Failed to load yearly inventory data', 'error');
            }
        }, 'json').fail(function() {
            showNotification('Error loading yearly inventory data', 'error');
        });
    }

    // Filter inventory data based on search input
    function filterInventoryData() {
        const searchTerm = $('#searchInput').val().toLowerCase();

        if (searchTerm === '') {
            displayYearlyInventory(fullInventoryData);
            return;
        }

        const filteredData = fullInventoryData.filter(book => {
            return (
                (book.Title && book.Title.toLowerCase().includes(searchTerm)) ||
                (book.Author && book.Author.toLowerCase().includes(searchTerm))
            );
        });

        displayYearlyInventory(filteredData);
    }

    // Display yearly inventory data in table
    function displayYearlyInventory(data) {
        let html = '';

        if (data && data.length > 0) {
            data.forEach(function(book, index) {
                html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${book.Title || 'N/A'}</td>
                    <td>${book.Author || 'N/A'}</td>
                    <td class="text-center">${book.preview_total || 0}</td>
                    <td class="text-center">${book.borrowed || 0}</td>
                    <td class="text-center">${book.returned || 0}</td>
                    <td class="text-center">${book.current_total || 0}</td>
                </tr>
            `;
            });
        } else {
            html = `
            <tr>
                <td colspan="7" class="text-center">No inventory data available for the selected year</td>
            </tr>
        `;
        }

        $('#yearlyInventoryTableBody').html(html);
    }

    // Refresh yearly inventory data
    function refreshYearlyInventory() {
        showNotification('Refreshing inventory data...', 'info');
        $('#searchInput').val(''); // Clear search input
        loadYearlyInventory();
        setTimeout(() => {
            showNotification('Data refreshed successfully!', 'success');
        }, 1000);
    }

    // Export yearly inventory to Excel
    function exportYearlyInventory() {
        var selectedYear = $('#yearSelector').val();
        window.open('inventory_operations.php?action=export_yearly_inventory_excel&year=' + selectedYear, '_blank');
    }

    // Print yearly inventory
    function printYearlyInventory() {
        window.print();
    }

    // Show notification message
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