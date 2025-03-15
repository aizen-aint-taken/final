document.addEventListener("DOMContentLoaded", function () {
    // DELETE USER
    document.querySelectorAll(".btn-delete").forEach((button) => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            let row = this.closest("tr");
            let userId = row.querySelector("input[name='delete_id']").value;

            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("../admin/deleteStudent.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `delete_id=${userId}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            row.remove();
                            Swal.fire("Deleted!", "Student has been deleted.", "success");
                        } else {
                            Swal.fire("Error!", "Failed to delete student.", "error");
                        }
                    });
                }
            });
        });
    });

 
    document.querySelector("#editStudentModal form").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);

        fetch("../admin/student.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "success") {
                Swal.fire("Updated!", "Student details updated.", "success").then(() => {
                    location.reload();
                });
            } else {
                Swal.fire("Error!", "Update failed.", "error");
            }
        });
    });

    // ADD USER
    document.querySelector("#addBookModal form").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);

        fetch("../admin/student.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "success") {
                Swal.fire("Added!", "New student has been added.", "success").then(() => {
                    location.reload();
                });
            } else {
                Swal.fire("Error!", "Failed to add student.", "error");
            }
        });
    });
});
