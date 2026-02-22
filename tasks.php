<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/login');
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
$stmt = $db->prepare("SELECT * FROM admin_tasks WHERE vip_level_required <= :vip_level ORDER BY RAND()");
$stmt->execute([':vip_level' => $vipTier['level']]);
$allTasks = $stmt->fetchAll();

// Limit to 35 tasks randomly
$tasks = array_slice($allTasks, 0, min(35, count($allTasks)));

// Map product images to tasks
$updatedTasks = [];
foreach ($tasks as $index => $task) {
    if (count($productImages) > 0) {
        $productImage = $productImages[$index % count($productImages)];
        $task['image_url'] = $productImage['image_url'] ?? $task['image_url'];
        $task['brand_name'] = $productImage['brand_name'] ?? $task['brand_name'];
        $task['product_name'] = $productImage['product_name'] ?? null;
        $task['price'] = $productImage['price'] ?? null;
    }
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
$allTasksCompleted = true;
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
        $allTasksCompleted = false;
        break;
    }
}

// Check if user completed all 35 tasks
$showCompletionPopup = false;
if (count($submissions) >= 35 && count($updatedTasks) >= 35) {
    $showCompletionPopup = true;
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
                ':description' => "Brand identification task completed"
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
            redirect('/tasks');
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
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <?php if ($showPreloader): ?>
    <div id="preloader" class="fixed inset-0 bg-white z-[9999] flex items-center justify-center">
        <div class="text-center">
            <div class="relative w-40 h-40 mx-auto mb-8">
                <div class="grid grid-cols-2 gap-3 w-full h-full">
                    <div class="square-loader bg-emerald-500 rounded-lg shadow-lg animate-square-1"></div>
                    <div class="square-loader bg-emerald-500 rounded-lg shadow-lg animate-square-2"></div>
                    <div class="square-loader bg-emerald-500 rounded-lg shadow-lg animate-square-3"></div>
                    <div class="square-loader bg-emerald-500 rounded-lg shadow-lg animate-square-4"></div>
                </div>
            </div>
            <p class="text-gray-900 text-3xl font-bold mb-2 animate-pulse">Loading Tasks</p>
            <p class="text-gray-600 text-lg">Preparing your workspace...</p>
        </div>
    </div>
    <style>
        @keyframes squareScale1 {
            0% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            20% { transform: scale(0.5) rotate(45deg); opacity: 0.5; }
            40% { transform: scale(1) rotate(90deg); opacity: 1; }
            60% { transform: scale(1.1) rotate(180deg); opacity: 1; }
            80% { transform: scale(1) rotate(270deg); opacity: 1; }
            100% { transform: scale(1) rotate(360deg); opacity: 1; }
        }
        @keyframes squareScale2 {
            0% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            20% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            40% { transform: scale(0.5) rotate(45deg); opacity: 0.5; }
            60% { transform: scale(1) rotate(90deg); opacity: 1; }
            80% { transform: scale(1.1) rotate(180deg); opacity: 1; }
            100% { transform: scale(1) rotate(270deg); opacity: 1; }
        }
        @keyframes squareScale3 {
            0%, 20% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            40% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            60% { transform: scale(0.5) rotate(45deg); opacity: 0.5; }
            80% { transform: scale(1) rotate(90deg); opacity: 1; }
            100% { transform: scale(1.1) rotate(180deg); opacity: 1; }
        }
        @keyframes squareScale4 {
            0%, 40% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            60% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            80% { transform: scale(0.5) rotate(45deg); opacity: 0.5; }
            100% { transform: scale(1) rotate(90deg); opacity: 1; }
        }
        .animate-square-1 { animation: squareScale1 2s ease-in-out forwards; }
        .animate-square-2 { animation: squareScale2 2s ease-in-out forwards; }
        .animate-square-3 { animation: squareScale3 2s ease-in-out forwards; }
        .animate-square-4 { animation: squareScale4 2s ease-in-out forwards; }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
    <script>
        setTimeout(function() {
            const preloader = document.getElementById('preloader');
            preloader.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(function() {
                preloader.style.display = 'none';
            }, 500);
        }, 4000);
    </script>
    <?php endif; ?>

    <!-- Header -->
    <header class="bg-slate-800/50 backdrop-blur-sm shadow-sm border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <a href="/dashboard" class="flex items-center space-x-2 text-white hover:text-blue-400">
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
            <a href="/dashboard" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700">
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
                    Identify This Brand
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

    <?php if ($showCompletionPopup): ?>
    <div id="completionModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl max-w-lg w-full p-8 border-2 border-emerald-500/50 shadow-2xl animate-pulse-slow">
            <div class="text-center">
                <div class="mb-6">
                    <svg class="w-20 h-20 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <h2 class="text-3xl font-bold text-white mb-4">Congratulations!</h2>

                <p class="text-xl text-emerald-400 font-semibold mb-4">
                    You've completed all 35 tasks!
                </p>

                <div class="bg-slate-700/50 rounded-2xl p-6 mb-6 border border-slate-600">
                    <p class="text-gray-300 text-lg mb-4">
                        To continue earning and access more tasks, please contact our support team on Telegram.
                    </p>

                    <div class="space-y-3">
                        <a href="https://t.me/EARNINGSLLCONLINECS1" target="_blank" class="flex items-center justify-center space-x-3 text-white hover:text-emerald-400 transition-colors bg-slate-800/50 rounded-lg p-3 border border-slate-600 hover:border-emerald-500">
                            <svg class="w-6 h-6 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                            </svg>
                            <span class="text-lg font-semibold">@EARNINGSLLCONLINECS1</span>
                        </a>

                        <div class="flex items-center justify-center space-x-3 text-gray-400">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">Support Available 24/7</span>
                        </div>
                    </div>
                </div>

                <button onclick="closeCompletionModal()" class="w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-bold py-4 rounded-xl transition-all shadow-lg text-lg">
                    Continue to Dashboard
                </button>
            </div>
        </div>
    </div>

    <script>
        function closeCompletionModal() {
            document.getElementById('completionModal').style.display = 'none';
        }
    </script>
    <?php endif; ?>
</body>
</html>
