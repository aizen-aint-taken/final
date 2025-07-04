<?php

session_start();


error_log("Session contents: " . print_r($_SESSION, true));


if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['a', 'sa'])) {
    header('Location: ../index.php');
    exit;
}


error_reporting(E_ALL);
ini_set('display_errors', 1);


include('../config/conn.php');

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    error_log("No user session found - redirecting to login");
    header('location: ../index.php');
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['a', 'sa'])) {
    error_log("Invalid role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));
    header('location: ../index.php');
    exit;
}




$subject = isset($_GET['subject']) ? $_GET['subject'] : '';


if ($subject) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE Genre = :subject");
    $stmt->execute(['subject' => $subject]);
} else {
    $stmt = $conn->query("SELECT * FROM books");
}

$books = $stmt->fetch_all();

include('../includes/header.php');
include('../includes/sidebar.php');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UI PAGE">
    <meta name="author" content="Ely Gian Ga">
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/assets/css/font-awesome.css">
    <!-- <link rel="stylesheet" href="../public/assets/css/admin_index.css"> -->
    <link rel="stylesheet" href="../public/assets/css/useradmin.css">
    <title>Library Inventory</title>
</head>
<style>
    .content-wrapper {
        background-color: rgba(234, 219, 205, 0.91);

    }

    .container {
        padding: 30px;
    }


    .hr {
        border: none;
        height: 2px;
        background-color: #333;
        margin: 20px 0;
    }
</style>

<body>
    <div class="content-wrapper">

        <!-- Main Content -->
        <section class="content">

            <div class="container-fluid">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs justify-content-center" id="dashboard-tabs" role="tablist">
                    <li class="nav-item">

                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-5" id="dashboard-tabContent">
                    <!-- Literature Tab -->
                    <div class="tab-pane fade show active" id="literature" role="tabpanel" aria-labelledby="literature-tab">
                        <div class="card">

                            <div class="container">
                                <h2 class="text-center text-black" style="font-size: 50px;">List of Library Collection</h2>
                            </div>
                            <hr class="hr">
                            <div class="card-body">
                                <?php
                                include("../categories/Books.php");
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php include('../includes/footer.php'); ?>

    <script src="../public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mqtt/4.3.7/mqtt.min.js"></script>
    <script>
        fetch('getSession.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw Response:', text);
                try {
                    return JSON.parse(text);
                } catch (error) {
                    throw new Error('Invalid JSON: ' + text);
                }
            })
            .then(data => console.log('Session Data:', data))
            .catch(error => console.error('Error fetching session data:', error));


        // client mqtt
        // wss://broker.hivemq.com:8884/mqtt
        // wss://broker.emqx.io:8084/mqtt
        const client = mqtt.connect('wss://broker.emqx.io:8084/mqtt', {
            reconnectPeriod: 5000,
            clean: true,
            clientId: 'libraryAdmin_' + Math.random().toString(16).substr(2, 8),
            username: '',
            password: '',
        })
        let notificationCount = 0;

        client.on('connect', () => {
            console.log('Connected to MQTT broker');
            client.subscribe('library/admin/notifications', {
                qos: 1
            }, (err) => {
                if (!err) {
                    console.log('Subscribed to library/admin/notifications');
                } else {
                    console.error('Subscription error:', err);
                }
            });
        });

        client.on('message', (topic, message) => {
            if (topic === 'library/admin/notifications') {
                try {
                    const data = JSON.parse(message.toString());
                    notificationCount++;
                    document.getElementById('notification-count').textContent = notificationCount;

                    const notification = `
                    <div class="notification-item">
                        <strong>New Reservation:</strong><br> Book Reserved: <strong> ${data.title}</strong> by ${data.author}<br>
                        <small>Reserved by: ${data.name}</small>
                    </div>`;
                    document.getElementById('notifications').insertAdjacentHTML('afterbegin', notification);

                    file_put_contents('mqtt_log.txt', json_encode($notification).PHP_EOL, FILE_APPEND);
                } catch (e) {
                    console.error('Error parsing message:', e);
                }
            }
        });

        client.on('error', (err) => {
            console.error('MQTT error:', err);
        });

        client.on('close', () => {
            console.log('MQTT connection closed');
        });

        client.on('offline', () => {
            console.log('MQTT client is offline');
        });

        client.on('reconnect', () => {
            console.log('Reconnecting to MQTT broker...');
        });


        function toggleNotifications() {
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            if (dropdown.style.display === 'block') {
                notificationCount = 0;
                document.getElementById('notification-count').textContent = notificationCount;
            }
        }


        document.addEventListener('click', (event) => {
            const dropdown = document.getElementById('notification-dropdown');
            const bell = document.querySelector('.notification-bell');
            if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });


        window.addEventListener('beforeunload', () => {
            client.end();
        });
    </script>
</body>

</html>