document.addEventListener('DOMContentLoaded', function() {

    var editModal = document.getElementById('editStudentModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        console.log(event.relatedTarget);

        // attributes sa edit button 

        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var age = button.getAttribute('data-age');
        var year = button.getAttribute('data-year');
        var sect = button.getAttribute('data-sect');
        var mail = button.getAttribute('data-mail');


        // display sa modal current values

        document.getElementById('edit-id').value = id;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-age').value = age;
        document.getElementById('edit-year').value = year;
        document.getElementById('edit-sect').value = sect;
        document.getElementById('edit-mail').value = mail;
    });
});