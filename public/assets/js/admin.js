document.querySelectorAll('.editAdmin').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('editName').value = this.getAttribute('data-name');
        document.getElementById('editEmail').value = this.getAttribute('data-email');
        document.getElementById('editRole').value = this.getAttribute('data-role');
    });
});

// Update the delete button handling
document.querySelectorAll('.delete-admin').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = this.href;
            }
        });
    });
});