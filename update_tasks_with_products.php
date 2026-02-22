<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    die("You must be logged in");
}

$auth = new Auth();
$userId = getCurrentUserId();
$isAdmin = $auth->isAdmin($userId);

if (!$isAdmin) {
    die("You must be an admin");
}

$database = new Database();
$db = $database->getConnection();

// Define product tasks with the correct product images
$productTasks = [
    [
        'brand_name' => 'Nike',
        'earning_amount' => 2.25,
        'image_url' => '/public/products/P1.jpg',
        'task_order' => 1,
        'vip_level_required' => 1
    ],
    [
        'brand_name' => 'Apple',
        'earning_amount' => 2.30,
        'image_url' => '/public/products/P2.jpg',
        'task_order' => 2,
        'vip_level_required' => 1
    ],
    [
        'brand_name' => 'Samsung',
        'earning_amount' => 2.20,
        'image_url' => '/public/products/P3.jpg',
        'task_order' => 3,
        'vip_level_required' => 1
    ],
    [
        'brand_name' => 'Adidas',
        'earning_amount' => 2.15,
        'image_url' => '/public/products/P4.jpg',
        'task_order' => 4,
        'vip_level_required' => 1
    ],
    [
        'brand_name' => 'Sony',
        'earning_amount' => 2.35,
        'image_url' => '/public/products/P5.jpg',
        'task_order' => 5,
        'vip_level_required' => 1
    ]
];

// First, delete all existing tasks
$stmt = $db->prepare("DELETE FROM admin_tasks");
$stmt->execute();

echo "Deleted all existing tasks.\n";

// Now insert the new product tasks
foreach ($productTasks as $task) {
    $taskId = generateUUID();
    $stmt = $db->prepare("INSERT INTO admin_tasks (id, brand_name, earning_amount, image_url, task_order, vip_level_required) VALUES (:id, :brand_name, :earning_amount, :image_url, :task_order, :vip_level_required)");
    $stmt->execute([
        ':id' => $taskId,
        ':brand_name' => $task['brand_name'],
        ':earning_amount' => $task['earning_amount'],
        ':image_url' => $task['image_url'],
        ':task_order' => $task['task_order'],
        ':vip_level_required' => $task['vip_level_required']
    ]);

    echo "Added task: " . $task['brand_name'] . " with image " . $task['image_url'] . "\n";
}

echo "\nAll tasks updated successfully!\n";
echo "<br><br><a href='/admin?tab=tasks'>Back to Admin Panel</a>";
?>
