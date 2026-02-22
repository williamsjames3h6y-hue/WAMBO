<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Populating 35 tasks for VIP 1 users...\n\n";

// First, clear existing tasks
try {
    $db->exec("DELETE FROM user_task_submissions");
    echo "Cleared existing task submissions.\n";

    $db->exec("DELETE FROM admin_tasks");
    echo "Cleared existing tasks.\n\n";
} catch (PDOException $e) {
    echo "Error clearing tasks: " . $e->getMessage() . "\n";
    exit(1);
}

// Define 35 tasks with product images
$tasks = [
    // First 5 - Premium products
    ['order' => 1, 'brand' => 'Nike', 'product' => 'Air Max Sneakers', 'price' => 159.99, 'img' => '/public/products/P1.jpg', 'earn' => 2.25],
    ['order' => 2, 'brand' => 'Apple', 'product' => 'iPhone 15 Pro', 'price' => 999.99, 'img' => '/public/products/P2.jpg', 'earn' => 2.10],
    ['order' => 3, 'brand' => 'Samsung', 'product' => 'Galaxy S24 Ultra', 'price' => 1199.99, 'img' => '/public/products/P3.jpg', 'earn' => 2.30],
    ['order' => 4, 'brand' => 'Adidas', 'product' => 'Ultraboost Running Shoes', 'price' => 189.99, 'img' => '/public/products/P4.jpg', 'earn' => 1.95],
    ['order' => 5, 'brand' => 'Sony', 'product' => 'PlayStation 5', 'price' => 499.99, 'img' => '/public/products/P5.jpg', 'earn' => 2.40],

    // Tasks 6-37 - Using available product images P6-P37
    ['order' => 6, 'brand' => 'Microsoft', 'product' => 'Surface Laptop', 'price' => 1299.99, 'img' => '/public/products/P6.jpg', 'earn' => 2.15],
    ['order' => 7, 'brand' => 'Dell', 'product' => 'XPS 15 Laptop', 'price' => 1499.99, 'img' => '/public/products/P7.jpg', 'earn' => 2.05],
    ['order' => 8, 'brand' => 'HP', 'product' => 'Spectre x360', 'price' => 1399.99, 'img' => '/public/products/P8.jpg', 'earn' => 2.35],
    ['order' => 9, 'brand' => 'Lenovo', 'product' => 'ThinkPad X1 Carbon', 'price' => 1599.99, 'img' => '/public/products/P9.jpg', 'earn' => 2.20],
    ['order' => 10, 'brand' => 'Asus', 'product' => 'ROG Gaming Laptop', 'price' => 1799.99, 'img' => '/public/products/p10.jpg', 'earn' => 2.00],

    ['order' => 11, 'brand' => 'Canon', 'product' => 'EOS R5 Camera', 'price' => 3899.99, 'img' => '/public/products/p11.jpg', 'earn' => 2.25],
    ['order' => 12, 'brand' => 'Nikon', 'product' => 'Z9 Mirrorless Camera', 'price' => 5499.99, 'img' => '/public/products/p12.jpg', 'earn' => 2.10],
    ['order' => 13, 'brand' => 'GoPro', 'product' => 'Hero 12 Black', 'price' => 399.99, 'img' => '/public/products/p13.jpg', 'earn' => 2.30],
    ['order' => 14, 'brand' => 'DJI', 'product' => 'Mavic 3 Pro Drone', 'price' => 2199.99, 'img' => '/public/products/p14.jpg', 'earn' => 1.85],
    ['order' => 15, 'brand' => 'Bose', 'product' => 'QuietComfort Headphones', 'price' => 349.99, 'img' => '/public/products/p15.jpg', 'earn' => 2.45],

    ['order' => 16, 'brand' => 'LG', 'product' => 'OLED TV 65"', 'price' => 1999.99, 'img' => '/public/products/p16.jpg', 'earn' => 2.15],
    ['order' => 17, 'brand' => 'Amazon', 'product' => 'Echo Studio', 'price' => 199.99, 'img' => '/public/products/p17.jpg', 'earn' => 2.05],
    ['order' => 18, 'brand' => 'Google', 'product' => 'Pixel 8 Pro', 'price' => 999.99, 'img' => '/public/products/p18.jpg', 'earn' => 2.30],
    ['order' => 19, 'brand' => 'OnePlus', 'product' => '12 Pro Smartphone', 'price' => 899.99, 'img' => '/public/products/p19.jpg', 'earn' => 2.20],
    ['order' => 20, 'brand' => 'Xiaomi', 'product' => '14 Ultra', 'price' => 1299.99, 'img' => '/public/products/p20.jpg', 'earn' => 2.00],

    ['order' => 21, 'brand' => 'Dyson', 'product' => 'V15 Detect Vacuum', 'price' => 649.99, 'img' => '/public/products/p21.jpg', 'earn' => 2.25],
    ['order' => 22, 'brand' => 'iRobot', 'product' => 'Roomba j7+', 'price' => 799.99, 'img' => '/public/products/p22.jpg', 'earn' => 2.10],
    ['order' => 23, 'brand' => 'Nest', 'product' => 'Learning Thermostat', 'price' => 249.99, 'img' => '/public/products/p23.jpg', 'earn' => 2.35],
    ['order' => 24, 'brand' => 'Ring', 'product' => 'Video Doorbell Pro 2', 'price' => 279.99, 'img' => '/public/products/p24.jpg', 'earn' => 1.90],
    ['order' => 25, 'brand' => 'Fitbit', 'product' => 'Sense 2 Smartwatch', 'price' => 299.99, 'img' => '/public/products/p25.jpg', 'earn' => 2.40],

    ['order' => 26, 'brand' => 'Garmin', 'product' => 'Fenix 7X Solar', 'price' => 899.99, 'img' => '/public/products/p26.jpg', 'earn' => 2.15],
    ['order' => 27, 'brand' => 'Rolex', 'product' => 'Submariner Watch', 'price' => 12999.99, 'img' => '/public/products/p27.jpg', 'earn' => 2.05],
    ['order' => 28, 'brand' => 'Omega', 'product' => 'Seamaster Watch', 'price' => 5999.99, 'img' => '/public/products/p28.jpg', 'earn' => 2.30],
    ['order' => 29, 'brand' => 'TAG Heuer', 'product' => 'Carrera Watch', 'price' => 4999.99, 'img' => '/public/products/p29.jpg', 'earn' => 2.20],
    ['order' => 30, 'brand' => 'Breitling', 'product' => 'Navitimer Watch', 'price' => 7999.99, 'img' => '/public/products/p30.jpg', 'earn' => 2.00],

    ['order' => 31, 'brand' => 'Tesla', 'product' => 'Model 3 Accessories', 'price' => 499.99, 'img' => '/public/products/p31.jpg', 'earn' => 2.25],
    ['order' => 32, 'brand' => 'BMW', 'product' => 'M Performance Parts', 'price' => 1999.99, 'img' => '/public/products/p32.jpg', 'earn' => 2.10],
    ['order' => 33, 'brand' => 'Mercedes', 'product' => 'AMG Accessories', 'price' => 2499.99, 'img' => '/public/products/p33.jpg', 'earn' => 2.30],
    ['order' => 34, 'brand' => 'Audi', 'product' => 'Sport Package', 'price' => 1799.99, 'img' => '/public/products/p34.jpg', 'earn' => 2.15],
    ['order' => 35, 'brand' => 'Porsche', 'product' => 'Carrera Accessories', 'price' => 3999.99, 'img' => '/public/products/p35.jpg', 'earn' => 2.05],
];

// Insert all 35 tasks
$insertStmt = $db->prepare("
    INSERT INTO admin_tasks
    (id, task_order, vip_level_required, image_url, brand_name, product_name, price, earning_amount)
    VALUES
    (:id, :task_order, :vip_level, :image_url, :brand_name, :product_name, :price, :earning_amount)
");

$successCount = 0;
$errorCount = 0;

foreach ($tasks as $task) {
    try {
        $taskId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $insertStmt->execute([
            ':id' => $taskId,
            ':task_order' => $task['order'],
            ':vip_level' => 1,
            ':image_url' => $task['img'],
            ':brand_name' => $task['brand'],
            ':product_name' => $task['product'],
            ':price' => $task['price'],
            ':earning_amount' => $task['earn']
        ]);

        $successCount++;
        echo "✓ Task {$task['order']}: {$task['brand']} - {$task['product']}\n";
    } catch (PDOException $e) {
        $errorCount++;
        echo "✗ Task {$task['order']}: Error - {$e->getMessage()}\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Summary:\n";
echo "Successfully created: $successCount tasks\n";
echo "Errors: $errorCount\n";
echo "\n";

// Verify count
$stmt = $db->query("SELECT COUNT(*) as total FROM admin_tasks");
$total = $stmt->fetch()['total'];
echo "Total tasks in database: $total\n";

if ($total == 35) {
    echo "✓ SUCCESS! All 35 tasks have been created.\n";
} else {
    echo "✗ WARNING! Expected 35 tasks but found $total.\n";
}

echo "\n";
echo "Run verify_tasks.php to see all tasks.\n";
?>
