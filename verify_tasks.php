<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Count total tasks
$stmt = $db->query("SELECT COUNT(*) as total FROM admin_tasks");
$total = $stmt->fetch()['total'];

echo "Total tasks in database: " . $total . "\n\n";

// Count by VIP level
$stmt = $db->query("SELECT vip_level_required, COUNT(*) as count FROM admin_tasks GROUP BY vip_level_required ORDER BY vip_level_required");
$levelCounts = $stmt->fetchAll();

echo "Tasks by VIP level:\n";
foreach ($levelCounts as $level) {
    echo "VIP Level " . $level['vip_level_required'] . ": " . $level['count'] . " tasks\n";
}

echo "\n";

// Show task order range
$stmt = $db->query("SELECT MIN(task_order) as min_order, MAX(task_order) as max_order FROM admin_tasks");
$orderRange = $stmt->fetch();

echo "Task order range: " . $orderRange['min_order'] . " to " . $orderRange['max_order'] . "\n\n";

// Check for missing task orders
echo "Checking for gaps in task_order sequence...\n";
$stmt = $db->query("SELECT task_order FROM admin_tasks ORDER BY task_order ASC");
$orders = $stmt->fetchAll(PDO::FETCH_COLUMN);

$expected = range(1, max($orders));
$missing = array_diff($expected, $orders);

if (empty($missing)) {
    echo "No gaps found - all task orders from 1 to " . max($orders) . " exist.\n";
} else {
    echo "Missing task orders: " . implode(", ", $missing) . "\n";
}

echo "\n";

// Show all task details
echo "All tasks:\n";
echo str_repeat("=", 80) . "\n";
$stmt = $db->query("SELECT id, task_order, brand_name, earning_amount, image_url FROM admin_tasks ORDER BY task_order ASC");
$tasks = $stmt->fetchAll();

foreach ($tasks as $task) {
    printf("#%d - %s - $%.2f - %s\n",
        $task['task_order'],
        $task['brand_name'],
        $task['earning_amount'],
        $task['image_url']
    );
}

echo "\n";
echo "If you see only 5 tasks but expected 35, the database needs to be reinitialized.\n";
echo "Run: php init_database.php\n";
?>
