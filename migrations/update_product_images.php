<?php
require_once __DIR__ . '/../config/config.php';

$database = new Database();
$db = $database->getConnection();

echo "Updating admin tasks with product images...\n\n";

// Update existing tasks with product images
$updates = [
    ['old_image' => '/1.jpg', 'new_image' => '/public/products/P1.jpg', 'brand_name' => 'Nike'],
    ['old_image' => '/2.jpg', 'new_image' => '/public/products/P2.jpg', 'brand_name' => 'Apple'],
    ['old_image' => '/3.jpg', 'new_image' => '/public/products/P3.jpg', 'brand_name' => 'Samsung'],
    ['old_image' => '/4.jpg', 'new_image' => '/public/products/P4.jpg', 'brand_name' => 'Adidas'],
    ['old_image' => '/5.jpg', 'new_image' => '/public/products/P5.jpg', 'brand_name' => 'Sony']
];

foreach ($updates as $update) {
    // Update by order since the old images are /1.jpg, /2.jpg etc
    $order = (int)str_replace(['/','jpg','.'], '', $update['old_image']);

    $stmt = $db->prepare("UPDATE admin_tasks SET image_url = :new_image, brand_name = :brand_name WHERE task_order = :task_order");
    $result = $stmt->execute([
        ':new_image' => $update['new_image'],
        ':brand_name' => $update['brand_name'],
        ':task_order' => $order
    ]);

    if ($result) {
        echo "✓ Updated task {$order}: {$update['brand_name']} -> {$update['new_image']}\n";
    } else {
        echo "✗ Failed to update task {$order}\n";
    }
}

echo "\n\nVerifying updates...\n";
$stmt = $db->query("SELECT task_order, brand_name, image_url FROM admin_tasks WHERE task_order <= 5 ORDER BY task_order");
$tasks = $stmt->fetchAll();

foreach ($tasks as $task) {
    echo "Task {$task['task_order']}: {$task['brand_name']} - {$task['image_url']}\n";
}

echo "\n✓ Migration completed!\n";
?>
