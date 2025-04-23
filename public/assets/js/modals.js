document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.editAdmin').forEach(button => {
                button.addEventListener('click', function() {
                    const name = this.getAttribute('data-name');
                    const email = this.getAttribute('data-email');
                    const role = this.getAttribute('data-role');

                    document.getElementById('editName').value = name;
                    document.getElementById('editEmail').value = email;
                    document.getElementById('editEmail').readOnly = true;
                    document.getElementById('editRole').value = role;
                });
            });


            document.querySelectorAll('[data-bs-target="#changeAdminPasswordModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    const adminEmail = this.getAttribute('data-email');
                    document.getElementById('change-admin-password-email').value = adminEmail;
                    document.getElementById('admin-email-display').textContent = adminEmail;
                });
            });


            document.querySelectorAll('.table-responsive .delete-admin').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const email = this.getAttribute('data-email');
                    const name = this.closest('tr').querySelector('td:first-child').textContent;

                    document.getElementById('deleteAdminName').textContent = name;
                    document.getElementById('deleteAdminEmail').textContent = email;

                    const deleteBtn = document.getElementById('confirmDeleteBtn');
                    deleteBtn.href = this.getAttribute('href');

                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
                    deleteModal.show();
                });
            });


            document.querySelectorAll('.mobile-cards .delete-admin').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const email = this.getAttribute('data-email');
                    const name = this.closest('.admin-card').querySelector('.card-title').textContent.trim();

                    document.getElementById('deleteAdminName').textContent = name;
                    document.getElementById('deleteAdminEmail').textContent = email;

                    const deleteBtn = document.getElementById('confirmDeleteBtn');
                    deleteBtn.href = this.getAttribute('href');

                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
                    deleteModal.show();
                });
            });


            const changePasswordForm = document.querySelector('#changeAdminPasswordModal form');
            if (changePasswordForm) {
                changePasswordForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    formData.append('change_admin_password', '1');

                    fetch('change_password.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: data.message
                                }).then(() => {
                                    bootstrap.Modal.getInstance(document.getElementById('changeAdminPasswordModal')).hide();
                                    changePasswordForm.reset();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while changing the password'
                            });
                        });
                });
            }
        });