<?php
require_once __DIR__ . '/config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get all tasks
$stmt = $db->query("SELECT * FROM admin_tasks ORDER BY task_order ASC");
$tasks = $stmt->fetchAll();

echo "Current Tasks in Database:\n";
echo "==========================\n\n";

foreach ($tasks as $task) {
    echo "ID: " . $task['id'] . "\n";
    echo "Brand: " . $task['brand_name'] . "\n";
    echo "Amount: $" . $task['earning_amount'] . "\n";
    echo "Image: " . $task['image_url'] . "\n";
    echo "Order: " . $task['task_order'] . "\n";
    echo "VIP Level: " . $task['vip_level_required'] . "\n";
    echo "---\n";
}

echo "\nTotal tasks: " . count($tasks) . "\n";

// Check product images
echo "\nChecking product images:\n";
$productImages = [
    '/public/products/P1.jpg',
    '/public/products/P2.jpg',
    '/public/products/P3.jpg',
    '/public/products/P4.jpg',
    '/public/products/P5.jpg'
];

foreach ($productImages as $img) {
    $exists = file_exists(__DIR__ . $img) ? "EXISTS" : "MISSING";
    echo $img . " - " . $exists . "\n";
}
