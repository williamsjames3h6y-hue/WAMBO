<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Product Images</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0af; }
    </style>
</head>
<body>
<h2>Updating Admin Tasks with Product Images</h2>
<pre>
<?php
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "<span class='error'>✗ Database connection failed!</span>\n";
    exit;
}

echo "<span class='success'>✓ Database connected</span>\n\n";

// Check current tasks
echo "<span class='info'>Current tasks in database:</span>\n";
$stmt = $db->query("SELECT task_order, brand_name, image_url FROM admin_tasks ORDER BY task_order LIMIT 10");
$currentTasks = $stmt->fetchAll();

foreach ($currentTasks as $task) {
    echo "  Task {$task['task_order']}: {$task['brand_name']} - {$task['image_url']}\n";
}

echo "\n<span class='info'>Updating tasks...</span>\n";

// Update tasks with product images
$updates = [
    1 => ['brand' => 'Nike', 'image' => '/public/products/P1.jpg'],
    2 => ['brand' => 'Apple', 'image' => '/public/products/P2.jpg'],
    3 => ['brand' => 'Samsung', 'image' => '/public/products/P3.jpg'],
    4 => ['brand' => 'Adidas', 'image' => '/public/products/P4.jpg'],
    5 => ['brand' => 'Sony', 'image' => '/public/products/P5.jpg']
];

foreach ($updates as $order => $data) {
    $stmt = $db->prepare("UPDATE admin_tasks SET image_url = :image, brand_name = :brand WHERE task_order = :order");
    $result = $stmt->execute([
        ':image' => $data['image'],
        ':brand' => $data['brand'],
        ':order' => $order
    ]);

    if ($result) {
        echo "<span class='success'>✓ Updated task {$order}: {$data['brand']} -> {$data['image']}</span>\n";
    } else {
        echo "<span class='error'>✗ Failed to update task {$order}</span>\n";
    }
}

echo "\n<span class='info'>Verifying updates...</span>\n";
$stmt = $db->query("SELECT task_order, brand_name, image_url FROM admin_tasks WHERE task_order <= 5 ORDER BY task_order");
$updatedTasks = $stmt->fetchAll();

foreach ($updatedTasks as $task) {
    echo "  Task {$task['task_order']}: {$task['brand_name']} - {$task['image_url']}\n";
}

echo "\n<span class='success'>✓ Migration completed successfully!</span>\n";
echo "\n<a href='/admin?tab=tasks' style='color: #0af;'>→ Go to Admin Panel</a>\n";
?>
</pre>
</body>
</html>
