<?php
include("../includes/header.php");
include("../includes/sidebar.php");
include("../config/conn.php");

date_default_timezone_set('Asia/Manila');
?>

<style>
    .main-content {
        background: #f8f9fa;
        min-height: 100vh;
        margin-left: 250px;
        padding: 40px 20px;
        transition: margin-left 0.3s ease;
    }

    /* When sidebar is collapsed or on mobile */
    @media (max-width: 991.98px) {
        .main-content {
            margin-left: 0;
            padding: 20px 10px;
        }
    }

    .card {
        border-radius: 10px;
    }

    .list-group-item {
        border: none;
        border-bottom: 1px solid #eee;
        padding: 15px 10px;
        flex-wrap: wrap;
    }

    .list-group-item:last-child {
        border-bottom: none;
    }

    /* Responsive text wrapping */
    .notification-info {
        flex: 1 1 auto;
        min-width: 200px;
        word-wrap: break-word;
    }

    .notification-time {
        white-space: nowrap;
        font-size: 0.9rem;
    }

    @media (max-width: 576px) {
        .notification-time {
            width: 100%;
            text-align: left;
            margin-top: 8px;
        }
    }

    /* Smaller screens text */
    @media (max-width: 400px) {
        .card-body h2 {
            font-size: 1.2rem;
        }

        .list-group-item {
            padding: 12px 8px;
        }
    }
</style>

<div class="main-content">
    <div class="container-fluid px-2 px-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="mb-4 d-flex align-items-center">
                    <i class="fa fa-bell me-2 text-center"></i> Recent Book Reservations
                </h2>

                <?php
                $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20");
                if ($result->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="notification-info">
                                    <strong>Book:</strong> <span class="text-primary"><?= htmlspecialchars($row['title']) ?></span><br>
                                    <strong>Author:</strong> <?= htmlspecialchars($row['author']) ?><br>
                                    <strong>Reserved by:</strong> <span class="text-success"><?= htmlspecialchars($row['name']) ?></span>
                                </div>
                                <small class="text-muted notification-time">
                                    <?= date('M d, Y H:i', strtotime($row['created_at'])) ?>
                                </small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No notifications yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>