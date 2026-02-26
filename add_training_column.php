<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        die("Database connection failed\n");
    }

    echo "Adding training system columns to users table...\n\n";

    // Add training_completed column
    $checkQuery = "SHOW COLUMNS FROM users LIKE 'training_completed'";
    $result = $db->query($checkQuery);

    if ($result->rowCount() == 0) {
        $alterQuery = "ALTER TABLE users ADD COLUMN training_completed BOOLEAN DEFAULT TRUE";
        $db->exec($alterQuery);
        echo "✓ Added training_completed column\n";
    } else {
        echo "✓ training_completed column already exists\n";
    }

    // Add training_account_id column
    $checkQuery2 = "SHOW COLUMNS FROM users LIKE 'training_account_id'";
    $result2 = $db->query($checkQuery2);

    if ($result2->rowCount() == 0) {
        $alterQuery2 = "ALTER TABLE users ADD COLUMN training_account_id CHAR(36) DEFAULT NULL";
        $db->exec($alterQuery2);
        echo "✓ Added training_account_id column\n";
    } else {
        echo "✓ training_account_id column already exists\n";
    }

    echo "\n✓ Database update completed successfully!\n\n";
    echo "Training system is now ready:\n";
    echo "- New users will get training accounts automatically\n";
    echo "- Training credentials will be sent to Telegram\n";
    echo "- After 15 tasks, users unlock the main dashboard\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
