<?php

session_start();

include('../config/conn.php');

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('location: ../index.php');
    exit;
}
// restrict access to admin only 
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'a') {
    header('location: ../index.php');
    //     exit;
}

$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Modify the query to filter by subject if one is selected
if ($subject) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE Genre = :subject");
    $stmt->execute(['subject' => $subject]);
} else {
    $stmt = $conn->query("SELECT * FROM books");
}

$books = $stmt->fetch_all();

include('../includes/header.php');
include('../includes/sidebar.php');
// include('../admin/displayStats.html');
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
    <link rel="stylesheet" href="../public/assets/css/admin_index.css">
    <title>Library Inventory</title>
</head>
<style>
    body {
        background: url('../maharlika/2nd pic.jpg') no-repeat center center fixed;
        background-size: cover;
        color: #fff;
    }

    .content-wrapper {
        background-color: rgba(0, 0, 0, 0.5);
        padding: 20px;
        border-radius: 10px;
    }

    /* Notification Bell Styles */
    .notification-bell {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .notification-bell .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 5px 8px;
        font-size: 12px;
    }

    .notification-dropdown {
        display: none;
        /* position: fixed; */
        top: 70px;
        right: 20px;
        background-color: white;
        color: black;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        width: 300px;
        max-height: 400px;
        overflow-y: auto;
        z-index: 1000;
        padding: 10px;
    }

    .notification-dropdown .notification-item {
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }

    .notification-dropdown .notification-item:last-child {
        border-bottom: none;
    }

    .notification-dropdown .notification-item strong {
        display: block;
        font-size: 14px;
    }

    .notification-dropdown .notification-item small {
        color: #666;
    }
</style>

<body>
    <div class="content-wrapper">
        <div class="container">
            <h2 class="text-center text-white">List of Library Collection</h2>
            <!-- Notification Bell -->
            <div class="notification-bell text-center" onclick="toggleNotifications()">
                <i class="fa fa-bell" style="font-size: 24px; color: white;"></i>
                <span class="badge" id="notification-count">0</span>
            </div>
            <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notification-dropdown">
                <div id="notifications"></div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs justify-content-center" id="dashboard-tabs" role="tablist">
                    <li class="nav-item">
                        <!-- <a class="nav-link active" id="literature-tab" data-bs-toggle="tab" href="#literature" role="tab" aria-controls="literature" aria-selected="true">
                            <i class="fas fa-book"></i> Books
                        </a> -->
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-5" id="dashboard-tabContent">
                    <!-- Literature Tab -->
                    <div class="tab-pane fade show active" id="literature" role="tabpanel" aria-labelledby="literature-tab">
                        <div class="card">
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
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
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
        const client = mqtt.connect('wss://test.mosquitto.org:8081/mqtt ', {
            reconnectPeriod: 5000,
            clean: true,
            clientId: 'libraryAdmin_' + Math.random().toString(16).substr(2, 8),
        });
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