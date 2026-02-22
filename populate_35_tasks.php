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

// Define 35 tasks with product codes and numbers
$tasks = [
    ['order' => 1, 'code' => 'PRD-001', 'product' => 'Product No.8901234567', 'price' => 159.99, 'img' => '/public/products/P1.jpg', 'earn' => 2.25],
    ['order' => 2, 'code' => 'PRD-002', 'product' => 'Product No.8902345678', 'price' => 999.99, 'img' => '/public/products/P2.jpg', 'earn' => 2.10],
    ['order' => 3, 'code' => 'PRD-003', 'product' => 'Product No.8903456789', 'price' => 1199.99, 'img' => '/public/products/P3.jpg', 'earn' => 2.30],
    ['order' => 4, 'code' => 'PRD-004', 'product' => 'Product No.8904567890', 'price' => 189.99, 'img' => '/public/products/P4.jpg', 'earn' => 1.95],
    ['order' => 5, 'code' => 'PRD-005', 'product' => 'Product No.8905678901', 'price' => 499.99, 'img' => '/public/products/P5.jpg', 'earn' => 2.40],

    ['order' => 6, 'code' => 'PRD-006', 'product' => 'Product No.8906789012', 'price' => 1299.99, 'img' => '/public/products/P6.jpg', 'earn' => 2.15],
    ['order' => 7, 'code' => 'PRD-007', 'product' => 'Product No.8907890123', 'price' => 1499.99, 'img' => '/public/products/P7.jpg', 'earn' => 2.05],
    ['order' => 8, 'code' => 'PRD-008', 'product' => 'Product No.8908901234', 'price' => 1399.99, 'img' => '/public/products/P8.jpg', 'earn' => 2.35],
    ['order' => 9, 'code' => 'PRD-009', 'product' => 'Product No.8909012345', 'price' => 1599.99, 'img' => '/public/products/P9.jpg', 'earn' => 2.20],
    ['order' => 10, 'code' => 'PRD-010', 'product' => 'Product No.8910123456', 'price' => 1799.99, 'img' => '/public/products/p10.jpg', 'earn' => 2.00],

    ['order' => 11, 'code' => 'PRD-011', 'product' => 'Product No.8911234567', 'price' => 3899.99, 'img' => '/public/products/p11.jpg', 'earn' => 2.25],
    ['order' => 12, 'code' => 'PRD-012', 'product' => 'Product No.8912345678', 'price' => 5499.99, 'img' => '/public/products/p12.jpg', 'earn' => 2.10],
    ['order' => 13, 'code' => 'PRD-013', 'product' => 'Product No.8913456789', 'price' => 399.99, 'img' => '/public/products/p13.jpg', 'earn' => 2.30],
    ['order' => 14, 'code' => 'PRD-014', 'product' => 'Product No.8914567890', 'price' => 2199.99, 'img' => '/public/products/p14.jpg', 'earn' => 1.85],
    ['order' => 15, 'code' => 'PRD-015', 'product' => 'Product No.8915678901', 'price' => 349.99, 'img' => '/public/products/p15.jpg', 'earn' => 2.45],

    ['order' => 16, 'code' => 'PRD-016', 'product' => 'Product No.8916789012', 'price' => 1999.99, 'img' => '/public/products/p16.jpg', 'earn' => 2.15],
    ['order' => 17, 'code' => 'PRD-017', 'product' => 'Product No.8917890123', 'price' => 199.99, 'img' => '/public/products/p17.jpg', 'earn' => 2.05],
    ['order' => 18, 'code' => 'PRD-018', 'product' => 'Product No.8918901234', 'price' => 999.99, 'img' => '/public/products/p18.jpg', 'earn' => 2.30],
    ['order' => 19, 'code' => 'PRD-019', 'product' => 'Product No.8919012345', 'price' => 899.99, 'img' => '/public/products/p19.jpg', 'earn' => 2.20],
    ['order' => 20, 'code' => 'PRD-020', 'product' => 'Product No.8920123456', 'price' => 1299.99, 'img' => '/public/products/p20.jpg', 'earn' => 2.00],

    ['order' => 21, 'code' => 'PRD-021', 'product' => 'Product No.8921234567', 'price' => 649.99, 'img' => '/public/products/p21.jpg', 'earn' => 2.25],
    ['order' => 22, 'code' => 'PRD-022', 'product' => 'Product No.8922345678', 'price' => 799.99, 'img' => '/public/products/p22.jpg', 'earn' => 2.10],
    ['order' => 23, 'code' => 'PRD-023', 'product' => 'Product No.8923456789', 'price' => 249.99, 'img' => '/public/products/p23.jpg', 'earn' => 2.35],
    ['order' => 24, 'code' => 'PRD-024', 'product' => 'Product No.8924567890', 'price' => 279.99, 'img' => '/public/products/p24.jpg', 'earn' => 1.90],
    ['order' => 25, 'code' => 'PRD-025', 'product' => 'Product No.8925678901', 'price' => 299.99, 'img' => '/public/products/p25.jpg', 'earn' => 2.40],

    ['order' => 26, 'code' => 'PRD-026', 'product' => 'Product No.8926789012', 'price' => 899.99, 'img' => '/public/products/p26.jpg', 'earn' => 2.15],
    ['order' => 27, 'code' => 'PRD-027', 'product' => 'Product No.8927890123', 'price' => 12999.99, 'img' => '/public/products/p27.jpg', 'earn' => 2.05],
    ['order' => 28, 'code' => 'PRD-028', 'product' => 'Product No.8928901234', 'price' => 5999.99, 'img' => '/public/products/p28.jpg', 'earn' => 2.30],
    ['order' => 29, 'code' => 'PRD-029', 'product' => 'Product No.8929012345', 'price' => 4999.99, 'img' => '/public/products/p29.jpg', 'earn' => 2.20],
    ['order' => 30, 'code' => 'PRD-030', 'product' => 'Product No.8930123456', 'price' => 7999.99, 'img' => '/public/products/p30.jpg', 'earn' => 2.00],

    ['order' => 31, 'code' => 'PRD-031', 'product' => 'Product No.8931234567', 'price' => 499.99, 'img' => '/public/products/p31.jpg', 'earn' => 2.25],
    ['order' => 32, 'code' => 'PRD-032', 'product' => 'Product No.8932345678', 'price' => 1999.99, 'img' => '/public/products/p32.jpg', 'earn' => 2.10],
    ['order' => 33, 'code' => 'PRD-033', 'product' => 'Product No.8933456789', 'price' => 2499.99, 'img' => '/public/products/p33.jpg', 'earn' => 2.30],
    ['order' => 34, 'code' => 'PRD-034', 'product' => 'Product No.8934567890', 'price' => 1799.99, 'img' => '/public/products/p34.jpg', 'earn' => 2.15],
    ['order' => 35, 'code' => 'PRD-035', 'product' => 'Product No.8935678901', 'price' => 3999.99, 'img' => '/public/products/p35.jpg', 'earn' => 2.05],
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
            ':brand_name' => $task['code'],
            ':product_name' => $task['product'],
            ':price' => $task['price'],
            ':earning_amount' => $task['earn']
        ]);

        $successCount++;
        echo "✓ Task {$task['order']}: {$task['code']} - {$task['product']}\n";
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
