<?php
require_once __DIR__ . '/config/config.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Update VIP Task Limits - EarningsLLC</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 flex items-center justify-center p-4'>
    <div class='max-w-4xl w-full bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700'>
        <h1 class='text-3xl font-bold text-white mb-6'>🎖️ Update VIP Task Limits</h1>
        <div class='space-y-4'>";

try {
    $db->beginTransaction();

    $vipLimits = [
        1 => ['name' => 'VIP 1 - Bronze', 'tasks' => 35],
        2 => ['name' => 'VIP 2 - Silver', 'tasks' => 45],
        3 => ['name' => 'VIP 3 - Gold', 'tasks' => 55],
        4 => ['name' => 'VIP 4 - Diamond', 'tasks' => 70],
        5 => ['name' => 'VIP 5 - Crown', 'tasks' => 999999]
    ];

    echo "<div class='bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 text-blue-300'>";
    echo "<p>Updating VIP tier task limits...</p>";
    echo "</div>";

    $checkColumn = $db->query("SHOW COLUMNS FROM vip_tiers LIKE 'daily_task_limit'");
    if ($checkColumn->rowCount() == 0) {
        $db->exec("ALTER TABLE vip_tiers ADD COLUMN daily_task_limit INT DEFAULT 35");
        echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
        echo "<p>✓ Added daily_task_limit column to vip_tiers table</p>";
        echo "</div>";
    }

    foreach ($vipLimits as $level => $data) {
        $checkTier = $db->prepare("SELECT id FROM vip_tiers WHERE level = :level");
        $checkTier->execute([':level' => $level]);

        if ($checkTier->rowCount() > 0) {
            $updateStmt = $db->prepare("
                UPDATE vip_tiers
                SET daily_task_limit = :limit,
                    max_tasks_per_day = :limit,
                    name = :name
                WHERE level = :level
            ");
            $updateStmt->execute([
                ':limit' => $data['tasks'],
                ':name' => $data['name'],
                ':level' => $level
            ]);

            echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
            echo "<p>✓ Updated " . htmlspecialchars($data['name']) . " - Daily Task Limit: " . $data['tasks'] . ($data['tasks'] > 1000 ? ' (Unlimited)' : '') . "</p>";
            echo "</div>";
        } else {
            $insertStmt = $db->prepare("
                INSERT INTO vip_tiers (id, name, level, price_monthly, daily_task_limit, max_tasks_per_day, commission_rate)
                VALUES (UUID(), :name, :level, 0, :limit, :limit, 0.5)
            ");
            $insertStmt->execute([
                ':name' => $data['name'],
                ':level' => $level,
                ':limit' => $data['tasks']
            ]);

            echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
            echo "<p>✓ Created " . htmlspecialchars($data['name']) . " - Daily Task Limit: " . $data['tasks'] . ($data['tasks'] > 1000 ? ' (Unlimited)' : '') . "</p>";
            echo "</div>";
        }
    }

    $db->commit();

    echo "<div class='mt-6 bg-green-500/20 border border-green-500 rounded-lg p-6 text-green-300'>";
    echo "<p class='text-xl font-bold mb-2'>✓ VIP task limits updated successfully!</p>";
    echo "<ul class='mt-4 space-y-2'>";
    echo "<li>• VIP 1: 35 tasks per day</li>";
    echo "<li>• VIP 2: 45 tasks per day</li>";
    echo "<li>• VIP 3: 55 tasks per day</li>";
    echo "<li>• VIP 4: 70 tasks per day</li>";
    echo "<li>• VIP 5: Unlimited tasks</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div class='mt-6 flex gap-4'>";
    echo "<a href='/dashboard' class='flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all text-center'>Go to Dashboard</a>";
    echo "<a href='/admin' class='flex-1 bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-all text-center'>Admin Panel</a>";
    echo "</div>";

} catch (Exception $e) {
    $db->rollBack();
    echo "<div class='bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300'>";
    echo "<p class='font-bold'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "    </div>
    </div>
</body>
</html>";
?>
