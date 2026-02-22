<?php
require_once __DIR__ . '/../config/config.php';

$database = new Database();
$db = $database->getConnection();

try {
    echo "Starting migration: Add withdrawal password and phone fields...\n";

    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM user_profiles LIKE 'phone'");
    $phoneExists = $stmt->rowCount() > 0;

    $stmt = $db->query("SHOW COLUMNS FROM user_profiles LIKE 'withdrawal_password_hash'");
    $withdrawalPasswordExists = $stmt->rowCount() > 0;

    $stmt = $db->query("SHOW COLUMNS FROM user_profiles LIKE 'is_active'");
    $isActiveExists = $stmt->rowCount() > 0;

    // Add phone column if it doesn't exist
    if (!$phoneExists) {
        $db->exec("ALTER TABLE user_profiles ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER full_name");
        echo "✓ Added 'phone' column to user_profiles table\n";
    } else {
        echo "- 'phone' column already exists\n";
    }

    // Add withdrawal_password_hash column if it doesn't exist
    if (!$withdrawalPasswordExists) {
        $db->exec("ALTER TABLE user_profiles ADD COLUMN withdrawal_password_hash VARCHAR(255) DEFAULT NULL AFTER phone");
        echo "✓ Added 'withdrawal_password_hash' column to user_profiles table\n";
    } else {
        echo "- 'withdrawal_password_hash' column already exists\n";
    }

    // Add is_active column if it doesn't exist
    if (!$isActiveExists) {
        $db->exec("ALTER TABLE user_profiles ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER annotations_this_month");
        echo "✓ Added 'is_active' column to user_profiles table\n";
    } else {
        echo "- 'is_active' column already exists\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
