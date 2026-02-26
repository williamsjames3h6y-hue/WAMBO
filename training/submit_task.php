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

    $taskQuery = "SELECT id, brand_name, product_name, earning_amount FROM admin_tasks WHERE id = :task_id";
    $taskStmt = $db->prepare($taskQuery);
    $taskStmt->bindParam(':task_id', $taskId);
    $taskStmt->execute();
    $task = $taskStmt->fetch();

    if (!$task) {
        throw new Exception('Task not found');
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
                    VALUES (:id, :user_id, :task_id, 'completed', NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':id', $submissionId);
    $insertStmt->bindParam(':user_id', $userId);
    $insertStmt->bindParam(':task_id', $taskId);
    $insertStmt->execute();

    $updateWalletQuery = "UPDATE wallets SET balance = balance + :amount, total_earnings = total_earnings + :amount
                          WHERE user_id = :user_id";
    $updateWalletStmt = $db->prepare($updateWalletQuery);
    $amount = $task['earning_amount'];
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
    $description = 'Task completion: ' . ($task['brand_name'] ?? $task['product_name']);
    $transactionStmt->bindParam(':description', $description);
    $transactionStmt->execute();

    $db->commit();

    // Check if this is a training account and if they completed 15 tasks
    $countQuery = "SELECT COUNT(*) as task_count FROM user_task_submissions WHERE user_id = :user_id AND status = 'completed'";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':user_id', $userId);
    $countStmt->execute();
    $countData = $countStmt->fetch();
    $completedCount = $countData['task_count'] ?? 0;

    $checkTrainingQuery = "SELECT u.training_completed, u.email, up.full_name
                           FROM users u
                           LEFT JOIN user_profiles up ON up.id = u.id
                           WHERE u.id = :user_id";
    $checkTrainingStmt = $db->prepare($checkTrainingQuery);
    $checkTrainingStmt->bindParam(':user_id', $userId);
    $checkTrainingStmt->execute();
    $userData = $checkTrainingStmt->fetch();

    // If training account and completed 15 tasks, mark training as complete
    if ($userData && isset($userData['training_completed']) && !$userData['training_completed'] && $completedCount >= 15) {
        $updateTrainingQuery = "UPDATE users SET training_completed = 1 WHERE id = :user_id";
        $updateTrainingStmt = $db->prepare($updateTrainingQuery);
        $updateTrainingStmt->bindParam(':user_id', $userId);
        $updateTrainingStmt->execute();

        // Send Telegram notification
        require_once __DIR__ . '/includes/telegram.php';
        $telegram = new TelegramNotifier();
        $telegram->sendTrainingComplete($userData['full_name'], $userData['email']);

        // Redirect to main login page with message
        $_SESSION['training_complete'] = true;
        $_SESSION['success'] = 'Training completed! Please login with your personal account to continue.';
        session_destroy();
        redirect('/login.php');
    }

    $_SESSION['success'] = 'Task submitted successfully! $' . number_format($amount, 2) . ' added to your balance.';
    redirect('/tasks.php');

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = $e->getMessage();
    redirect('/tasks.php');
}
?>
