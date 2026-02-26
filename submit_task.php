<?php
require_once __DIR__ . '/config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tasks.php');
}

$database = new Database();
$db = $database->getConnection();

$userId = $_SESSION['user_id'];
$taskId = $_POST['task_id'] ?? null;

if (!$taskId) {
    $_SESSION['error'] = 'Invalid task';
    redirect('/tasks.php');
}

try {
    $db->beginTransaction();

    $taskQuery = "SELECT * FROM tasks WHERE id = :task_id AND status = 'active'";
    $taskStmt = $db->prepare($taskQuery);
    $taskStmt->bindParam(':task_id', $taskId);
    $taskStmt->execute();
    $task = $taskStmt->fetch();

    if (!$task) {
        throw new Exception('Task not found or inactive');
    }

    $checkQuery = "SELECT id FROM user_task_submissions WHERE user_id = :user_id AND task_id = :task_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $userId);
    $checkStmt->bindParam(':task_id', $taskId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        throw new Exception('Task already submitted');
    }

    $submissionId = generateUUID();
    $insertQuery = "INSERT INTO user_task_submissions (id, user_id, task_id, status, submitted_at)
                    VALUES (:id, :user_id, :task_id, 'pending', NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':id', $submissionId);
    $insertStmt->bindParam(':user_id', $userId);
    $insertStmt->bindParam(':task_id', $taskId);
    $insertStmt->execute();

    $updateWalletQuery = "UPDATE wallets SET balance = balance + :amount, total_earnings = total_earnings + :amount
                          WHERE user_id = :user_id";
    $updateWalletStmt = $db->prepare($updateWalletQuery);
    $amount = $task['reward_amount'];
    $updateWalletStmt->bindParam(':amount', $amount);
    $updateWalletStmt->bindParam(':user_id', $userId);
    $updateWalletStmt->execute();

    $transactionId = generateUUID();
    $transactionQuery = "INSERT INTO transactions (id, user_id, type, amount, status, description)
                         VALUES (:id, :user_id, 'task_completion', :amount, 'completed', :description)";
    $transactionStmt = $db->prepare($transactionQuery);
    $transactionStmt->bindParam(':id', $transactionId);
    $transactionStmt->bindParam(':user_id', $userId);
    $transactionStmt->bindParam(':amount', $amount);
    $description = 'Task completion: ' . ($task['product_code'] ?? $task['title']);
    $transactionStmt->bindParam(':description', $description);
    $transactionStmt->execute();

    $db->commit();

    $_SESSION['success'] = 'Task submitted successfully! $' . number_format($amount, 2) . ' added to your balance.';
    redirect('/tasks.php');

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = $e->getMessage();
    redirect('/tasks.php');
}
?>
