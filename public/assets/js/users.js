  $(document).ready(function() {

                $('.rb').on('click', function() {
                  
                    const bookId = $(this).data('id');
                    const bookTitle = $(this).data('title');
                    const bookAuthor = $(this).data('author');
                    console.log(bookId, bookTitle, bookAuthor);

                    $('#reserveBookId').val(bookId);
                    $('#reserveBookTitle').val(bookTitle);
                    $('#reserveBookAuthor').val(bookAuthor);
                });

                $('.rbm').on('click', function() {
                
                    const bookId = $(this).data('id');
                    const bookTitle = $(this).data('title');
                    const bookAuthor = $(this).data('author');
                    // console.log(bookId, bookTitle, bookAuthor);

                    $('#reserveBookIdm').val(bookId);
                    $('#reserveBookTitlem').val(bookTitle);
                    $('#reserveBookAuthorm').val(bookAuthor);
                });

                $('#booksTable').DataTable();

                $('form.filter-form').on('submit', function(event) {
                    const booksFilter = $('#booksFilter').val();
                    console.log(booksFilter);

                    if (booksFilter === null || booksFilter === '') {
                        event.preventDefault();
                        alert('Please select a genre before selecting a subject.');
                    }
                });
            });

            const client = mqtt.connect('ws://broker.hivemq.com:8000/mqtt');
            client.on('connect', () => {
                console.log('Connected to MQTT broker');
                client.subscribe('library/admin/notifications');
            });

            client.on('message', (topic, message) => {
                if (topic === 'library/admin/notifications') {
                    const data = JSON.parse(message.toString());
                    const notification = `
                    <div class="alert alert-info">
                        <strong>New Reservation:</strong> ${data.title} by ${data.author}<br>
                        Reserved by: ${data.name}
                    </div>`;
                    document.getElementById('notifications').innerHTML += notification;
                }
            });