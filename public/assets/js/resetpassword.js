document.querySelectorAll('[data-bs-target="#changePasswordModal"]').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const email = this.getAttribute('data-email');

        document.getElementById('change-password-id').value = id;
        document.getElementById('change-password-email').value = email;
        document.getElementById('student-email-display').textContent = email;
    });
});

document.querySelector('#changePasswordModal form').addEventListener('submit', function(e) {
    e.preventDefault();
    const password = this.querySelector('input[name="new_password"]').value;
    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;

    if (password !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Passwords Do Not Match',
            text: 'Please make sure your passwords match!',
            confirmButtonColor: '#d33'
        });
        return;
    }

    if (password.length < 6) {
        Swal.fire({
            icon: 'warning',
            title: 'Password Too Short',
            text: 'Password must be at least 6 characters long!',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    Swal.fire({
        title: 'Change Password?',
        text: "Are you sure you want to change this student's password?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form data
            const formData = new FormData(this);
            formData.append('change_password', '1');

            // Use fetch to submit to current page instead
            fetch('', {  // Empty string means current page
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Changed!',
                        text: 'The password has been updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to change password',
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while changing the password',
                });
            });
        }
    });
});
 