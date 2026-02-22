<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Product Details to Tasks</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0af; }
    </style>
</head>
<body>
<h2>Adding Product Details to Admin Tasks</h2>
<pre>
<?php
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "<span class='error'>✗ Database connection failed!</span>\n";
    exit;
}

echo "<span class='success'>✓ Database connected</span>\n\n";

// Add new columns if they don't exist
echo "<span class='info'>Adding new columns...</span>\n";

try {
    $db->exec("ALTER TABLE admin_tasks ADD COLUMN IF NOT EXISTS product_name VARCHAR(255) DEFAULT NULL");
    echo "<span class='success'>✓ Added product_name column</span>\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<span class='info'>  product_name column already exists</span>\n";
    } else {
        echo "<span class='error'>✗ Error adding product_name: " . $e->getMessage() . "</span>\n";
    }
}

try {
    $db->exec("ALTER TABLE admin_tasks ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT NULL");
    echo "<span class='success'>✓ Added price column</span>\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<span class='info'>  price column already exists</span>\n";
    } else {
        echo "<span class='error'>✗ Error adding price: " . $e->getMessage() . "</span>\n";
    }
}

echo "\n<span class='info'>Updating tasks with product details...</span>\n";

// Update tasks with product information
$updates = [
    1 => [
        'brand' => 'Nike',
        'product' => 'Air Max Sneakers',
        'price' => 120.00,
        'image' => '/public/products/P1.jpg',
        'earning' => 2.25
    ],
    2 => [
        'brand' => 'Apple',
        'product' => 'iPhone 15 Pro',
        'price' => 999.00,
        'image' => '/public/products/P2.jpg',
        'earning' => 2.10
    ],
    3 => [
        'brand' => 'Samsung',
        'product' => 'Galaxy S24 Ultra',
        'price' => 899.00,
        'image' => '/public/products/P3.jpg',
        'earning' => 2.30
    ],
    4 => [
        'brand' => 'Adidas',
        'product' => 'Ultraboost Running Shoes',
        'price' => 180.00,
        'image' => '/public/products/P4.jpg',
        'earning' => 1.95
    ],
    5 => [
        'brand' => 'Sony',
        'product' => 'WH-1000XM5 Headphones',
        'price' => 399.00,
        'image' => '/public/products/P5.jpg',
        'earning' => 2.40
    ]
];

foreach ($updates as $order => $data) {
    $stmt = $db->prepare("UPDATE admin_tasks SET brand_name = :brand, product_name = :product, price = :price, image_url = :image, earning_amount = :earning WHERE task_order = :order");
    $result = $stmt->execute([
        ':brand' => $data['brand'],
        ':product' => $data['product'],
        ':price' => $data['price'],
        ':image' => $data['image'],
        ':earning' => $data['earning'],
        ':order' => $order
    ]);

    if ($result) {
        echo "<span class='success'>✓ Updated task {$order}: {$data['product']} (${$data['price']})</span>\n";
    } else {
        echo "<span class='error'>✗ Failed to update task {$order}</span>\n";
    }
}

echo "\n<span class='info'>Verifying updates...</span>\n";
$stmt = $db->query("SELECT task_order, brand_name, product_name, price, earning_amount, image_url FROM admin_tasks WHERE task_order <= 5 ORDER BY task_order");
$tasks = $stmt->fetchAll();

foreach ($tasks as $task) {
    echo "  Task {$task['task_order']}: {$task['product_name']} by {$task['brand_name']} - \${$task['price']} (Earn: \${$task['earning_amount']})\n";
    echo "    Image: {$task['image_url']}\n";
}

echo "\n<span class='success'>✓ Migration completed successfully!</span>\n";
echo "\n<a href='/admin?tab=tasks' style='color: #0af;'>→ Go to Admin Panel</a>\n";
echo "<a href='/tasks' style='color: #0af; margin-left: 20px;'>→ View Tasks</a>\n";
?>
</pre>
</body>
</html>
