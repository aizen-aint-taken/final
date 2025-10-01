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


<div class="content-wrapper" style="padding-top: 40px;">
    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Tab Content -->
            <div class="tab-content mt-3" id="dashboard-tabContent">
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

<!-- MQTT and additional scripts -->
<script>
    // Wait for AdminLTE to fully load before initializing MQTT
    document.addEventListener('DOMContentLoaded', function() {
        // Small delay to ensure AdminLTE is fully initialized
        setTimeout(function() {
            initializeMQTT();
        }, 100);
    });

    function initializeMQTT() {
        // MQTT Client Configuration
        const client = mqtt.connect('wss://broker.hivemq.com:8884/mqtt', {
            reconnectPeriod: 5000,
            clean: true,
            clientId: 'libraryAdmin_' + Math.random().toString(16).substr(2, 8),
            username: '',
            password: '',
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
                    const notificationElement = document.getElementById('notification-count');
                    if (notificationElement) {
                        notificationElement.textContent = notificationCount;
                    }

                    const notificationsContainer = document.getElementById('notifications');
                    if (notificationsContainer) {
                        const notification = `
                            <div class="notification-item">
                                <strong>New Reservation:</strong><br> Book Reserved: <strong> ${data.title}</strong> by ${data.author}<br>
                                <small>Reserved by: ${data.name}</small>
                            </div>`;
                        notificationsContainer.insertAdjacentHTML('afterbegin', notification);
                    }
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

        window.addEventListener('beforeunload', () => {
            client.end();
        });
    }
</script>

<!-- Load MQTT library after everything else -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mqtt/4.3.7/mqtt.min.js"></script>
<script src="../public/assets/js/Books.js"></script>