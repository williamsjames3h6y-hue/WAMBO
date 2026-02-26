<?php
require_once __DIR__ . '/config/config.php';

echo "=== Update Existing Accounts to Personal (training_completed = 1) ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if training_completed column exists
    echo "Checking if training_completed column exists...\n";
    $checkCol = $db->query("SHOW COLUMNS FROM users LIKE 'training_completed'");
    $hasTrainingColumn = $checkCol->rowCount() > 0;

    if (!$hasTrainingColumn) {
        echo "Creating training_completed column...\n";
        $db->exec("ALTER TABLE users ADD COLUMN training_completed TINYINT(1) DEFAULT 0 AFTER email_confirmed");
        echo "✅ Column created successfully!\n\n";
    } else {
        echo "✅ Column already exists!\n\n";
    }

    // Get count of all users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    echo "Total users in database: {$totalUsers}\n\n";

    // Update all existing users to have training_completed = 1 (personal accounts)
    echo "Updating all existing accounts to personal (training_completed = 1)...\n";
    $stmt = $db->exec("UPDATE users SET training_completed = 1 WHERE training_completed = 0 OR training_completed IS NULL");
    echo "✅ Updated {$stmt} accounts to personal status!\n\n";

    // Show summary
    echo "=== Summary ===\n";
    $stmt = $db->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN training_completed = 1 THEN 1 ELSE 0 END) as personal_accounts,
        SUM(CASE WHEN training_completed = 0 THEN 1 ELSE 0 END) as training_accounts
        FROM users");
    $summary = $stmt->fetch();

    echo "Total Accounts: {$summary['total']}\n";
    echo "Personal Accounts (training_completed = 1): {$summary['personal_accounts']}\n";
    echo "Training Accounts (training_completed = 0): {$summary['training_accounts']}\n\n";

    echo "✅ All done!\n\n";
    echo "IMPORTANT:\n";
    echo "- All existing accounts are now marked as PERSONAL accounts\n";
    echo "- The TRAINING badge will NOT show for these accounts\n";
    echo "- To create new TRAINING accounts, use the Admin Panel > Users > Create Training Account button\n";
    echo "- New registrations will automatically be personal accounts\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
