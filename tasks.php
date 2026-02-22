<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$auth = new Auth();
$userId = getCurrentUserId();
$profile = $auth->getUserProfile($userId);

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get VIP tier
$stmt = $db->prepare("SELECT vt.* FROM user_profiles up LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id WHERE up.id = :user_id");
$stmt->execute([':user_id' => $userId]);
$vipTier = $stmt->fetch();

// Get product images
$stmt = $db->prepare("SELECT * FROM product_images WHERE is_active = TRUE ORDER BY display_order ASC");
$stmt->execute();
$productImages = $stmt->fetchAll();

// Get admin tasks for user's VIP level
$stmt = $db->prepare("SELECT * FROM admin_tasks WHERE vip_level_required = :vip_level ORDER BY task_order ASC");
$stmt->execute([':vip_level' => $vipTier['level']]);
$tasks = $stmt->fetchAll();

// Map product images to tasks
$updatedTasks = [];
foreach ($tasks as $index => $task) {
    $productImage = $productImages[$index % count($productImages)];
    $task['image_url'] = $productImage['image_url'] ?? $task['image_url'];
    $task['brand_name'] = $productImage['brand_name'] ?? $task['brand_name'];
    $task['product_name'] = $productImage['product_name'] ?? null;
    $task['price'] = $productImage['price'] ?? null;
    $updatedTasks[] = $task;
}

// Get today's submissions
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM user_task_submissions WHERE user_id = :user_id AND DATE(created_at) = :date");
$stmt->execute([':user_id' => $userId, ':date' => $today]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily stats
$stmt = $db->prepare("SELECT * FROM daily_earnings WHERE user_id = :user_id AND date = :date");
$stmt->execute([':user_id' => $userId, ':date' => $today]);
$dailyEarnings = $stmt->fetch();

if (!$dailyEarnings) {
    $dailyEarnings = ['tasks_completed' => 0, 'total_earnings' => 0];
}

// Find next incomplete task
$currentTaskIndex = 0;
foreach ($updatedTasks as $index => $task) {
    $isCompleted = false;
    foreach ($submissions as $submission) {
        if ($submission['task_id'] === $task['id']) {
            $isCompleted = true;
            break;
        }
    }
    if (!$isCompleted) {
        $currentTaskIndex = $index;
        break;
    }
}

// Handle task submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_task'])) {
    $taskId = $_POST['task_id'];

    // Check if already submitted
    $stmt = $db->prepare("SELECT * FROM user_task_submissions WHERE user_id = :user_id AND task_id = :task_id");
    $stmt->execute([':user_id' => $userId, ':task_id' => $taskId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        // Find the task
        $currentTask = null;
        foreach ($updatedTasks as $task) {
            if ($task['id'] === $taskId) {
                $currentTask = $task;
                break;
            }
        }

        if ($currentTask) {
            // Insert submission
            $submissionId = generateUUID();
            $stmt = $db->prepare("INSERT INTO user_task_submissions (id, user_id, task_id, user_answer, status, completed_at) VALUES (:id, :user_id, :task_id, '{}', 'completed', NOW())");
            $stmt->execute([
                ':id' => $submissionId,
                ':user_id' => $userId,
                ':task_id' => $taskId
            ]);

            // Get or create wallet
            $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                $walletId = generateUUID();
                $stmt = $db->prepare("INSERT INTO wallets (id, user_id, balance, total_earnings) VALUES (:id, :user_id, 0.00, 0.00)");
                $stmt->execute([':id' => $walletId, ':user_id' => $userId]);
                $wallet = ['id' => $walletId, 'balance' => 0, 'total_earnings' => 0];
            }

            // Update wallet balance
            $newBalance = floatval($wallet['balance']) + floatval($currentTask['earning_amount']);
            $stmt = $db->prepare("UPDATE wallets SET balance = :balance, updated_at = NOW() WHERE id = :wallet_id");
            $stmt->execute([
                ':balance' => $newBalance,
                ':wallet_id' => $wallet['id']
            ]);

            // Insert transaction
            $transactionId = generateUUID();
            $stmt = $db->prepare("INSERT INTO transactions (id, user_id, wallet_id, type, amount, status, description) VALUES (:id, :user_id, :wallet_id, 'earnings', :amount, 'completed', :description)");
            $stmt->execute([
                ':id' => $transactionId,
                ':user_id' => $userId,
                ':wallet_id' => $wallet['id'],
                ':amount' => $currentTask['earning_amount'],
                ':description' => "Brand identification task #{$currentTask['task_order']} completed"
            ]);

            // Update daily earnings
            if ($dailyEarnings && isset($dailyEarnings['id'])) {
                $newTasksCompleted = intval($dailyEarnings['tasks_completed']) + 1;
                $newTotalEarnings = floatval($dailyEarnings['total_earnings']) + floatval($currentTask['earning_amount']);
                $canWithdraw = $newTasksCompleted >= 35;

                $stmt = $db->prepare("UPDATE daily_earnings SET tasks_completed = :tasks_completed, commission_earned = commission_earned + :commission, total_earnings = :total_earnings, can_withdraw = :can_withdraw WHERE id = :id");
                $stmt->execute([
                    ':tasks_completed' => $newTasksCompleted,
                    ':commission' => $currentTask['earning_amount'],
                    ':total_earnings' => $newTotalEarnings,
                    ':can_withdraw' => $canWithdraw ? 1 : 0,
                    ':id' => $dailyEarnings['id']
                ]);
            } else {
                $dailyEarningId = generateUUID();
                $stmt = $db->prepare("INSERT INTO daily_earnings (id, user_id, date, tasks_completed, commission_earned, total_earnings, can_withdraw) VALUES (:id, :user_id, :date, 1, :commission, :total, 0)");
                $stmt->execute([
                    ':id' => $dailyEarningId,
                    ':user_id' => $userId,
                    ':date' => $today,
                    ':commission' => $currentTask['earning_amount'],
                    ':total' => $currentTask['earning_amount']
                ]);
            }

            $_SESSION['show_preloader'] = true;
            redirect('tasks.php');
        }
    }
}

$totalTasks = count($updatedTasks);
$completedTasks = count($submissions);
$allCompleted = $completedTasks >= $totalTasks && $totalTasks > 0;

$showPreloader = isset($_SESSION['show_preloader']) && $_SESSION['show_preloader'];
unset($_SESSION['show_preloader']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <?php if ($showPreloader): ?>
    <div id="preloader" class="fixed inset-0 bg-white z-[9999] flex items-center justify-center">
        <div class="text-center">
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 border-4 border-blue-500/30 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-blue-500 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <p class="text-gray-900 text-2xl font-bold mb-2">Loading Next Task</p>
            <p class="text-gray-600">Preparing your next product...</p>
        </div>
    </div>
    <script>
        setTimeout(function() {
            document.getElementById('preloader').style.display = 'none';
        }, 3000);
    </script>
    <?php endif; ?>

    <!-- Header -->
    <header class="bg-slate-800/50 backdrop-blur-sm shadow-sm border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <a href="dashboard.php" class="flex items-center space-x-2 text-white hover:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Dashboard</span>
                </a>

                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        <p class="text-sm text-gray-400">Tasks Completed</p>
                        <p class="text-xl font-bold text-white">
                            <?php echo $completedTasks; ?> / <?php echo $totalTasks; ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-400">Earnings Today</p>
                        <p class="text-xl font-bold text-green-400">
                            $<?php echo number_format($dailyEarnings['total_earnings'] ?? 0, 2); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <?php if ($allCompleted): ?>
        <!-- All Tasks Completed -->
        <div class="text-center">
            <svg class="w-24 h-24 text-green-500 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="text-4xl font-bold text-white mb-4">All Tasks Completed!</h2>
            <p class="text-xl text-gray-300 mb-4">
                You've completed all <?php echo $totalTasks; ?> tasks for today
            </p>
            <p class="text-lg text-gray-200 mb-8">
                Total Earnings: <span class="font-bold text-green-400">$<?php echo number_format($dailyEarnings['total_earnings'] ?? 0, 2); ?></span>
            </p>
            <a href="dashboard.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700">
                Back to Dashboard
            </a>
        </div>
        <?php elseif (isset($updatedTasks[$currentTaskIndex])): ?>
        <?php
            $currentTask = $updatedTasks[$currentTaskIndex];
            $isCompleted = false;
            foreach ($submissions as $submission) {
                if ($submission['task_id'] === $currentTask['id']) {
                    $isCompleted = true;
                    break;
                }
            }
        ?>
        <!-- Current Task -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-3xl shadow-2xl p-8 border border-slate-700">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-white mb-2">
                    Task <?php echo $currentTask['task_order']; ?>
                </h2>
            </div>

            <div class="mb-8">
                <div class="bg-white rounded-2xl p-6 flex items-center justify-center min-h-[280px]">
                    <img
                        src="<?php echo htmlspecialchars($currentTask['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($currentTask['brand_name']); ?>"
                        class="max-w-full max-h-[250px] object-contain rounded-lg"
                    />
                </div>

                <div class="mt-6 text-center">
                    <h3 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($currentTask['brand_name']); ?></h3>
                    <?php if (!empty($currentTask['product_name'])): ?>
                    <p class="text-gray-400 mb-3"><?php echo htmlspecialchars($currentTask['product_name']); ?></p>
                    <?php endif; ?>
                    <div class="flex justify-center gap-8 text-lg">
                        <?php if (!empty($currentTask['price'])): ?>
                        <div>
                            <span class="text-gray-400">Amount: </span>
                            <span class="text-white font-bold">USD <?php echo number_format($currentTask['price'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-gray-400">Profit: </span>
                            <span class="text-green-400 font-bold">USD <?php echo number_format($currentTask['earning_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 text-center">
                <p class="text-gray-300 text-lg">
                    Click submit to complete this task and earn your reward
                </p>
                <p class="text-green-400 font-bold text-xl mt-2">
                    Earn $<?php echo number_format($currentTask['earning_amount'], 2); ?> for this task
                </p>
            </div>

            <?php if (!$isCompleted): ?>
            <form method="POST" action="">
                <input type="hidden" name="task_id" value="<?php echo $currentTask['id']; ?>" />
                <button
                    type="submit"
                    name="submit_task"
                    class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-5 rounded-xl font-bold hover:from-green-700 hover:to-emerald-700 transition-all text-xl shadow-lg"
                >
                    Submit Task
                </button>
            </form>
            <?php else: ?>
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-6 text-center">
                <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-green-400 font-semibold text-lg">
                    Task Completed Successfully!
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- No Tasks Available -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-3xl shadow-2xl p-8 text-center border border-slate-700">
            <h3 class="text-xl font-semibold text-gray-300 mb-2">
                No tasks available
            </h3>
            <p class="text-gray-500">
                Please check back later or contact support
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
