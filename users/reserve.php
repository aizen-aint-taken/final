<?php
// echo "hello";

session_start();
include("../config/conn.php");

require_once '../vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

if (isset($_POST['reserve'])) {
    $bookID = $_POST['book_id'];
    $studentID = $_SESSION['student_id'];
    $name = $_SESSION['username'];
    $reserveDate = date('Y-m-d H:i:s');
    date_default_timezone_set("Asia/Manila");


    $stmt = $conn->prepare("SELECT Stock FROM books WHERE BookID = ?");
    $stmt->bind_param("i", $bookID);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    if ($stock > 0) {

        $stmt = $conn->prepare("INSERT INTO reservations (BookID, StudentID, ReserveDate) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $bookID, $studentID, $reserveDate);

        if ($stmt->execute()) {

            $stmt = $conn->prepare("UPDATE books SET Stock = Stock - 1 WHERE BookID = ?");
            $stmt->bind_param("i", $bookID);
            $stmt->execute();


            $mqtt = new MqttClient('broker.hivemq.com', 1883, uniqid());

            $settings = (new ConnectionSettings)->setUsername(null)->setPassword(null);
            // var_dump($settings);
            $mqtt->connect($settings, true);

            $notification = [
                'type' => 'reserve',
                'message' => 'A book has been reserved!',
                'book_id' => $bookID,
                'name' => $name,
                'student_id' => $studentID,
                'title' => $_POST['book_title'],
                'author' => $_POST['book_author']
            ];

            // var_dump($notification);


            $mqtt->publish('library/admin/notifications', json_encode($notification), MqttClient::QOS_AT_MOST_ONCE);
            $mqtt->disconnect();
            file_put_contents('mqtt_log.txt', json_encode($notification) . PHP_EOL, FILE_APPEND);


            $_SESSION['success'] = "Reservation successful!";
        } else {
            $_SESSION['error'] = "Failed to reserve the book.";
        }
    } else {
        $_SESSION['error'] = "Book is out of stock.";
    }

    header("Location:   index.php");
    exit;
}
