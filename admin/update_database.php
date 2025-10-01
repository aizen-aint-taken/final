<?php
// Database update script to add created_date column for import tracking
session_start();

// Check authentication
if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['a', 'sa'])) {
    header('Location: ../index.php');
    exit;
}

include('../config/conn.php');

// Check if created_date column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM books LIKE 'created_date'");

if ($checkColumn->num_rows == 0) {
    // Add created_date column
    $addColumn = $conn->query("ALTER TABLE books ADD COLUMN created_date DATETIME DEFAULT NULL");

    if ($addColumn) {
        echo "✅ Successfully added created_date column to books table.<br>";

        // Update existing books with estimated import dates (optional)
        // This sets created_date to NULL for existing books, which will be treated as manual entries
        echo "📝 Existing books will be marked as manual entries (created_date = NULL).<br>";
        echo "🎯 New Excel imports will have proper timestamps.<br>";
    } else {
        echo "❌ Error adding created_date column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ created_date column already exists.<br>";
}

// Check if we need to create an inventory_transactions table for future use
$checkTransTable = $conn->query("SHOW TABLES LIKE 'inventory_transactions'");

if ($checkTransTable->num_rows == 0) {
    $createTransTable = "CREATE TABLE inventory_transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        BookID INT NOT NULL,
        transaction_type ENUM('import', 'export', 'adjustment', 'return', 'borrow') NOT NULL,
        quantity INT NOT NULL,
        notes TEXT,
        user_id INT,
        transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (BookID) REFERENCES books(BookID) ON DELETE CASCADE,
        INDEX idx_book_id (BookID),
        INDEX idx_transaction_date (transaction_date)
    )";

    if ($conn->query($createTransTable)) {
        echo "✅ Successfully created inventory_transactions table.<br>";
    } else {
        echo "❌ Error creating inventory_transactions table: " . $conn->error . "<br>";
    }
} else {
    echo "✅ inventory_transactions table already exists.<br>";
}

echo "<br><strong>Database update completed!</strong><br>";
echo "<a href='inventory.php'>← Back to Inventory</a>";
