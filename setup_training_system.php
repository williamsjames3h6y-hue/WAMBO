<?php
require_once __DIR__ . '/config/database.php';

echo "Setting up training system...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Read and execute the SQL migration
    $sql = file_get_contents(__DIR__ . '/database/add_training_system.sql');

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        try {
            $db->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }

    echo "\n✓ Training system setup completed!\n\n";
    echo "Summary:\n";
    echo "- Added training_completed column to users table\n";
    echo "- Added training_account_id for linking accounts\n";
    echo "- Created indexes for better query performance\n";
    echo "- Created training_progress view for tracking\n\n";

    // Show current training accounts
    $query = "SELECT email, full_name, training_completed FROM users u
              LEFT JOIN user_profiles up ON up.id = u.id
              WHERE email LIKE '%@training.com'
              ORDER BY u.created_at DESC
              LIMIT 10";

    $stmt = $db->query($query);
    $trainingAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Recent training accounts:\n";
    if (empty($trainingAccounts)) {
        echo "  No training accounts found\n";
    } else {
        foreach ($trainingAccounts as $account) {
            $status = $account['training_completed'] ? '✓ Completed' : '○ In Progress';
            echo "  $status - {$account['email']} ({$account['full_name']})\n";
        }
    }

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
