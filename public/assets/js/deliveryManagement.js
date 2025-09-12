// Delivery Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize delivery management when modal opens
    $('#deliveryManagementModal').on('shown.bs.modal', function() {
        loadDeliveryRecords();
        loadBookOptions();
    });

    // Add delivery form submission
    document.getElementById('addDeliveryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addDeliveryRecord();
    });

    // Import delivery form submission
    document.getElementById('importDeliveryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        importDeliveryData();
    });

    // Export delivery button
    document.getElementById('exportDeliveryBtn').addEventListener('click', function() {
        exportDeliveryData();
    });

    // Search functionality
    document.getElementById('deliverySearch').addEventListener('input', function() {
        filterDeliveryRecords(this.value);
    });
});

// Load delivery records from database
function loadDeliveryRecords() {
    fetch('../admin/delivery_operations.php?action=list')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('deliveryTableBody');
            tbody.innerHTML = '';
            
            if (data.success && data.records.length > 0) {
                data.records.forEach(record => {
                    const row = createDeliveryRow(record);
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No delivery records found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading delivery records:', error);
            showAlert('Error loading delivery records', 'danger');
        });
}

// Create table row for delivery record
function createDeliveryRow(record) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${record.DeliveryID}</td>
        <td>${record.title_and_grade_level}</td>
        <td class="text-center">${record.quantity_delivered || 0}</td>
        <td class="text-center">${record.quantity_allocated || 0}</td>
        <td class="text-center">${record.date_of_delivery || ''}</td>
        <td>${record.name_of_school_delivery_site || ''}</td>
        <td>
            <button class="btn btn-sm btn-warning me-1" onclick="editDeliveryRecord(${record.DeliveryID})" title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-danger" onclick="deleteDeliveryRecord(${record.DeliveryID})" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    return row;
}

// Load book options for dropdown
function loadBookOptions() {
    fetch('../admin/delivery_operations.php?action=books')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('deliveryBookSelect');
            select.innerHTML = '<option value="">Choose a book...</option>';
            
            if (data.success && data.books.length > 0) {
                data.books.forEach(book => {
                    const option = document.createElement('option');
                    option.value = book.BookID;
                    option.textContent = `${book.Title} - ${book.Author}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading books:', error);
        });
}

// Add new delivery record
function addDeliveryRecord() {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('book_id', document.getElementById('deliveryBookSelect').value);
    formData.append('title_and_grade_level', document.getElementById('deliveryTitleGradeLevel').value);
    formData.append('quantity_delivered', document.getElementById('deliveryQuantityDelivered').value);
    formData.append('quantity_allocated', document.getElementById('deliveryQuantityAllocated').value);
    formData.append('delivery_date', document.getElementById('deliveryDate').value);
    formData.append('delivery_site', document.getElementById('deliverySite').value);

    fetch('../admin/delivery_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Delivery record added successfully!', 'success');
            document.getElementById('addDeliveryForm').reset();
            loadDeliveryRecords();
            // Switch to list tab
            document.getElementById('delivery-list-tab').click();
        } else {
            showAlert(data.message || 'Error adding delivery record', 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding delivery record:', error);
        showAlert('Error adding delivery record', 'danger');
    });
}

// Edit delivery record
function editDeliveryRecord(deliveryId) {
    fetch(`../admin/delivery_operations.php?action=get&delivery_id=${deliveryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.record) {
                const record = data.record;
                document.getElementById('editDeliveryDeliveryId').value = record.DeliveryID;
                document.getElementById('editDeliveryTitle').value = record.Title;
                document.getElementById('editDeliveryTitleGradeLevel').value = record.title_and_grade_level || '';
                document.getElementById('editDeliveryQuantityDelivered').value = record.quantity_delivered || 0;
                document.getElementById('editDeliveryQuantityAllocated').value = record.quantity_allocated || 0;
                document.getElementById('editDeliveryDate').value = record.date_of_delivery || '';
                document.getElementById('editDeliverySite').value = record.name_of_school_delivery_site || '';
                
                const editModal = new bootstrap.Modal(document.getElementById('editDeliveryModal'));
                editModal.show();
            } else {
                showAlert('Error loading delivery record', 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading delivery record:', error);
            showAlert('Error loading delivery record', 'danger');
        });
}

// Update delivery record
function updateDeliveryRecord() {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('delivery_id', document.getElementById('editDeliveryDeliveryId').value);
    formData.append('title_and_grade_level', document.getElementById('editDeliveryTitleGradeLevel').value);
    formData.append('quantity_delivered', document.getElementById('editDeliveryQuantityDelivered').value);
    formData.append('quantity_allocated', document.getElementById('editDeliveryQuantityAllocated').value);
    formData.append('delivery_date', document.getElementById('editDeliveryDate').value);
    formData.append('delivery_site', document.getElementById('editDeliverySite').value);

    fetch('../admin/delivery_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Delivery record updated successfully!', 'success');
            loadDeliveryRecords();
            bootstrap.Modal.getInstance(document.getElementById('editDeliveryModal')).hide();
        } else {
            showAlert(data.message || 'Error updating delivery record', 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating delivery record:', error);
        showAlert('Error updating delivery record', 'danger');
    });
}

// Delete delivery record
function deleteDeliveryRecord(deliveryId) {
    if (confirm('Are you sure you want to delete this delivery record?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('delivery_id', deliveryId);

        fetch('../admin/delivery_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Delivery record deleted successfully!', 'success');
                loadDeliveryRecords();
            } else {
                showAlert(data.message || 'Error deleting delivery record', 'danger');
            }
        })
        .catch(error => {
            console.error('Error deleting delivery record:', error);
            showAlert('Error deleting delivery record', 'danger');
        });
    }
}

// Import delivery data
function importDeliveryData() {
    const fileInput = document.getElementById('deliveryFileUpload');
    if (!fileInput.files[0]) {
        showAlert('Please select a file to import', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'import');
    formData.append('delivery_file', fileInput.files[0]);

    fetch('../admin/delivery_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`Import successful! ${data.imported_count} records imported.`, 'success');
            loadDeliveryRecords();
            document.getElementById('importDeliveryForm').reset();
        } else {
            showAlert(data.message || 'Error importing data', 'danger');
        }
    })
    .catch(error => {
        console.error('Error importing delivery data:', error);
        showAlert('Error importing delivery data', 'danger');
    });
}

// Export delivery data
function exportDeliveryData() {
    const includeEmpty = document.getElementById('includeEmptyRecords').checked;
    const url = `../admin/delivery_operations.php?action=export&include_empty=${includeEmpty ? 1 : 0}`;
    
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = 'delivery_records.xlsx';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('Export started! File will download shortly.', 'info');
}

// Filter delivery records
function filterDeliveryRecords(searchTerm) {
    const tbody = document.getElementById('deliveryTableBody');
    const rows = tbody.getElementsByTagName('tr');
    
    for (let row of rows) {
        if (row.cells.length > 1) {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        }
    }
}

// Refresh delivery list
function refreshDeliveryList() {
    loadDeliveryRecords();
    showAlert('Delivery records refreshed!', 'info');
}

// Show alert message
function showAlert(message, type) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert at top of modal body
    const modalBody = document.querySelector('#deliveryManagementModal .modal-body');
    modalBody.insertBefore(alertDiv, modalBody.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}