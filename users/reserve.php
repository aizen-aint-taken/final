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

    // Check how many books the user currently has borrowed
    $borrowedStmt = $conn->prepare("SELECT COUNT(*) as borrowed_count FROM reservations WHERE StudentID = ? AND STATUS = 'Borrowed'");
    $borrowedStmt->bind_param("i", $studentID);
    $borrowedStmt->execute();
    $borrowedResult = $borrowedStmt->get_result();
    $borrowedCount = $borrowedResult->fetch_assoc()['borrowed_count'];
    $borrowedStmt->close();

    // Limit to 8 borrowed books per user
    if ($borrowedCount >= 8) {
        $_SESSION['error'] = "You have reached the maximum limit of 8 borrowed books. Please return some books before borrowing more.";
        header("Location: index.php");
        exit;
    }

    // Check stock using stock_update if available, otherwise fallback to Stock
    $stmt = $conn->prepare("SELECT COALESCE(stock_update, Stock) as available_stock FROM books WHERE BookID = ?");
    $stmt->bind_param("i", $bookID);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    if ($stock > 0) {

        $stmt = $conn->prepare("INSERT INTO reservations (BookID, StudentID, ReserveDate, STATUS) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("iis", $bookID, $studentID, $reserveDate);

        if ($stmt->execute()) {

            // Do NOT decrement stock here - only reserve the book, admin will approve later

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

            // Save notification to database
            $stmt = $conn->prepare("INSERT INTO notifications (title, author, name) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $_POST['book_title'], $_POST['book_author'], $name);
            $stmt->execute();

            $_SESSION['success'] = "Reservation successful! Please wait for admin approval.";
        } else {
            $_SESSION['error'] = "Failed to reserve the book.";
        }
    } else {
        $_SESSION['error'] = "Book is out of stock.";
    }

    header("Location: index.php");
    exit;
}
