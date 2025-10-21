<?php
session_start();
include("../config/conn.php");

require_once '../vendor/autoload.php';




use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

if (isset($_POST['return'])) {
    $bookID = $_POST['book_id'];
    $studentID = $_SESSION['student_id'];
    $name = $_SESSION['username'];
    $returnDate = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT * FROM reservations WHERE BookID = ? AND StudentID = ? AND STATUS = 'Borrowed'");
    $stmt->bind_param("ii", $bookID, $studentID);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt = $conn->prepare("UPDATE reservations SET ReturnDate = ?, STATUS = 'Returned' WHERE BookID = ? AND StudentID = ?");
        $stmt->bind_param("sii", $returnDate, $bookID, $studentID);

        if ($stmt->execute()) {


            $stmt = $conn->prepare("UPDATE books SET stock_update = stock_update + 1 WHERE BookID = ?");
            $stmt->bind_param("i", $bookID);
            $stmt->execute();

            // Set up MQTT
            $mqtt = new MqttClient('broker.hivemq.com', 1883, uniqid());
            $settings = (new ConnectionSettings)->setUsername(null)->setPassword(null);
            $mqtt->connect($settings, true);

            // Prepare notification
            $notification = [
                'type' => 'return',
                'message' => 'A book has been returned!',
                'book_id' => $bookID,
                'name' => $name,
                'student_id' => $studentID,
                'title' => $_POST['book_title'],
                'author' => $_POST['book_author']
            ];

            // Publish MQTT notification
            $mqtt->publish('library/admin/notifications', json_encode($notification), MqttClient::QOS_AT_MOST_ONCE);
            $mqtt->disconnect();
            file_put_contents('mqtt_log.txt', json_encode($notification) . PHP_EOL, FILE_APPEND);

            $_SESSION['success'] = "Book returned successfully!";
        } else {
            $_SESSION['error'] = "Failed to return the book.";
        }
    } else {
        $_SESSION['error'] = "No borrowed book found for this book.";
    }

    header("Location: index.php");
    exit;
}
