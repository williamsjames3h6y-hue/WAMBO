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

// Array of product images to cycle through
$productImages = [
    '/public/products/P1.jpg',
    '/public/products/P2.jpg',
    '/public/products/P3.jpg',
    '/public/products/P4.jpg',
    '/public/products/P5.jpg'
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
