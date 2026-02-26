<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!");
}

try {
    // Check if daily_task_limit column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM vip_tiers LIKE 'daily_task_limit'");

    if ($checkColumn->rowCount() == 0) {
        // Add the column
        $db->exec("ALTER TABLE vip_tiers ADD COLUMN daily_task_limit INT DEFAULT 35 AFTER max_tasks_per_day");
        echo "✅ Added daily_task_limit column to vip_tiers table\n";

        // Update existing records with correct values
        $updates = [
            1 => 35,
            2 => 45,
            3 => 55,
            4 => 70,
            5 => 999999
        ];

        foreach ($updates as $level => $limit) {
            $stmt = $db->prepare("UPDATE vip_tiers SET daily_task_limit = :limit WHERE level = :level");
            $stmt->execute([':limit' => $limit, ':level' => $level]);
        }

        echo "✅ Updated VIP tier limits\n";
    } else {
        echo "✅ Column already exists\n";
    }

    echo "\nDone! You can now refresh your dashboard.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
