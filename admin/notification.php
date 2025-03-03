<?php
include("../config/conn.php");

$due = date('Y-m-d', strtotime('+1 day'));

$query = "
    SELECT U.email, U.name, B.Title, R.ReserveDate, DATE_ADD(R.ReserveDate, INTERVAL 7 DAY) AS DueDate
    FROM reservations R
    INNER JOIN users U ON R.StudentID = U.id
    INNER JOIN books B ON R.BookID = B.BookID
    WHERE DATE_ADD(R.ReserveDate, INTERVAL 7 DAY) = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $due);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $email = $row['email'];
    $name = $row['name'];
    $book = $row['Title'];
    $dueDate = $row['DueDate'];

    $subject = "Library Due Date Reminder 📚";
    $message = "
        Hello $name,\n\n
        This is a friendly reminder that the book **'$book'** you reserved is **due tomorrow ($dueDate)**.\n
        Please return it on time to avoid penalties.\n\n
        Thank you, Library Admin
    ";

    // $headers = "From: no-reply@yourlibrary.com\r\n";
    $headers = "From: admin@gmail.com\r\n";

    mail($email, $subject, $message, $headers);
}

echo "Due date reminders sent successfully!";
