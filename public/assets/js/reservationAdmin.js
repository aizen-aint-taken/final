 
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
                        'Approved': ['Pending', 'Rejected'],
                        'Returned': ['Pending', 'Approved', 'Rejected']
                    };

                
                    if (invalidTransitions[previousStatus] && invalidTransitions[previousStatus].includes(newStatus)) {
                        alert(`❌ Cannot change status from "${previousStatus}" to "${newStatus}"`);
                        dropdownElement.value = previousStatus;
                        return;
                    }

                    if (newStatus === 'Returned' && previousStatus !== 'Approved') {
                        alert("❌ Can only mark approved books as returned.");
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

                                if (newStatus === 'Approved' && data.dueDate) {
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

            // Handle select all checkbox
            const selectAllCheckbox = document.getElementById('selectAll');
            const deleteSelectedBtn = document.getElementById('deleteSelected');

            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.reservation-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonVisibility();
            });

            // Handle individual checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('reservation-checkbox')) {
                    updateDeleteButtonVisibility();
                }
            });

            // Update delete button visibility
            function updateDeleteButtonVisibility() {
                const checkedBoxes = document.querySelectorAll('.reservation-checkbox:checked');
                deleteSelectedBtn.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
            }

            // Handle single deletion
            document.querySelectorAll('.delete-single').forEach(button => {
                button.addEventListener('click', function() {
                    const reservationId = this.getAttribute('data-id');
                    deleteReservations([reservationId]);
                });
            });

            // Handle bulk deletion
            deleteSelectedBtn.addEventListener('click', function() {
                const selectedIds = Array.from(document.querySelectorAll('.reservation-checkbox:checked'))
                    .map(checkbox => checkbox.getAttribute('data-id'));

                if (selectedIds.length > 0) {
                    deleteReservations(selectedIds);
                }
            });

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
                                if (row) row.remove();
                            });
                            updateDeleteButtonVisibility();
                        } else {
                            alert('❌ Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('❌ Error deleting reservations');
                    });
            }
        });

        document.getElementById("sendNotificationsBtn").addEventListener("click", function() {
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
