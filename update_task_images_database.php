<?php
/**
 * Script to Update All Task Images in Database
 * This will cycle through the 5 product images (P1-P5) for all tasks
 */

require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Failed to connect to database.\n");
}

// Array of product images to cycle through (P1-P37)
$productImages = [
    '/public/products/P1.jpg',
    '/public/products/P2.jpg',
    '/public/products/P3.jpg',
    '/public/products/P4.jpg',
    '/public/products/P5.jpg',
    '/public/products/P6.jpg',
    '/public/products/P7.jpg',
    '/public/products/P8.jpg',
    '/public/products/P9.jpg',
    '/public/products/p10.jpg',
    '/public/products/p11.jpg',
    '/public/products/p12.jpg',
    '/public/products/p13.jpg',
    '/public/products/p14.jpg',
    '/public/products/p15.jpg',
    '/public/products/p16.jpg',
    '/public/products/p17.jpg',
    '/public/products/p18.jpg',
    '/public/products/p19.jpg',
    '/public/products/p20.jpg',
    '/public/products/p21.jpg',
    '/public/products/p22.jpg',
    '/public/products/p23.jpg',
    '/public/products/p24.jpg',
    '/public/products/p25.jpg',
    '/public/products/p26.jpg',
    '/public/products/p27.jpg',
    '/public/products/p28.jpg',
    '/public/products/p29.jpg',
    '/public/products/p30.jpg',
    '/public/products/p31.jpg',
    '/public/products/p32.jpg',
    '/public/products/p33.jpg',
    '/public/products/p34.jpg',
    '/public/products/p35.jpg',
    '/public/products/p36.jpg',
    '/public/products/p37.jpg'
];

try {
    // Get all tasks ordered by task_order
    $stmt = $db->query("SELECT id, task_order, brand_name FROM admin_tasks ORDER BY task_order ASC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($tasks) == 0) {
        echo "No tasks found in database.\n";
        exit;
    }

    echo "Found " . count($tasks) . " tasks to update...\n\n";

    // Prepare update statement
    $updateStmt = $db->prepare("UPDATE admin_tasks SET image_url = :image_url WHERE id = :id");

    $count = 0;
    foreach ($tasks as $index => $task) {
        // Calculate which image to use (cycling through the 5 images)
        $imageIndex = $index % count($productImages);
        $imageUrl = $productImages[$imageIndex];

        // Update the task
        $updateStmt->execute([
            ':image_url' => $imageUrl,
            ':id' => $task['id']
        ]);

        $count++;
        echo sprintf(
            "Updated Task %d (%s): %s\n",
            $task['task_order'],
            $task['brand_name'],
            $imageUrl
        );
    }

    echo "\n✓ Successfully updated all $count task images!\n";
    echo "Images are now rotating through the 5 product images (P1-P5).\n";
    echo "Each task submission will show a different image in sequence.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
