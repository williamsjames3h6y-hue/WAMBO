<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Adding is_training_account column to users table...\n";

    // Check if column already exists
    $checkQuery = "SHOW COLUMNS FROM users LIKE 'is_training_account'";
    $result = $db->query($checkQuery);

    if ($result->rowCount() == 0) {
        // Add the column
        $alterQuery = "ALTER TABLE users ADD COLUMN is_training_account BOOLEAN DEFAULT FALSE";
        $db->exec($alterQuery);
        echo "✓ Column added successfully!\n";
    } else {
        echo "✓ Column already exists!\n";
    }

    echo "\nDatabase update completed!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
