<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/vip_badge.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$database = new Database();
$db = $database->getConnection();

$userId = $_SESSION['user_id'];

// Check if daily_task_limit column exists
$hasTaskLimit = false;
try {
    $checkCol = $db->query("SHOW COLUMNS FROM vip_tiers LIKE 'daily_task_limit'");
    $hasTaskLimit = $checkCol->rowCount() > 0;
} catch (PDOException $e) {
    $hasTaskLimit = false;
}

// Check if training_completed column exists in users table
$hasTrainingCompleted = false;
try {
    $checkTraining = $db->query("SHOW COLUMNS FROM users LIKE 'training_completed'");
    $hasTrainingCompleted = $checkTraining->rowCount() > 0;
} catch (PDOException $e) {
    $hasTrainingCompleted = false;
}

$taskLimitField = $hasTaskLimit ? ', vt.daily_task_limit' : '';
$trainingField = $hasTrainingCompleted ? ', u.training_completed' : '';
$query = "SELECT up.*, vt.level as vip_level, vt.name as vip_name, vt.max_tasks_per_day{$taskLimitField}, w.balance{$trainingField}
          FROM user_profiles up
          LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id
          LEFT JOIN wallets w ON w.user_id = up.id
          LEFT JOIN users u ON u.id = up.id
          WHERE up.id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$userProfile = $stmt->fetch();

if (!$userProfile) {
    redirect('/dashboard.php');
}

$vipLevel = $userProfile['vip_level'] ?? 1;
$balance = $userProfile['balance'] ?? 0;
$fullName = $userProfile['full_name'] ?? 'User';
$isTrainingAccount = isset($userProfile['training_completed']) && !$userProfile['training_completed'];
$taskLimit = $userProfile['daily_task_limit'] ?? $userProfile['max_tasks_per_day'] ?? 35;

$tasksQuery = "SELECT * FROM tasks WHERE status = 'active' ORDER BY created_at DESC LIMIT 20";
$tasksStmt = $db->prepare($tasksQuery);
$tasksStmt->execute();
$tasks = $tasksStmt->fetchAll();

$completedQuery = "SELECT COUNT(*) as completed FROM user_task_submissions WHERE user_id = :user_id AND DATE(created_at) = CURDATE()";
$completedStmt = $db->prepare($completedQuery);
$completedStmt->bindParam(':user_id', $userId);
$completedStmt->execute();
$completedData = $completedStmt->fetch();
$tasksCompleted = $completedData['completed'] ?? 0;

$earningsQuery = "SELECT COALESCE(SUM(amount), 0) as earnings FROM transactions WHERE user_id = :user_id AND DATE(created_at) = CURDATE() AND type = 'task_completion'";
$earningsStmt = $db->prepare($earningsQuery);
$earningsStmt->bindParam(':user_id', $userId);
$earningsStmt->execute();
$earningsData = $earningsStmt->fetch();
$earningsToday = $earningsData['earnings'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - EarningsLLC</title>
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            color: white;
        }

        .header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #3b82f6;
        }

        .stats-header {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .stat-value.success {
            color: #10b981;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .vip-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .vip-info {
            text-align: center;
        }

        .vip-info h2 {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .vip-info p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }

        .training-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.5);
            margin-top: 8px;
        }

        .training-progress {
            margin-top: 12px;
            padding: 12px;
            background: rgba(245, 158, 11, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 5px;
            text-align: center;
        }

        .task-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .task-vip-badge {
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .task-image {
            width: 100%;
            max-width: 400px;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
            margin: 20px auto;
            display: block;
            border: 3px solid rgba(59, 130, 246, 0.2);
        }

        .task-id {
            text-align: center;
            font-size: 1.3rem;
            font-weight: 800;
            color: #1e293b;
            margin: 15px 0;
            letter-spacing: 1px;
        }

        .task-profit {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: #10b981;
            margin: 10px 0;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .no-tasks {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        .no-tasks-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
                align-items: stretch;
            }

            .back-btn {
                font-size: 0.95rem;
            }

            .stats-header {
                gap: 20px;
                justify-content: center;
            }

            .stat-label {
                font-size: 0.7rem;
            }

            .stat-value {
                font-size: 1rem;
            }

            .vip-section {
                flex-direction: column;
                padding: 20px;
                gap: 15px;
            }

            .vip-info h2 {
                font-size: 1.3rem;
            }

            .vip-info p {
                font-size: 0.85rem;
            }

            .training-progress {
                padding: 10px;
            }

            .container {
                padding: 20px 15px;
            }

            .task-card {
                padding: 15px;
            }

            .task-image {
                max-width: 100%;
                height: 250px;
            }

            .task-id {
                font-size: 1.1rem;
            }

            .task-profit {
                font-size: 1rem;
            }

            .submit-btn {
                padding: 14px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 12px;
            }

            .back-btn {
                font-size: 0.9rem;
            }

            .stats-header {
                flex-direction: column;
                gap: 10px;
            }

            .stat-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                padding: 8px 12px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 8px;
            }

            .stat-label {
                font-size: 0.75rem;
                text-align: left;
                margin-bottom: 0;
            }

            .stat-value {
                font-size: 0.95rem;
            }

            .vip-section {
                padding: 15px;
            }

            .vip-info h2 {
                font-size: 1.1rem;
            }

            .vip-info p {
                font-size: 0.8rem;
            }

            .training-badge {
                font-size: 0.75rem;
                padding: 5px 12px;
            }

            .training-progress {
                padding: 8px;
                margin-top: 10px;
            }

            .progress-text {
                font-size: 0.75rem;
            }

            .container {
                padding: 15px 10px;
            }

            .task-card {
                padding: 12px;
                margin-bottom: 15px;
            }

            .task-image {
                height: 200px;
                margin: 15px auto;
            }

            .task-id {
                font-size: 1rem;
                margin: 12px 0;
            }

            .task-profit {
                font-size: 0.95rem;
            }

            .submit-btn {
                padding: 12px;
                font-size: 0.9rem;
            }

            .no-tasks {
                padding: 40px 15px;
            }

            .no-tasks-icon {
                font-size: 3rem;
            }

            .no-tasks h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="back-btn">
            <span>←</span>
            <span>Back to Dashboard</span>
        </a>

        <div class="stats-header">
            <div class="stat-item">
                <div class="stat-label">Tasks Completed</div>
                <div class="stat-value"><?php echo $tasksCompleted; ?> / <?php echo $taskLimit > 1000 ? 'Unlimited' : $taskLimit; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Earnings Today</div>
                <div class="stat-value success">$<?php echo number_format($earningsToday, 2); ?></div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="vip-section">
            <?php echo renderVipBadge($vipLevel, 'large'); ?>
            <div class="vip-info">
                <h2>Welcome, <?php echo htmlspecialchars($fullName); ?></h2>
                <p>Your VIP Level: <?php echo $vipLevel; ?> | Balance: $<?php echo number_format($balance, 2); ?></p>
                <?php if ($isTrainingAccount): ?>
                    <div class="training-badge">
                        <span>🎓</span>
                        <span>TRAINING ACCOUNT</span>
                    </div>
                    <div class="training-progress">
                        <div style="font-weight: 600; font-size: 0.9rem; color: white; margin-bottom: 6px;">
                            Training Progress: <?php echo $tasksCompleted; ?>/15 tasks
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min(100, ($tasksCompleted / 15) * 100); ?>%"></div>
                        </div>
                        <div class="progress-text">
                            <?php
                            $remaining = 15 - $tasksCompleted;
                            if ($remaining > 0) {
                                echo $remaining . " more task" . ($remaining != 1 ? "s" : "") . " to unlock main dashboard";
                            } else {
                                echo "Training complete! Finish current task to proceed.";
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                <p style="margin-top: 8px; font-size: 0.9rem;">Daily Limit: <?php echo $taskLimit > 1000 ? 'Unlimited' : $taskLimit; ?> tasks</p>
            </div>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="no-tasks">
                <div class="no-tasks-icon">📋</div>
                <h2 style="margin-bottom: 10px;">No Tasks Available</h2>
                <p style="color: rgba(255, 255, 255, 0.7);">Check back later for new tasks</p>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <div class="task-card">
                    <div class="task-vip-badge">
                        <?php echo renderVipBadgeInline($vipLevel); ?>
                    </div>

                    <img src="<?php echo htmlspecialchars($task['image_url'] ?? 'https://images.pexels.com/photos/190819/pexels-photo-190819.jpeg?auto=compress&cs=tinysrgb&w=800'); ?>"
                         alt="Task Product"
                         class="task-image">

                    <div class="task-id">
                        <?php echo htmlspecialchars($task['product_code'] ?? 'PROD-' . strtoupper(substr(md5($task['id']), 0, 8))); ?>
                    </div>

                    <div class="task-profit">
                        Profit: USD <?php echo number_format($task['reward_amount'] ?? 2.25, 2); ?>
                    </div>

                    <form method="POST" action="submit_task.php">
                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                        <button type="submit" class="submit-btn">
                            Click submit to complete this task
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
