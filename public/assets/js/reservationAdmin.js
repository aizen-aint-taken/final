 
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("filter-btn").addEventListener("click", function() {
                let userId = document.getElementById("user-filter").value;
                let status = document.getElementById("status-filter").value;

                let url = new URL(window.location.href);
                url.searchParams.set("user_id", userId);
                url.searchParams.set("status", status);

                window.location.href = url.toString();
            });

            document.querySelectorAll(".status-dropdown").forEach(dropdown => {
                dropdown.addEventListener("change", function(event) {
                    let dropdownElement = event.target;
                    let reservationId = dropdownElement.getAttribute("data-id");
                    let newStatus = dropdownElement.value;
                    let previousStatus = dropdownElement.dataset.previous;

                 
                    const invalidTransitions = {
                        'Borrowed': ['Pending', 'Rejected'],
                        'Returned': ['Pending', 'Borrowed', 'Rejected']
                    };

                
                    if (invalidTransitions[previousStatus] && invalidTransitions[previousStatus].includes(newStatus)) {
                        alert(`❌ Cannot change status from "${previousStatus}" to "${newStatus}"`);
                        dropdownElement.value = previousStatus;
                        return;
                    }

                    if (newStatus === 'Returned' && previousStatus !== 'Borrowed') {
                        alert("❌ Can only mark borrowed books as returned.");
                        dropdownElement.value = previousStatus;
                        return;
                    }

                    if (!confirm(`Are you sure you want to change the status to "${newStatus}"?`)) {
                        dropdownElement.value = previousStatus;
                        return;
                    }

                    let formData = new FormData();
                    formData.append("reservation_id", reservationId);
                    formData.append("status", newStatus);
                    formData.append("previous_status", previousStatus);

                    fetch("update_status.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(`✅ ${data.message}`);
                                dropdownElement.dataset.previous = newStatus;

                                const row = dropdownElement.closest('tr');

                                if (newStatus === 'Returned') {
                                    const dueDateInput = row.querySelector('.due-date-input');
                                    if (dueDateInput) {
                                        dueDateInput.style.display = 'none';
                                    }
                                }

                                if (newStatus === 'Borrowed' && data.dueDate) {
                                    const dueDateCell = row.querySelector('[data-label="Return Date"]');
                                    if (dueDateCell) {
                                        const formattedDate = new Date(data.dueDate).toLocaleDateString('en-US', {
                                            month: '2-digit',
                                            day: '2-digit',
                                            year: 'numeric'
                                        });
                                        dueDateCell.innerHTML = formattedDate;

                                        // Add the "Due in 7 days" badge
                                        const badge = document.createElement('span');
                                        badge.className = 'badge bg-warning ms-2';
                                        badge.textContent = '⚠ Due in 7 days';
                                        dueDateCell.appendChild(badge);
                                    }
                                }

                                // Refresh the page to update all statuses
                                location.reload();
                            } else {
                                alert(`❌ ${data.message}`);
                                dropdownElement.value = previousStatus;
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            alert("❌ Error updating status.");
                            dropdownElement.value = previousStatus;
                        });
                });

                // Store initial status
                dropdown.dataset.previous = dropdown.value;
            });

            // Handle select all checkbox (desktop)
            const selectAllDesktopCheckbox = document.getElementById('selectAllDesktop');
            const selectAllBulkCheckbox = document.getElementById('selectAllBulk');
            const deleteSelectedBtn = document.getElementById('deleteSelected');
            const bulkActionButtons = document.getElementById('bulkActionButtons');
            const bulkActionInfo = document.getElementById('bulkActionInfo');
            const selectedCountSpan = document.getElementById('selectedCount');
            const bulkApproveBtn = document.getElementById('bulkApprove');
            const bulkRejectBtn = document.getElementById('bulkReject');
            const bulkReturnBtn = document.getElementById('bulkReturn');

            if (selectAllDesktopCheckbox) {
                selectAllDesktopCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.bulk-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateBulkActionVisibility();
                });
            }

            // Handle bulk select all checkbox
            if (selectAllBulkCheckbox) {
                selectAllBulkCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    
                    // Show/hide checkbox column and individual checkboxes
                    toggleCheckboxVisibility(isChecked);
                    
                    if (isChecked) {
                        const checkboxes = document.querySelectorAll('.bulk-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    } else {
                        // Uncheck all checkboxes when hiding them
                        const checkboxes = document.querySelectorAll('.bulk-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    }
                    
                    updateBulkActionVisibility();
                });
            }
            
            // Function to toggle checkbox visibility
            function toggleCheckboxVisibility(show) {
                // Desktop view
                const selectAllColumn = document.getElementById('selectAllColumn');
                const checkboxCells = document.querySelectorAll('.checkbox-cell');
                
                if (selectAllColumn) {
                    selectAllColumn.style.display = show ? 'table-cell' : 'none';
                }
                
                checkboxCells.forEach(cell => {
                    cell.style.display = show ? 'table-cell' : 'none';
                });
                
                // Mobile view
                const mobileSelectAllContainer = document.getElementById('mobileSelectAllContainer');
                const mobileCheckboxContainers = document.querySelectorAll('.mobile-checkbox-container');
                
                if (mobileSelectAllContainer) {
                    mobileSelectAllContainer.style.display = show ? 'flex' : 'none';
                }
                
                mobileCheckboxContainers.forEach(container => {
                    container.style.display = show ? 'block' : 'none';
                });
            }

            // Handle select all checkbox (mobile)
            const selectAllMobileCheckbox = document.getElementById('selectAllMobile');
            if (selectAllMobileCheckbox) {
                selectAllMobileCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.bulk-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateBulkActionVisibility();
                });
            }

            // Handle individual checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('reservation-checkbox')) {
                    updateActionButtonsVisibility();
                }
                if (e.target.classList.contains('bulk-checkbox')) {
                    updateBulkActionVisibility();
                }
            });

            // Update bulk action visibility
            function updateBulkActionVisibility() {
                const checkedBoxes = document.querySelectorAll('.bulk-checkbox:checked');
                const selectedStatuses = Array.from(checkedBoxes).map(cb => cb.dataset.status);
                
                if (bulkActionButtons) {
                    if (checkedBoxes.length > 0) {
                        bulkActionButtons.style.display = 'block';
                        bulkActionInfo.style.display = 'block';
                        selectedCountSpan.textContent = checkedBoxes.length;
                        
                        // Show appropriate buttons based on selected statuses
                        const pendingSelected = selectedStatuses.filter(status => status === 'Pending').length;
                        const borrowedSelected = selectedStatuses.filter(status => status === 'Borrowed').length;
                        const returnedSelected = selectedStatuses.filter(status => status === 'Returned').length;
                        const rejectedSelected = selectedStatuses.filter(status => status === 'Rejected').length;
                        
                        // Show approve/reject buttons only if pending items are selected
                        if (bulkApproveBtn) bulkApproveBtn.style.display = pendingSelected > 0 ? 'inline-block' : 'none';
                        if (bulkRejectBtn) bulkRejectBtn.style.display = pendingSelected > 0 ? 'inline-block' : 'none';
                        
                        // Show return button only if borrowed items are selected
                        if (bulkReturnBtn) bulkReturnBtn.style.display = borrowedSelected > 0 ? 'inline-block' : 'none';
                        
                        // Show delete button for any selected items (including returned)
                        if (deleteSelectedBtn) deleteSelectedBtn.style.display = 'inline-block';
                        
                        // Update available actions info
                        const availableActionsSpan = document.getElementById('availableActions');
                        if (availableActionsSpan) {
                            let actions = [];
                            if (pendingSelected > 0) actions.push('Approve/Reject');
                            if (borrowedSelected > 0) actions.push('Return');
                            actions.push('Delete'); // Always available
                            availableActionsSpan.textContent = actions.join(', ');
                        }
                    } else {
                        bulkActionButtons.style.display = 'none';
                        bulkActionInfo.style.display = 'none';
                    }
                }
                
                // Update bulk select all checkbox state
                const allBulkCheckboxes = document.querySelectorAll('.bulk-checkbox');
                const checkedCount = checkedBoxes.length;
                
                if (selectAllBulkCheckbox) {
                    selectAllBulkCheckbox.checked = allBulkCheckboxes.length > 0 && checkedCount === allBulkCheckboxes.length;
                    selectAllBulkCheckbox.indeterminate = checkedCount > 0 && checkedCount < allBulkCheckboxes.length;
                }
            }

            // Update action buttons visibility based on selected items
            function updateActionButtonsVisibility() {
                const checkedBoxes = document.querySelectorAll('.reservation-checkbox:checked');
                const selectedStatuses = Array.from(checkedBoxes).map(cb => cb.dataset.status);
                
                // Update "Select All" checkboxes state
                const allCheckboxes = document.querySelectorAll('.reservation-checkbox');
                const checkedCount = checkedBoxes.length;
                
                const selectAllDesktop = document.getElementById('selectAllDesktop');
                const selectAllMobile = document.getElementById('selectAllMobile');
                
                if (selectAllDesktop) {
                    selectAllDesktop.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
                    selectAllDesktop.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
                }
                
                if (selectAllMobile) {
                    selectAllMobile.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
                    selectAllMobile.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
                }
            }

            // Handle single deletion
            document.querySelectorAll('.delete-single').forEach(button => {
                button.addEventListener('click', function() {
                    const reservationId = this.getAttribute('data-id');
                    deleteReservations([reservationId]);
                });
            });

            // Handle bulk deletion
            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', function() {
                    const selectedIds = Array.from(document.querySelectorAll('.bulk-checkbox:checked'))
                        .map(checkbox => checkbox.getAttribute('data-id'));

                    if (selectedIds.length > 0) {
                        deleteReservations(selectedIds);
                    }
                });
            }

            // Function to handle deletion
            function deleteReservations(ids) {
                if (!confirm(`Are you sure you want to delete ${ids.length} reservation(s)?`)) {
                    return;
                }

                fetch('delete_reservations.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ids: ids
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Reservations deleted successfully');
                            // Remove deleted rows from the table
                            ids.forEach(id => {
                                const row = document.getElementById(`row-${id}`);
                                const card = document.getElementById(`card-${id}`);
                                if (row) row.remove();
                                if (card) card.remove();
                            });
                            updateBulkActionVisibility();
                        } else {
                            alert('❌ Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('❌ Error deleting reservations');
                    });
            }
            
            // Handle bulk approve
            if (bulkApproveBtn) {
                bulkApproveBtn.addEventListener('click', function() {
                    const selectedIds = Array.from(document.querySelectorAll('.bulk-checkbox:checked'))
                        .filter(cb => cb.dataset.status === 'Pending')
                        .map(cb => cb.getAttribute('data-id'));
                    
                    if (selectedIds.length > 0) {
                        bulkUpdateStatus(selectedIds, 'Borrowed', 'approve');
                    }
                });
            }
            
            // Handle bulk reject
            if (bulkRejectBtn) {
                bulkRejectBtn.addEventListener('click', function() {
                    const selectedIds = Array.from(document.querySelectorAll('.bulk-checkbox:checked'))
                        .filter(cb => cb.dataset.status === 'Pending')
                        .map(cb => cb.getAttribute('data-id'));
                    
                    if (selectedIds.length > 0) {
                        bulkUpdateStatus(selectedIds, 'Rejected', 'reject');
                    }
                });
            }
            
            // Handle bulk return
            if (bulkReturnBtn) {
                bulkReturnBtn.addEventListener('click', function() {
                    const selectedIds = Array.from(document.querySelectorAll('.bulk-checkbox:checked'))
                        .filter(cb => cb.dataset.status === 'Borrowed')
                        .map(cb => cb.getAttribute('data-id'));
                    
                    if (selectedIds.length > 0) {
                        bulkUpdateStatus(selectedIds, 'Returned', 'return');
                    }
                });
            }
            
            // Function to handle bulk status updates
            function bulkUpdateStatus(ids, newStatus, action) {
                if (!confirm(`Are you sure you want to ${action} ${ids.length} reservation(s)?`)) {
                    return;
                }
                
                // Process each reservation individually
                let completed = 0;
                let errors = 0;
                
                ids.forEach(id => {
                    const formData = new FormData();
                    formData.append("reservation_id", id);
                    formData.append("status", newStatus);
                    formData.append("previous_status", "Pending");
                    
                    fetch("update_status.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        completed++;
                        if (!data.success) {
                            errors++;
                        }
                        
                        // Check if all requests are completed
                        if (completed === ids.length) {
                            if (errors === 0) {
                                alert(`✅ Successfully ${action}ed ${ids.length} reservation(s)`);
                            } else {
                                alert(`⚠️ Completed with ${errors} error(s) out of ${ids.length} reservation(s)`);
                            }
                            // Refresh the page to update all statuses
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        completed++;
                        errors++;
                        
                        if (completed === ids.length) {
                            alert(`❌ Bulk operation completed with ${errors} error(s)`);
                            location.reload();
                        }
                    });
                });
            }
        });

        // Handle notification sending (only if button exists)
        const sendNotificationsBtn = document.getElementById("sendNotificationsBtn");
        if (sendNotificationsBtn) {
            sendNotificationsBtn.addEventListener("click", function() {
                fetch('notification.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`✅ ${data.message}`);
                        } else {
                            alert("❌ Error sending notifications.");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("❌ Error sending notifications.");
                    });
            });
        }
