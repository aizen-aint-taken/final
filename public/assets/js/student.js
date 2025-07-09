$(document).ready(function() {
    function filterStudents() {
        var search = $('#studentSearchInput').val().toLowerCase();
        var year = $('#yearFilterSelect').val();
        $('table.table tbody tr').each(function() {
            var row = $(this);
            var name = row.find('td:nth-child(1)').text().toLowerCase();
            var age = row.find('td:nth-child(2)').text().toLowerCase();
            var yearLevel = row.find('td:nth-child(3)').text().toLowerCase();
            var sect = row.find('td:nth-child(4)').text().toLowerCase();
            var email = row.find('td:nth-child(5)').text().toLowerCase();
            var match = (
                (name.indexOf(search) !== -1 ||
                email.indexOf(search) !== -1 ||
                sect.indexOf(search) !== -1)
            );
            var yearMatch = (!year || yearLevel === year.toLowerCase());
            if (match && yearMatch) {
                row.show();
            } else {
                row.hide();
            }
        });
        $('.mobile-cards .card').each(function() {
            var card = $(this);
            var name = card.find('.card-title').text().toLowerCase();
            var age = card.find('.card-text p').eq(0).text().toLowerCase();
            var yearLevel = card.find('.card-text p').eq(1).text().replace('Year Level:', '').trim().toLowerCase();
            var sect = card.find('.card-text p').eq(2).text().replace('Section:', '').trim().toLowerCase();
            var email = card.find('.card-text p').eq(3).text().replace('Email:', '').trim().toLowerCase();
            var match = (
                (name.indexOf(search) !== -1 ||
                email.indexOf(search) !== -1 ||
                sect.indexOf(search) !== -1)
            );
            var yearMatch = (!year || yearLevel === year.toLowerCase());
            if (match && yearMatch) {
                card.show();
            } else {
                card.hide();
            }
        });
    }
    $('#studentSearchInput').on('input', filterStudents);
    $('#yearFilterSelect').on('change', filterStudents);
    filterStudents();
}); 