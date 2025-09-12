// Library Delivery Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize delivery management when modal opens
    const libraryDeliveryModal = document.getElementById('libraryDeliveryModal');
    if (libraryDeliveryModal) {
        libraryDeliveryModal.addEventListener('shown.bs.modal', function() {
            loadDeliveryRecords();
        });
    }

    // Add delivery form submission
    const addDeliveryForm = document.getElementById('addDeliveryForm');
    if (addDeliveryForm) {
        addDeliveryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addDeliveryRecord();
        });
    }

    // Import delivery form submission
    const importDeliveryForm = document.getElementById('importDeliveryForm');
    if (importDeliveryForm) {
        importDeliveryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            importDeliveryData();
        });
    }

    // Export delivery button
    const exportDeliveryBtn = document.getElementById('exportDeliveryBtn');
    if (exportDeliveryBtn) {
        exportDeliveryBtn.addEventListener('click', function() {
            exportDeliveryData();
        });
    }

    // Search functionality
    const deliverySearch = document.getElementById('deliverySearch');
    if (deliverySearch) {
        deliverySearch.addEventListener('input', function() {
            filterDeliveryRecords(this.value);
        });
    }
});

// Load delivery records from database
function loadDeliveryRecords() {
    fetch('../admin/library_delivery_operations.php?action=list')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('deliveryTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (data.success && data.deliveries.length > 0) {
                data.deliveries.forEach(delivery => {
                    const row = createDeliveryRow(delivery);
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">📋 No delivery records found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading delivery records:', error);
            showAlert('❌ Error loading delivery records', 'danger');
        });
}

// Create table row for delivery record
function createDeliveryRow(delivery) {
    const row = document.createElement('tr');
    const deliveryDate = delivery.date_of_delivery ? new Date(delivery.date_of_delivery).toLocaleDateString() : '';
    
    row.innerHTML = `
        <td>${delivery.DeliveryID}</td>
        <td>${delivery.title_and_grade_level}</td>
        <td class="text-center">${delivery.quantity_delivered || 0}</td>
        <td class="text-center">${delivery.quantity_allocated || 0}</td>
        <td class="text-center">${deliveryDate}</td>
        <td>${delivery.name_of_school_delivery_site || ''}</td>
        <td class="text-center">
            <button class="btn btn-sm btn-warning me-1" onclick="editDeliveryRecord(${delivery.DeliveryID})" title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-danger" onclick="deleteDeliveryRecord(${delivery.DeliveryID})" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    return row;
}

// Add new delivery record
function addDeliveryRecord() {
    const titleGradeLevel = document.getElementById('deliveryTitleGradeLevel').value.trim();
    const quantityDelivered = document.getElementById('deliveryQuantityDelivered').value;
    const quantityAllocated = document.getElementById('deliveryQuantityAllocated').value;
    const deliveryDate = document.getElementById('deliveryDate').value;
    const deliverySite = document.getElementById('deliverySite').value;
    const otherSite = document.getElementById('deliveryOtherSite').value;
    
    if (!titleGradeLevel) {
        showAlert('❌ Title and Grade Level is required', 'danger');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('title_grade_level', titleGradeLevel);
    formData.append('quantity_delivered', quantityDelivered);
    formData.append('quantity_allocated', quantityAllocated);
    formData.append('delivery_date', deliveryDate);
    formData.append('delivery_site', deliverySite === 'OTHER' ? otherSite : deliverySite);

    fetch('../admin/library_delivery_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('✅ ' + data.message, 'success');
            document.getElementById('addDeliveryForm').reset();
            document.getElementById('deliveryDate').valueAsDate = new Date(); // Reset to today
            loadDeliveryRecords();
            // Switch to list tab
            document.getElementById('delivery-list-tab').click();
        } else {
            showAlert('❌ ' + (data.message || 'Error adding delivery record'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding delivery record:', error);
        showAlert('❌ Error adding delivery record', 'danger');
    });
}

// Edit delivery record
function editDeliveryRecord(deliveryId) {
    fetch(`../admin/library_delivery_operations.php?action=get&delivery_id=${deliveryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.delivery) {
                const delivery = data.delivery;
                document.getElementById('editDeliveryId').value = delivery.DeliveryID;
                document.getElementById('editDeliveryTitleGradeLevel').value = delivery.title_and_grade_level || '';
                document.getElementById('editDeliveryQuantityDelivered').value = delivery.quantity_delivered || 0;
                document.getElementById('editDeliveryQuantityAllocated').value = delivery.quantity_allocated || 0;
                document.getElementById('editDeliveryDate').value = delivery.date_of_delivery || '';
                document.getElementById('editDeliverySite').value = delivery.name_of_school_delivery_site || '';
                
                const editModal = new bootstrap.Modal(document.getElementById('editDeliveryModal'));
                editModal.show();
            } else {
                showAlert('❌ Error loading delivery record', 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading delivery record:', error);
            showAlert('❌ Error loading delivery record', 'danger');
        });
}

// Update delivery record
function updateDeliveryRecord() {
    const deliveryId = document.getElementById('editDeliveryId').value;
    const titleGradeLevel = document.getElementById('editDeliveryTitleGradeLevel').value.trim();
    const quantityDelivered = document.getElementById('editDeliveryQuantityDelivered').value;
    const quantityAllocated = document.getElementById('editDeliveryQuantityAllocated').value;
    const deliveryDate = document.getElementById('editDeliveryDate').value;
    const deliverySite = document.getElementById('editDeliverySite').value;
    
    if (!titleGradeLevel) {
        showAlert('❌ Title and Grade Level is required', 'danger');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('delivery_id', deliveryId);
    formData.append('title_grade_level', titleGradeLevel);
    formData.append('quantity_delivered', quantityDelivered);
    formData.append('quantity_allocated', quantityAllocated);
    formData.append('delivery_date', deliveryDate);
    formData.append('delivery_site', deliverySite);

    fetch('../admin/library_delivery_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('✅ ' + data.message, 'success');
            loadDeliveryRecords();
            bootstrap.Modal.getInstance(document.getElementById('editDeliveryModal')).hide();
        } else {
            showAlert('❌ ' + (data.message || 'Error updating delivery record'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating delivery record:', error);
        showAlert('❌ Error updating delivery record', 'danger');
    });
}

// Delete delivery record
function deleteDeliveryRecord(deliveryId) {
    if (confirm('🗑️ Are you sure you want to delete this delivery record?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('delivery_id', deliveryId);

        fetch('../admin/library_delivery_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('✅ ' + data.message, 'success');
                loadDeliveryRecords();
            } else {
                showAlert('❌ ' + (data.message || 'Error deleting delivery record'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error deleting delivery record:', error);
            showAlert('❌ Error deleting delivery record', 'danger');
        });
    }
}

// Import delivery data
function importDeliveryData() {
    const fileInput = document.getElementById('deliveryFileUpload');
    
    if (!fileInput.files.length) {
        showAlert('❌ Please select an Excel file to import', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'import');
    formData.append('excel_file', fileInput.files[0]);

    fetch('../admin/library_delivery_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('✅ ' + data.message, 'success');
            document.getElementById('importDeliveryForm').reset();
            loadDeliveryRecords();
            // Switch to list tab
            document.getElementById('delivery-list-tab').click();
        } else {
            showAlert('❌ ' + (data.message || 'Error importing delivery data'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error importing delivery data:', error);
        showAlert('❌ Error importing delivery data', 'danger');
    });
}

// Export delivery data
function exportDeliveryData() {
    window.location.href = '../admin/library_delivery_operations.php?action=export';
}

// Refresh delivery list
function refreshDeliveryList() {
    loadDeliveryRecords();
    showAlert('🔄 Delivery list refreshed', 'info');
}

// Filter delivery records
function filterDeliveryRecords(searchTerm) {
    const tbody = document.getElementById('deliveryTableBody');
    if (!tbody) return;
    
    const rows = tbody.querySelectorAll('tr');
    const term = searchTerm.toLowerCase().trim();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let rowText = '';
        
        // Get text from all cells except the actions column
        for (let i = 0; i < cells.length - 1; i++) {
            rowText += cells[i].textContent.toLowerCase() + ' ';
        }
        
        if (term === '' || rowText.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Show alert messages
function showAlert(message, type) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}