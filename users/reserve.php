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


            $server   = 'broker.hivemq.com';
            $port     = 1883;
            $clientId = 'phpMQTT-publisher-' . rand(1, 10000);

            try {
                $mqtt = new MqttClient($server, $port, $clientId);

                $connectionSettings = (new ConnectionSettings)
                    ->setConnectTimeout(5)
                    ->setUseTls(false)
                    ->setTlsSelfSignedAllowed(true);

                $mqtt->connect($connectionSettings, true);

                // Your message data
                $message = json_encode([
                    'title' => $_POST['book_title'],
                    'author' => $_POST['book_author'],
                    'name' => $name
                ]);


                $mqtt->publish('library/admin/notifications', $message, 0);
                $mqtt->disconnect();
            } catch (Exception $e) {

                error_log("MQTT Error: " . $e->getMessage());
            }

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
