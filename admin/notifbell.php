<?php
include("../includes/header.php");
include("../includes/sidebar.php");
include("../config/conn.php");
?>

<style>
    .main-content {
        background: #f8f9fa;
        min-height: 100vh;
        margin-left: 250px;
        padding: 40px 20px;
    }

    .card {
        border-radius: 10px;
    }

    .list-group-item {
        border: none;
        border-bottom: 1px solid #eee;
        padding: 20px 10px;
    }

    .list-group-item:last-child {
        border-bottom: none;
    }
</style>

<div class="main-content">
    <div class="container">
        <h2 class="mb-4"><i class="fa fa-bell"></i> Recent Book Reservations</h2>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php
                $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20");
                if ($result->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Book:</strong> <span class="text-primary"><?= htmlspecialchars($row['title']) ?></span><br>
                                        <strong>Author:</strong> <?= htmlspecialchars($row['author']) ?><br>
                                        <strong>Reserved by:</strong> <span class="text-success"><?= htmlspecialchars($row['name']) ?></span>
                                    </div>
                                    <small class="text-muted"><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></small>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info">No notifications yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>