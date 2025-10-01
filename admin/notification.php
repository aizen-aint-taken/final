<?php
include("../config/conn.php");

$tomorrow = date('Y-m-d', strtotime('+1 day'));

$sevenDays = date('Y-m-d', strtotime('+7 days'));

$query = "
    SELECT U.email, U.name, B.Title, R.ReserveDate, R.DueDate,
    CASE 
        WHEN R.DueDate = ? THEN 'tomorrow'
        WHEN R.DueDate = ? THEN '7 days'
    END as due_period
    FROM reservations R
    INNER JOIN users U ON R.StudentID = U.id
    INNER JOIN books B ON R.BookID = B.BookID
    WHERE (R.DueDate = ? OR R.DueDate = ?)
    AND R.STATUS = 'Borrowed'
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $tomorrow, $sevenDays, $tomorrow, $sevenDays);
$stmt->execute();
$result = $stmt->get_result();

$notifications_sent = 0;

while ($row = $result->fetch_assoc()) {
    $email = $row['email'];
    $name = $row['name'];
    $book = $row['Title'];
    $dueDate = $row['DueDate'];
    $due_period = $row['due_period'];

    $subject = "Library Book Due Date Reminder 📚";
    $message = "
        Hello $name,\n\n
        This is a friendly reminder that the book **'$book'** is due " .
        ($due_period === 'tomorrow' ? "tomorrow" : "in 7 days") .
        " ($dueDate).\n
        Please return it on time to avoid penalties.\n\n
        Thank you,\nLibrary Admin
    ";

    $headers = "From: admin@gmail.com\r\n";

    if (mail($email, $subject, $message, $headers)) {
        $notifications_sent++;
    }
}

$response = [
    'success' => true,
    'message' => "Sent $notifications_sent due date reminder(s) successfully!",
    'count' => $notifications_sent
];

echo json_encode($response);
