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

// Redirect AJAX requests to dedicated handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include('../admin/library_delivery_operations.php');
    exit;
}

// Redirect Excel operations to dedicated handler
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Location: ../admin/library_delivery_excel.php?action=export');
    exit;
}

if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    include('../admin/library_delivery_excel.php');
    exit;
}

// Handle template download
if (isset($_GET['template']) && $_GET['template'] === 'download') {
    header('Location: ../admin/library_delivery_excel.php?action=template');
    exit;
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper" style="padding-top: 40px;">
    <section class="content">
        <div class="container-fluid">
            <!-- Enhanced Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-header-enhanced">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="page-title-section text-center">
                                <h1 class="page-title">📚 Library Delivery Management</h1>
                                <p class="page-subtitle">Track and manage book deliveries to schools and institutions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="action-buttons-section">
                        <div class="btn-group-enhanced justify-content-center">
                            <button type="button" class="btn btn-primary btn-enhanced" data-toggle="modal" data-target="#addDeliveryModal">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add New Delivery</span>
                            </button>
                            <button type="button" class="btn btn-success btn-enhanced" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i>
                                <span>Export to Excel</span>
                            </button>
                            <button type="button" class="btn btn-info btn-enhanced" data-toggle="modal" data-target="#importModal">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Import Data</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-enhanced" onclick="downloadTemplate()">
                                <i class="fas fa-file-download"></i>
                                <span>Download Template</span>
                            </button>
                            <!-- <button type="button" class="btn btn-outline-primary btn-enhanced" onclick="loadDeliveries()">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh Data</span>
                            </button> -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="card card-enhanced">
                <div class="card-header card-header-enhanced">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title-enhanced">
                            <i class="fas fa-table text-primary text-center"></i>
                            Delivery Confirmations
                        </h3>
                        <div class="card-tools-enhanced">
                            <div class="search-section">
                                <!-- <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search deliveries...">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body card-body-enhanced">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show alert-enhanced">
                            <i class="fas fa-check-circle"></i>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?= $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <?php foreach ($_SESSION['error'] as $error): ?>
                            <div class="alert alert-danger alert-dismissible fade show alert-enhanced">
                                <i class="fas fa-exclamation-triangle"></i>
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?= $error; ?>
                            </div>
                        <?php endforeach;
                        unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p>Loading delivery records...</p>
                    </div>

                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-enhanced" id="deliveryTable">
                                <thead class="thead-enhanced">
                                    <tr>
                                        <th class="sortable" data-sort="title">
                                            <i class="fas fa-book"></i> Title and Grade Level
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="sortable text-center" data-sort="delivered">
                                            <i class="fas fa-truck"></i> Quantity Delivered
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="sortable text-center" data-sort="allocated">
                                            <i class="fas fa-chart-bar"></i> Quantity Allocated
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="sortable text-center" data-sort="date">
                                            <i class="fas fa-calendar"></i> Date of Delivery | YY-MM-DD
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="sortable" data-sort="site">
                                            <i class="fas fa-school"></i>Name Of School / Delivery Site
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="text-center">
                                            <i class="fas fa-cogs"></i> Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="deliveryTableBody">
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Enhanced Add Delivery Modal -->
<div class="modal fade" id="addDeliveryModal" tabindex="-1" aria-labelledby="addDeliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-enhanced">
            <div class="modal-header modal-header-enhanced bg-gradient-primary">
                <h5 class="modal-title" id="addDeliveryModalLabel">
                    <i class="fas fa-plus-circle"></i> Add New Delivery Record
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addDeliveryForm">
                <div class="modal-body modal-body-enhanced">
                    <div class="form-section">
                        <div class="form-group form-group-enhanced">
                            <label for="title_grade_level" class="form-label-enhanced">
                                <i class="fas fa-book"></i> Title and Grade Level <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-enhanced" id="title_grade_level"
                                name="title_and_grade_level" placeholder="e.g., Grade 7 - Mathematics Books" required>
                            <div class="form-feedback"></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-boxes"></i> Quantity Information
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group form-group-enhanced">
                                    <label for="quantity_delivered" class="form-label-enhanced">
                                        <i class="fas fa-truck"></i> Quantity Delivered
                                    </label>
                                    <input type="number" class="form-control form-control-enhanced"
                                        id="quantity_delivered" name="quantity_delivered" min="0" value="0">
                                    <div class="form-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group form-group-enhanced">
                                    <label for="quantity_allocated" class="form-label-enhanced">
                                        <i class="fas fa-chart-bar"></i> Quantity Allocated
                                    </label>
                                    <input type="number" class="form-control form-control-enhanced"
                                        id="quantity_allocated" name="quantity_allocated" min="0" value="0">
                                    <div class="form-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Delivery Details
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group form-group-enhanced">
                                    <label for="delivery_date" class="form-label-enhanced">
                                        <i class="fas fa-calendar"></i> Date of Delivery
                                    </label>
                                    <input type="date" class="form-control form-control-enhanced"
                                        id="delivery_date" name="date_of_delivery">
                                    <div class="form-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group form-group-enhanced">
                                    <label for="delivery_site" class="form-label-enhanced">
                                        <i class="fas fa-school"></i> Delivery Site
                                    </label>
                                    <select class="form-control form-control-enhanced" id="delivery_site"
                                        name="name_of_school_delivery_site">
                                        <option value="MAHARLIKA NHS" selected>MAHARLIKA NHS</option>
                                        <option value="BISLIG CENTRAL ELEMENTARY SCHOOL">BISLIG CENTRAL ELEMENTARY SCHOOL</option>
                                        <option value="BISLIG NATIONAL HIGH SCHOOL">BISLIG NATIONAL HIGH SCHOOL</option>
                                        <option value="OTHER">Other (specify below)</option>
                                    </select>
                                    <div class="form-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group form-group-enhanced" id="otherSiteGroup" style="display: none;">
                            <label for="other_site" class="form-label-enhanced">
                                <i class="fas fa-map-marker-alt"></i> Specify Other Delivery Site
                            </label>
                            <input type="text" class="form-control form-control-enhanced"
                                id="other_site" placeholder="Enter delivery site name">
                            <div class="form-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-enhanced">
                    <button type="button" class="btn btn-secondary btn-enhanced" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-enhanced">
                        <i class="fas fa-plus-circle"></i> Add Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Delivery Modal -->
<div class="modal fade" id="editDeliveryModal" tabindex="-1" aria-labelledby="editDeliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editDeliveryModalLabel">✏️ Edit Delivery Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editDeliveryForm">
                <input type="hidden" id="edit_delivery_id" name="delivery_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_title_grade_level">📖 Title and Grade Level <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title_grade_level" name="title_and_grade_level" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_quantity_delivered">📦 Quantity Delivered</label>
                                <input type="number" class="form-control" id="edit_quantity_delivered" name="quantity_delivered" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_quantity_allocated">📊 Quantity Allocated</label>
                                <input type="number" class="form-control" id="edit_quantity_allocated" name="quantity_allocated" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_delivery_date">📅 Date of Delivery</label>
                                <input type="date" class="form-control" id="edit_delivery_date" name="date_of_delivery">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_delivery_site">🏫 Name of School/Delivery Site</label>
                                <input type="text" class="form-control" id="edit_delivery_site" name="name_of_school_delivery_site">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">✏️ Update Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="importModalLabel">📤 Import Excel File</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="excel_file">📁 Choose Excel File</label>
                        <input type="file" class="form-control-file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                        <small class="form-text text-muted">Upload Excel file with delivery data (.xlsx or .xls)</small>
                    </div>
                    <div class="alert alert-info">
                        <strong>📋 Expected Excel Format:</strong><br>
                        Columns: Title and Grade Level, Quantity Delivered, Quantity Allocated, Date of Delivery, Name of School/Delivery Site
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="downloadTemplate()">
                        📥 Download Template
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="import_excel" class="btn btn-info">📤 Import Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<link rel="stylesheet" href="stats.css">

<script>
    // Ensure DOM is fully loaded before executing
    $(document).ready(function() {
        console.log('🔍 Starting debug - jQuery loaded:', typeof $ !== 'undefined');
        console.log('🔍 Document ready state:', document.readyState);

        // Wait a bit for AdminLTE to initialize
        setTimeout(function() {
            console.log('🔍 Delayed initialization...');
            loadDeliveries();

            // Set today's date as default
            var today = new Date().toISOString().split('T')[0];
            $('#delivery_date').val(today);
            console.log('🔍 Date set to:', today);
        }, 500);
    });

    // Handle delivery site selection
    $(document).on('change', '#delivery_site', function() {
        console.log('🔍 Site changed to:', $(this).val());
        if ($(this).val() === 'OTHER') {
            $('#otherSiteGroup').show();
            $('#other_site').prop('required', true);
        } else {
            $('#otherSiteGroup').hide();
            $('#other_site').prop('required', false);
        }
    });

    // Load deliveries data with enhanced debugging
    function loadDeliveries() {
        console.log('🔍 loadDeliveries() called');

        // Check if the table body exists
        var tbody = $('#deliveryTableBody');
        console.log('🔍 Table body found:', tbody.length > 0);

        if (tbody.length === 0) {
            console.error('❌ Table body not found!');
            return;
        }

        console.log('🔍 Making AJAX request...');

        $.ajax({
            url: '../admin/library_delivery_operations.php',
            type: 'POST',
            data: {
                action: 'fetch_deliveries'
            },
            dataType: 'json',
            success: function(response) {
                console.log('✅ AJAX Success:', response);

                if (response.success) {
                    tbody.empty();
                    console.log('🔍 Number of records:', response.data ? response.data.length : 0);

                    if (!response.data || response.data.length === 0) {
                        console.log('📝 No records - showing empty message');
                        tbody.append('<tr><td colspan=\"6\" class=\"text-center text-muted\">No delivery records found</td></tr>');
                    } else {
                        console.log('📊 Building table rows...');

                        $.each(response.data, function(index, delivery) {
                            console.log('📋 Processing record ' + (index + 1) + ':', delivery);

                            var row = '<tr style=\"border: 1px solid black;\">' +
                                '<td style=\"border: 1px solid black;\">' + (delivery.title_and_grade_level || '') + '</td>' +
                                '<td style=\"border: 1px solid black;\">' + (delivery.quantity_delivered || '') + '</td>' +
                                '<td style=\"border: 1px solid black;\">' + (delivery.quantity_allocated || '') + '</td>' +
                                '<td style=\"border: 1px solid black;\">' + (delivery.date_of_delivery || '') + '</td>' +
                                '<td style=\"border: 1px solid black;\">' + (delivery.name_of_school_delivery_site || '') + '</td>' +
                                '<td style=\"border: 1px solid black;\">' +
                                '<button class=\"btn btn-sm btn-warning\" onclick=\"editDelivery(' + delivery.DeliveryID + ')\">' +
                                '<i class=\"fas fa-edit\"></i> ✏️' +
                                '</button> ' +
                                '<button class=\"btn btn-sm btn-danger\" onclick=\"deleteDelivery(' + delivery.DeliveryID + ')\">' +
                                '<i class=\"fas fa-trash\"></i> 🗑️' +
                                '</button>' +
                                '</td>' +
                                '</tr>';

                            tbody.append(row);
                        });

                        console.log('✅ Table rows added successfully');
                    }
                } else {
                    console.error('❌ Server returned error:', response.message);
                    showNotification('Failed to load delivery records: ' + response.message, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('❌ AJAX Error:', textStatus, errorThrown);
                console.error('❌ Response text:', jqXHR.responseText);

                var errorMsg = 'Failed to load delivery records. ';
                if (jqXHR.status === 404) {
                    errorMsg += 'Backend file not found.';
                } else if (jqXHR.status === 500) {
                    errorMsg += 'Server error.';
                } else {
                    errorMsg += 'Network error.';
                }

                showNotification(errorMsg, 'error');

                // Show debug info in table
                tbody.html('<tr><td colspan=\"6\" class=\"text-center text-danger\">Error loading data. Check console for details.</td></tr>');
            }
        });
    }

    // Add delivery form submission with debugging
    $(document).on('submit', '#addDeliveryForm', function(e) {
        e.preventDefault();
        console.log('🔍 Form submitted');

        var formData = $(this).serialize();
        formData += '&action=add_delivery';

        // Handle "Other" site selection
        if ($('#delivery_site').val() === 'OTHER') {
            formData = formData.replace(/name_of_school_delivery_site=[^&]*/, 'name_of_school_delivery_site=' + encodeURIComponent($('#other_site').val()));
        }

        console.log('🔍 Sending form data:', formData);

        $.ajax({
            url: '../admin/library_delivery_operations.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('✅ Add response:', response);

                if (response.success) {
                    $('#addDeliveryModal').modal('hide');
                    $('#addDeliveryForm')[0].reset();
                    loadDeliveries(); // Reload the table
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('❌ Add delivery AJAX failed:', textStatus, errorThrown);
                showNotification('Failed to add delivery. Please try again.', 'error');
            }
        });
    });

    // Edit delivery
    function editDelivery(id) {
        $.post('../admin/library_delivery_operations.php', {
            action: 'fetch_deliveries'
        }, function(response) {
            if (response.success) {
                let delivery = response.data.find(d => d.DeliveryID == id);
                if (delivery) {
                    $('#edit_delivery_id').val(delivery.DeliveryID);
                    $('#edit_title_grade_level').val(delivery.title_and_grade_level);
                    $('#edit_quantity_delivered').val(delivery.quantity_delivered);
                    $('#edit_quantity_allocated').val(delivery.quantity_allocated);
                    $('#edit_delivery_date').val(delivery.date_of_delivery);
                    $('#edit_delivery_site').val(delivery.name_of_school_delivery_site);
                    $('#editDeliveryModal').modal('show');
                }
            }
        }, 'json');
    }

    // Update delivery form submission
    $('#editDeliveryForm').submit(function(e) {
        e.preventDefault();

        let formData = $(this).serialize();
        formData += '&action=update_delivery';

        $.post('../admin/library_delivery_operations.php', formData, function(response) {
            if (response.success) {
                $('#editDeliveryModal').modal('hide');
                loadDeliveries();
                showNotification(response.message, 'success');
            } else {
                showNotification(response.message, 'error');
            }
        }, 'json');
    });

    // Delete delivery
    function deleteDelivery(id) {
        if (confirm('🗑️ Are you sure you want to delete this delivery record?')) {
            $.post('../admin/library_delivery_operations.php', {
                action: 'delete_delivery',
                delivery_id: id
            }, function(response) {
                if (response.success) {
                    loadDeliveries();
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'error');
                }
            }, 'json');
        }
    }

    function exportToExcel() {
        window.location.href = 'delivery.php?export=excel';
    }

    function downloadTemplate() {
        window.location.href = '../admin/library_delivery_excel.php?action=template';
    }


    function showNotification(message, type) {
        let alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        let alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ${message}
        </div>
    `;
        $('.card-body').prepend(alertHtml);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Enhanced statistics and utility functions
    function loadStatistics() {
        $.post('../admin/library_delivery_operations.php', {
                action: 'get_stats'
            },
            function(response) {
                if (response.success) {
                    updateStatisticsDisplay(response.data);
                }
            }, 'json');
    }

    function updateStatistics() {
        const totalDeliveries = deliveryData.length;
        const totalBooks = deliveryData.reduce((sum, delivery) =>
            sum + (parseInt(delivery.quantity_delivered) || 0), 0);
        animateNumber('#totalDeliveries .stat-number', totalDeliveries);
        animateNumber('#totalBooks .stat-number', totalBooks);
    }

    function updateStatisticsDisplay(stats) {
        animateNumber('#totalDeliveries .stat-number', stats.total_deliveries || 0);
        animateNumber('#totalBooks .stat-number', stats.total_delivered || 0);
    }

    function animateNumber(selector, targetNumber) {
        const $element = $(selector);
        const startNumber = parseInt($element.text()) || 0;
        const increment = (targetNumber - startNumber) / 20;
        let currentNumber = startNumber;
        const timer = setInterval(() => {
            currentNumber += increment;
            if ((increment > 0 && currentNumber >= targetNumber) ||
                (increment <= 0 && currentNumber <= targetNumber)) {
                currentNumber = targetNumber;
                clearInterval(timer);
            }
            $element.text(Math.round(currentNumber));
        }, 50);
    }

    // Add dynamic CSS for enhanced UI animations
    $('<style>').prop('type', 'text/css').html(`
        .table-row-animated { opacity: 0; transform: translateY(20px); transition: all 0.3s ease; }
        .table-row-animated.fade-in-up { opacity: 1; transform: translateY(0); }
        .empty-state td { background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%); }
        .badge-pill { padding: 0.5em 0.8em; font-size: 0.9em; font-weight: 600; }
        .btn-enhanced:hover { transform: translateY(-2px); transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-3px); }
    `).appendTo('head');
</script>