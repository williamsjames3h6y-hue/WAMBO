<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$database = new Database();
$db = $database->getConnection();
$userId = getCurrentUserId();

switch ($action) {
    case 'get_tasks':
        try {
            // Get user's VIP level
            $query = "SELECT vt.level FROM user_profiles up
                     JOIN vip_tiers vt ON up.vip_tier_id = vt.id
                     WHERE up.id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $user = $stmt->fetch();

            $vipLevel = $user['level'] ?? 1;

            // Get tasks for this VIP level
            $query = "SELECT * FROM admin_tasks WHERE vip_level_required <= :vip_level ORDER BY task_order ASC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':vip_level', $vipLevel);
            $stmt->execute();
            $tasks = $stmt->fetchAll();

            // Get user's submissions
            $query = "SELECT task_id, status FROM user_task_submissions WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $submissions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            jsonResponse(['success' => true, 'tasks' => $tasks, 'submissions' => $submissions]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'submit_task':
        try {
            $taskId = $_POST['task_id'] ?? '';
            $answer = sanitizeInput($_POST['answer'] ?? '');

            if (empty($taskId) || empty($answer)) {
                jsonResponse(['success' => false, 'message' => 'Task ID and answer are required'], 400);
            }

            // Get task details
            $query = "SELECT * FROM admin_tasks WHERE id = :task_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':task_id', $taskId);
            $stmt->execute();
            $task = $stmt->fetch();

            if (!$task) {
                jsonResponse(['success' => false, 'message' => 'Task not found'], 404);
            }

            // Check if already submitted
            $query = "SELECT id FROM user_task_submissions WHERE user_id = :user_id AND task_id = :task_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':task_id', $taskId);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => false, 'message' => 'Task already submitted'], 400);
            }

            // Insert submission
            $submissionId = generateUUID();
            $query = "INSERT INTO user_task_submissions (id, user_id, task_id, user_answer, status)
                     VALUES (:id, :user_id, :task_id, :answer, 'completed')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $submissionId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':task_id', $taskId);
            $stmt->bindParam(':answer', $answer);
            $stmt->execute();

            // Update wallet balance
            $walletId = generateUUID();
            $query = "UPDATE wallets SET balance = balance + :amount, total_earnings = total_earnings + :amount WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':amount', $task['earning_amount']);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            // Get wallet for transaction
            $query = "SELECT id FROM wallets WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $wallet = $stmt->fetch();

            // Record transaction
            $transactionId = generateUUID();
            $description = "Earnings from " . $task['brand_name'];
            $query = "INSERT INTO transactions (id, user_id, wallet_id, type, amount, status, description)
                     VALUES (:id, :user_id, :wallet_id, 'earnings', :amount, 'completed', :description)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $transactionId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':wallet_id', $wallet['id']);
            $stmt->bindParam(':amount', $task['earning_amount']);
            $stmt->bindParam(':description', $description);
            $stmt->execute();

            // Update daily earnings
            $today = date('Y-m-d');
            $query = "INSERT INTO daily_earnings (id, user_id, date, tasks_completed, commission_earned, total_earnings)
                     VALUES (:id, :user_id, :date, 1, :amount, :amount)
                     ON DUPLICATE KEY UPDATE
                     tasks_completed = tasks_completed + 1,
                     commission_earned = commission_earned + :amount,
                     total_earnings = total_earnings + :amount";
            $stmt = $db->prepare($query);
            $earningsId = generateUUID();
            $stmt->bindParam(':id', $earningsId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':date', $today);
            $stmt->bindParam(':amount', $task['earning_amount']);
            $stmt->execute();

            jsonResponse(['success' => true, 'message' => 'Task submitted successfully', 'earned' => $task['earning_amount']]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'get_wallet':
        try {
            $query = "SELECT * FROM wallets WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $wallet = $stmt->fetch();

            if (!$wallet) {
                // Create wallet if doesn't exist
                $walletId = generateUUID();
                $query = "INSERT INTO wallets (id, user_id, balance, total_earnings) VALUES (:id, :user_id, 0.00, 0.00)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $walletId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();

                $wallet = ['id' => $walletId, 'user_id' => $userId, 'balance' => 0.00, 'total_earnings' => 0.00];
            }

            jsonResponse(['success' => true, 'wallet' => $wallet]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'get_transactions':
        try {
            $query = "SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $transactions = $stmt->fetchAll();

            jsonResponse(['success' => true, 'transactions' => $transactions]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'get_daily_earnings':
        try {
            $today = date('Y-m-d');
            $query = "SELECT * FROM daily_earnings WHERE user_id = :user_id AND date = :date";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':date', $today);
            $stmt->execute();
            $earnings = $stmt->fetch();

            jsonResponse(['success' => true, 'earnings' => $earnings]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}
?>
