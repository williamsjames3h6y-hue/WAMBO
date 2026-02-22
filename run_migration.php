<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Only allow admin access
if (!isLoggedIn()) {
    die("Unauthorized: Please login first");
}

$auth = new Auth();
$userId = getCurrentUserId();
$isAdmin = $auth->isAdmin($userId);

if (!$isAdmin) {
    die("Forbidden: Admin access required");
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!");
}

try {
    echo "<h2>Starting migration: Add withdrawal password and phone fields...</h2>";

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
        echo "<p>✓ Added 'phone' column to user_profiles table</p>";
    } else {
        echo "<p>- 'phone' column already exists</p>";
    }

    // Add withdrawal_password_hash column if it doesn't exist
    if (!$withdrawalPasswordExists) {
        $db->exec("ALTER TABLE user_profiles ADD COLUMN withdrawal_password_hash VARCHAR(255) DEFAULT NULL AFTER phone");
        echo "<p>✓ Added 'withdrawal_password_hash' column to user_profiles table</p>";
    } else {
        echo "<p>- 'withdrawal_password_hash' column already exists</p>";
    }

    // Add is_active column if it doesn't exist
    if (!$isActiveExists) {
        $db->exec("ALTER TABLE user_profiles ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER annotations_this_month");
        echo "<p>✓ Added 'is_active' column to user_profiles table</p>";
    } else {
        echo "<p>- 'is_active' column already exists</p>";
    }

    echo "<h3 style='color: green;'>Migration completed successfully!</h3>";
    echo "<p><a href='/admin'>Return to Admin Panel</a></p>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Migration failed: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
?>
