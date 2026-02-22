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
$isAdmin = $auth->isAdmin($userId);

// Get user data
$database = new Database();
$db = $database->getConnection();

// Get wallet
$stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$wallet = $stmt->fetch();

if (!$wallet) {
    // Create wallet if doesn't exist
    $walletId = generateUUID();
    $stmt = $db->prepare("INSERT INTO wallets (id, user_id, balance, total_earnings) VALUES (:id, :user_id, 0.00, 0.00)");
    $stmt->execute([':id' => $walletId, ':user_id' => $userId]);
    $wallet = ['id' => $walletId, 'balance' => 0, 'total_earnings' => 0, 'can_withdraw' => false];
}

// Get VIP tier
$stmt = $db->prepare("SELECT vt.* FROM user_profiles up LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id WHERE up.id = :user_id");
$stmt->execute([':user_id' => $userId]);
$vipTier = $stmt->fetch();

// Get daily earnings
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM daily_earnings WHERE user_id = :user_id AND date = :date");
$stmt->execute([':user_id' => $userId, ':date' => $today]);
$dailyEarnings = $stmt->fetch();

if (!$dailyEarnings) {
    $dailyEarnings = ['tasks_completed' => 0, 'total_earnings' => 0, 'can_withdraw' => false];
}

// Get transactions
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20");
$stmt->execute([':user_id' => $userId]);
$transactions = $stmt->fetchAll();

// Handle wallet actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $amount = floatval($_POST['amount'] ?? 0);

    if ($amount > 0) {
        if ($action === 'withdraw') {
            if ($amount <= ($wallet['total_earnings'] ?? 0)) {
                $transactionId = generateUUID();
                $stmt = $db->prepare("INSERT INTO transactions (id, user_id, wallet_id, type, amount, status, description) VALUES (:id, :user_id, :wallet_id, 'withdrawal', :amount, 'pending', :description)");
                $stmt->execute([
                    ':id' => $transactionId,
                    ':user_id' => $userId,
                    ':wallet_id' => $wallet['id'],
                    ':amount' => $amount,
                    ':description' => "Withdrawal of $" . number_format($amount, 2)
                ]);

                $stmt = $db->prepare("UPDATE wallets SET total_earnings = total_earnings - :amount WHERE user_id = :user_id");
                $stmt->execute([':amount' => $amount, ':user_id' => $userId]);

                $_SESSION['message'] = 'Withdrawal request submitted successfully!';
                redirect('dashboard.php');
            }
        } elseif ($action === 'deposit') {
            $transactionId = generateUUID();
            $stmt = $db->prepare("INSERT INTO transactions (id, user_id, wallet_id, type, amount, status, description) VALUES (:id, :user_id, :wallet_id, 'deposit', :amount, 'pending', :description)");
            $stmt->execute([
                ':id' => $transactionId,
                ':user_id' => $userId,
                ':wallet_id' => $wallet['id'],
                ':amount' => $amount,
                ':description' => "Deposit of $" . number_format($amount, 2)
            ]);

            $_SESSION['message'] = 'Deposit request submitted successfully!';
            redirect('dashboard.php');
        }
    }
}

$heroImages = ['/AI.jpg', '/AI2.jpg', '/AI3.jpg', '/AI4.jpg', '/AI5.jpg'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <style>
        @keyframes slide-fade { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes slide-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes zoom-in { from { opacity: 0; transform: scale(1.1); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulse-glow { 0%, 100% { box-shadow: 0 0 20px rgba(251, 191, 36, 0.5); } 50% { box-shadow: 0 0 30px rgba(251, 191, 36, 0.8); } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        .animate-slide-fade { animation: slide-fade 0.6s ease-out; }
        .animate-slide-up { animation: slide-up 0.6s ease-out; }
        .animate-zoom-in { animation: zoom-in 0.8s ease-out; }
        .animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .animate-shimmer { background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); background-size: 1000px 100%; animation: shimmer 2s infinite; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/50 backdrop-blur-sm shadow-sm border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center">
                        <span class="text-white font-bold text-lg">
                            <?php echo strtoupper(substr($profile['full_name'] ?? 'U', 0, 1)); ?>
                        </span>
                    </div>
                    <span class="text-white font-semibold">Personal</span>
                </div>

                <button onclick="toggleMenu()" class="text-white p-2 hover:bg-slate-700 rounded-lg transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Menu Dropdown -->
    <div id="menuDropdown" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" onclick="toggleMenu()">
        <div class="absolute right-0 top-16 bg-slate-800 rounded-lg shadow-2xl m-4 p-4 min-w-[200px]" onclick="event.stopPropagation()">
            <a href="logout.php" class="w-full flex items-center space-x-2 text-white hover:bg-slate-700 p-3 rounded-lg transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Logout</span>
            </a>
            <?php if ($isAdmin): ?>
            <a href="admin.php" class="w-full flex items-center space-x-2 text-white hover:bg-slate-700 p-3 rounded-lg transition-all mt-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Admin Panel</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Hero Image Carousel -->
        <div class="relative rounded-3xl shadow-2xl overflow-hidden mb-6 group">
            <div class="relative h-80 overflow-hidden bg-slate-800">
                <?php foreach ($heroImages as $index => $image): ?>
                <div class="carousel-item absolute inset-0 transition-all duration-700 ease-out <?php echo $index === 0 ? 'opacity-100 translate-x-0 z-10' : 'opacity-0 translate-x-full z-0'; ?>" data-index="<?php echo $index; ?>">
                    <img src="<?php echo $image; ?>" alt="AI Automation <?php echo $index + 1; ?>" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent"></div>
                </div>
                <?php endforeach; ?>

                <button onclick="prevImage()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white p-3 rounded-full transition-all duration-300 hover:scale-110 z-20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <button onclick="nextImage()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white p-3 rounded-full transition-all duration-300 hover:scale-110 z-20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <div class="absolute bottom-0 left-0 right-0 p-6 z-10">
                    <div class="flex justify-center space-x-2 mb-4">
                        <?php foreach ($heroImages as $index => $image): ?>
                        <button onclick="goToImage(<?php echo $index; ?>)" class="carousel-dot h-1.5 rounded-full transition-all duration-300 <?php echo $index === 0 ? 'bg-white w-8' : 'bg-white/40 hover:bg-white/60 w-1.5'; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Section -->
        <div class="mb-6 animate-slide-fade">
            <h2 class="text-2xl font-bold text-white mb-2 flex items-center">
                Welcome, <?php echo htmlspecialchars($profile['full_name'] ?? 'User'); ?>
                <div class="ml-3 w-10 h-10 rounded-full bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center animate-pulse-glow">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
            </h2>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-5 gap-3 mb-8">
            <button onclick="showCustomerSupport()" class="flex flex-col items-center justify-center bg-slate-800/50 backdrop-blur-sm rounded-xl p-3 border border-slate-700 hover:bg-slate-700/50 hover:scale-105 hover:shadow-xl transition-all duration-300 animate-slide-up" style="animation-delay: 0.1s;">
                <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center mb-2">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="text-white text-xs font-medium text-center">Customer Care</span>
            </button>

            <button class="flex flex-col items-center justify-center bg-slate-800/50 backdrop-blur-sm rounded-xl p-3 border border-slate-700 hover:bg-slate-700/50 hover:scale-105 hover:shadow-xl transition-all duration-300 animate-slide-up" style="animation-delay: 0.2s;">
                <div class="w-12 h-12 rounded-full bg-violet-600 flex items-center justify-center mb-2">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <span class="text-white text-xs font-medium text-center">Certificate</span>
            </button>

            <button onclick="showPaymentMethods()" class="flex flex-col items-center justify-center bg-slate-800/50 backdrop-blur-sm rounded-xl p-3 border border-slate-700 hover:bg-slate-700/50 hover:scale-105 hover:shadow-xl transition-all duration-300 animate-slide-up" style="animation-delay: 0.25s;">
                <div class="w-12 h-12 rounded-full bg-cyan-600 flex items-center justify-center mb-2">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <span class="text-white text-xs font-medium text-center">Payment Method</span>
            </button>

            <button onclick="showFAQ()" class="flex flex-col items-center justify-center bg-slate-800/50 backdrop-blur-sm rounded-xl p-3 border border-slate-700 hover:bg-slate-700/50 hover:scale-105 hover:shadow-xl transition-all duration-300 animate-slide-up" style="animation-delay: 0.3s;">
                <div class="w-12 h-12 rounded-full bg-green-600 flex items-center justify-center mb-2">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-white text-xs font-medium text-center">FAQ</span>
            </button>

            <button onclick="showAboutUs()" class="flex flex-col items-center justify-center bg-slate-800/50 backdrop-blur-sm rounded-xl p-3 border border-slate-700 hover:bg-slate-700/50 hover:scale-105 hover:shadow-xl transition-all duration-300 animate-slide-up" style="animation-delay: 0.35s;">
                <div class="w-12 h-12 rounded-full bg-orange-600 flex items-center justify-center mb-2">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-white text-xs font-medium text-center">About Us</span>
            </button>
        </div>

        <!-- Membership Level -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-white">Membership Level</h3>
                <button class="text-blue-400 text-sm hover:text-blue-300">View More</button>
            </div>

            <div class="flex items-center gap-3 overflow-x-auto pb-2">
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-3 border border-slate-700 min-w-fit">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm"><?php echo htmlspecialchars($vipTier['name'] ?? 'VIP 1'); ?></h4>
                            <p class="text-gray-400 text-xs"><?php echo $vipTier['max_tasks_per_day'] ?? 35; ?> Tasks</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <span class="text-xs px-3 py-1 rounded-full bg-green-600/20 text-green-400">Active</span>
                    </div>
                </div>

                <?php for ($i = 2; $i <= 5; $i++): ?>
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-3 border border-slate-700 min-w-fit opacity-50">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-gray-400 to-gray-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm">VIP <?php echo $i; ?></h4>
                            <p class="text-gray-400 text-xs"><?php echo 35 + ($i - 1) * 15; ?> Tasks</p>
                        </div>
                    </div>
                    <button onclick="showCustomerSupport()" class="w-full bg-blue-600 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-blue-700 transition-all text-xs">
                        Unlock
                    </button>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Main Earnings Card -->
        <div class="bg-gradient-to-br from-blue-600 to-cyan-600 rounded-2xl shadow-xl p-8 mb-6 animate-zoom-in relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-cyan-400/20 animate-shimmer"></div>
            <div class="text-center relative z-10">
                <h3 class="text-white text-2xl font-bold mb-4 animate-slide-fade">Ready to Start Earning?</h3>
                <p class="text-blue-100 mb-2">Current Balance</p>
                <p class="text-5xl font-bold text-white mb-6 animate-pulse">
                    $<?php echo number_format($wallet['balance'] ?? 0, 2); ?>
                </p>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-white/10 rounded-lg p-4 backdrop-blur-sm hover:bg-white/20 transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.2s;">
                        <p class="text-blue-100 text-sm mb-1">Today's Earnings</p>
                        <p class="text-2xl font-bold text-white">
                            $<?php echo number_format($dailyEarnings['total_earnings'] ?? 0, 2); ?>
                        </p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4 backdrop-blur-sm hover:bg-white/20 transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.3s;">
                        <p class="text-blue-100 text-sm mb-1">Tasks Completed</p>
                        <p class="text-2xl font-bold text-white">
                            <?php echo $dailyEarnings['tasks_completed'] ?? 0; ?> / <?php echo $vipTier['max_tasks_per_day'] ?? 35; ?>
                        </p>
                    </div>
                </div>
                <a href="tasks.php" class="w-full block bg-white text-blue-600 py-4 rounded-xl font-bold text-lg hover:bg-blue-50 hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-2xl animate-slide-up" style="animation-delay: 0.4s;">
                    Start Working on Tasks
                </a>

                <div class="bg-slate-800/60 backdrop-blur-md rounded-2xl p-4 border border-white/10 mt-4 animate-slide-up" style="animation-delay: 0.5s;">
                    <p class="text-gray-200 text-sm text-center leading-relaxed">
                        Dear user, please note that our platform operates 24/7 with automated task distribution. Complete your daily tasks to unlock withdrawal privileges.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-slate-800/90 backdrop-blur-sm border-t border-slate-700 py-4 z-40">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-around items-center">
                <button class="flex flex-col items-center text-blue-400">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-xs font-medium">Home</span>
                </button>
                <a href="tasks.php" class="flex flex-col items-center text-gray-400 hover:text-white transition-all">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span class="text-xs font-medium">Start</span>
                </a>
                <button onclick="showWalletChoice()" class="flex flex-col items-center text-gray-400 hover:text-white transition-all">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span class="text-xs font-medium">Wallet</span>
                </button>
                <button onclick="showRecords()" class="flex flex-col items-center text-gray-400 hover:text-white transition-all">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-xs font-medium">Record</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Modals will be added via JavaScript -->
    <div id="modalContainer"></div>

    <script>
        let currentImageIndex = 0;
        const totalImages = <?php echo count($heroImages); ?>;

        function nextImage() {
            currentImageIndex = (currentImageIndex + 1) % totalImages;
            updateCarousel();
        }

        function prevImage() {
            currentImageIndex = (currentImageIndex - 1 + totalImages) % totalImages;
            updateCarousel();
        }

        function goToImage(index) {
            currentImageIndex = index;
            updateCarousel();
        }

        function updateCarousel() {
            const items = document.querySelectorAll('.carousel-item');
            const dots = document.querySelectorAll('.carousel-dot');

            items.forEach((item, index) => {
                if (index === currentImageIndex) {
                    item.classList.remove('opacity-0', 'translate-x-full', '-translate-x-full', 'z-0');
                    item.classList.add('opacity-100', 'translate-x-0', 'z-10');
                } else if (index < currentImageIndex) {
                    item.classList.remove('opacity-100', 'translate-x-0', 'translate-x-full', 'z-10');
                    item.classList.add('opacity-0', '-translate-x-full', 'z-0');
                } else {
                    item.classList.remove('opacity-100', 'translate-x-0', '-translate-x-full', 'z-10');
                    item.classList.add('opacity-0', 'translate-x-full', 'z-0');
                }
            });

            dots.forEach((dot, index) => {
                if (index === currentImageIndex) {
                    dot.classList.remove('bg-white/40', 'w-1.5');
                    dot.classList.add('bg-white', 'w-8');
                } else {
                    dot.classList.remove('bg-white', 'w-8');
                    dot.classList.add('bg-white/40', 'w-1.5');
                }
            });
        }

        // Auto-advance carousel
        setInterval(nextImage, 5000);

        function toggleMenu() {
            const menu = document.getElementById('menuDropdown');
            menu.classList.toggle('hidden');
        }

        function showCustomerSupport() {
            alert('Customer Support\\n\\nEmail: support@earningsllc.com\\nPhone: +1 (555) 123-4567\\n\\nOur team is available 24/7 to assist you.');
        }

        function showPaymentMethods() {
            window.location.href = 'payment_methods.php';
        }

        function showWalletChoice() {
            const modal = `
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4" onclick="closeModal(event)">
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 max-w-md w-full border border-white/10 shadow-2xl" onclick="event.stopPropagation()">
                        <div class="text-center mb-8">
                            <div class="bg-gradient-to-br from-blue-600 to-cyan-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-white mb-2">Wallet Options</h2>
                            <p class="text-gray-400">Choose an action</p>
                        </div>

                        <div class="space-y-4 mb-6">
                            <button onclick="showWalletModal('deposit')" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all shadow-lg flex items-center justify-between group">
                                <span class="text-lg">Deposit Funds</span>
                                <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                            </button>

                            <button onclick="showWalletModal('withdraw')" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-semibold py-4 px-6 rounded-xl transition-all shadow-lg flex items-center justify-between group">
                                <span class="text-lg">Withdraw Funds</span>
                                <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                            </button>

                            <button onclick="showPaymentMethods()" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold py-4 px-6 rounded-xl transition-all shadow-lg flex items-center justify-between group">
                                <span class="text-lg">Payment Method</span>
                                <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </button>
                        </div>

                        <button onclick="closeModal()" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-6 rounded-xl transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('modalContainer').innerHTML = modal;
        }

        function showWalletModal(action) {
            const canWithdraw = <?php echo $wallet['can_withdraw'] ?? 0 ? 'true' : 'false'; ?>;

            if (action === 'withdraw' && !canWithdraw) {
                alert('Complete your daily tasks to unlock withdrawal privileges.');
                return;
            }

            const modal = `
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4" onclick="closeModal(event)">
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 max-w-md w-full border border-white/10 shadow-2xl" onclick="event.stopPropagation()">
                        <div class="text-center mb-6">
                            <div class="bg-gradient-to-br from-blue-600 to-cyan-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-white mb-2">
                                ${action === 'deposit' ? 'Deposit Funds' : 'Withdraw Funds'}
                            </h2>
                            <p class="text-gray-400">
                                ${action === 'deposit' ? 'Add funds to your wallet' : 'Withdraw your earnings'}
                            </p>
                        </div>

                        <form method="POST" action="" class="mb-6">
                            <input type="hidden" name="action" value="${action}" />

                            <div class="mb-6">
                                <label class="block text-gray-300 text-sm font-semibold mb-3">Amount (USD)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl">$</span>
                                    <input
                                        type="number"
                                        name="amount"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-12 py-4 text-white text-xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                ${action === 'withdraw' ? `<p class="text-sm text-gray-400 mt-2">Available balance: $${<?php echo $wallet['total_earnings'] ?? 0; ?>.toFixed(2)}</p>` : ''}
                            </div>

                            <div class="bg-slate-800/50 border border-white/10 rounded-xl p-4 mb-6">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-400">Transaction Fee</span>
                                    <span class="text-white font-semibold">$0.00</span>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <button type="button" onclick="closeModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-6 rounded-xl transition-all">
                                    Cancel
                                </button>
                                <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-semibold py-3 px-6 rounded-xl transition-all shadow-lg">
                                    ${action === 'deposit' ? 'Deposit' : 'Withdraw'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('modalContainer').innerHTML = modal;
        }

        function showRecords() {
            const transactions = <?php echo json_encode($transactions); ?>;

            let transactionHTML = '';
            if (transactions.length === 0) {
                transactionHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-400">No transactions yet</p>
                    </div>
                `;
            } else {
                transactionHTML = '<div class="space-y-3">';
                transactions.forEach(transaction => {
                    const date = new Date(transaction.created_at);
                    const bgColor = transaction.type === 'earnings' ? 'bg-green-600' :
                                   transaction.type === 'withdrawal' ? 'bg-red-600' : 'bg-blue-600';
                    const textColor = transaction.type === 'earnings' ? 'text-green-400' :
                                     transaction.type === 'withdrawal' ? 'text-red-400' : 'text-blue-400';
                    const statusColor = transaction.status === 'completed' ? 'bg-green-600/20 text-green-400' :
                                       transaction.status === 'pending' ? 'bg-yellow-600/20 text-yellow-400' :
                                       'bg-red-600/20 text-red-400';
                    const sign = transaction.type === 'withdrawal' ? '-' : '+';

                    transactionHTML += `
                        <div class="bg-slate-700/50 rounded-xl p-4 border border-slate-600 hover:bg-slate-700/70 transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3">
                                    <div class="${bgColor} w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${transaction.type === 'earnings' ? 'M19 14l-7 7m0 0l-7-7m7 7V3' : 'M5 10l7-7m0 0l7 7m-7-7v18'}"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold text-sm">
                                            ${transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1)}
                                        </h4>
                                        <p class="text-gray-400 text-xs mt-1">${transaction.description}</p>
                                        <p class="text-gray-500 text-xs mt-1">${date.toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'})}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="${textColor} font-bold text-lg">${sign}$${parseFloat(transaction.amount).toFixed(2)}</p>
                                    <span class="text-xs px-2 py-1 rounded-full ${statusColor}">${transaction.status}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                transactionHTML += '</div>';
            }

            const modal = `
                <div class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" onclick="closeModal(event)">
                    <div class="bg-slate-800 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden border border-slate-700" onclick="event.stopPropagation()">
                        <div class="p-6 border-b border-slate-700 flex justify-between items-center">
                            <h2 class="text-2xl font-bold text-white">Transaction Records</h2>
                            <button onclick="closeModal()" class="text-gray-400 hover:text-white transition-all">
                                <span class="text-2xl">&times;</span>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                            ${transactionHTML}
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('modalContainer').innerHTML = modal;
        }

        function showFAQ() {
            const modal = `
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto" onclick="closeModal(event)">
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 max-w-3xl w-full border border-white/10 shadow-2xl my-8" onclick="event.stopPropagation()">
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-green-600 to-emerald-600 w-12 h-12 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h2 class="text-3xl font-bold text-white">Frequently Asked Questions</h2>
                            </div>
                            <button onclick="closeModal()" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                            <div class="bg-slate-800/50 rounded-2xl p-6 border border-white/10">
                                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                                    <span class="bg-blue-600 w-8 h-8 rounded-full flex items-center justify-center text-white font-bold mr-3 text-sm">1</span>
                                    Deposit Guide
                                </h3>
                                <div class="text-gray-300 space-y-3 text-sm leading-relaxed">
                                    <p>Click the "Online Customer Support" button and provide your username or registered mobile phone number to apply for a deposit address. After deposit using the address provided by customer support, please submit a screenshot of the successful deposit so that we can verify the payment with online customer support.</p>
                                    <p>To ensure fast deposit, please ensure that the remittance address and amount match those provided by customer support. If you encounter any problems during the deposit process that you cannot resolve, please contact customer support promptly.</p>
                                    <p>Due to the large amount of information, please confirm your deposit wallet address with customer support again before deposit. Our company may change cryptocurrency deposit addresses from time to time.</p>
                                    <p>Our company will launch a series of promotional activities from time to time. We remind users to choose the most suitable deposit method according to their own circumstances to avoid serious consequences. If you have any questions, please click on online customer support for consultation!</p>
                                </div>
                            </div>

                            <div class="bg-slate-800/50 rounded-2xl p-6 border border-white/10">
                                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                                    <span class="bg-blue-600 w-8 h-8 rounded-full flex items-center justify-center text-white font-bold mr-3 text-sm">2</span>
                                    How to Upload Data
                                </h3>
                                <div class="text-gray-300 space-y-3 text-sm leading-relaxed">
                                    <p>After recharging, you can begin uploading your application. Go to the "Upload Now" page, click the "Upload Now" button, wait for the system to receive your application, and submit your data when the data submission pop-up appears. You will receive a corresponding rebate for every 100% of the data completed.</p>
                                    <p>When users perform application data optimization tasks, the platform will provide real transaction records of the data generated by the system's AI to ensure that the uploaded data after optimization is authentic, valid, and legal.</p>
                                    <ul class="list-disc list-inside space-y-2 ml-4">
                                        <li>Ordinary task receive a 0.5% rebate for each completed application.</li>
                                        <li>Premium task receive a 10%-20% rebate.</li>
                                        <li>All applications cannot be cancelled or redeemed.</li>
                                    </ul>
                                    <p>After completing the task, your account balance will be fully refunded. You can withdraw your earnings after completing the daily task.</p>
                                    <p>The platform will randomly assign application data as daily tasks based on your VIP level and account activity.</p>
                                </div>
                            </div>
                        </div>

                        <button onclick="closeModal()" class="w-full mt-6 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-all">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('modalContainer').innerHTML = modal;
        }

        function showAboutUs() {
            const modal = `
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto" onclick="closeModal(event)">
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 max-w-5xl w-full border border-white/10 shadow-2xl my-8" onclick="event.stopPropagation()">
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-orange-600 to-red-600 w-12 h-12 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h2 class="text-3xl font-bold text-white">About Us</h2>
                            </div>
                            <button onclick="closeModal()" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="bg-slate-800/50 rounded-2xl p-6 border border-white/10">
                                    <h3 class="text-2xl font-bold text-blue-400 mb-4">Job Requirements</h3>
                                    <h4 class="text-xl font-bold text-white mb-3">We are all 100% Remote</h4>
                                    <p class="text-gray-300 leading-relaxed mb-4">We are a remote-first company, and we want to work with people where working remotely is important to them for some reason. Maybe you want the freedom to build an exciting new career, or be there when your baby takes their first steps, or to move to a sunnier climate where you can surf and hike. We have got your back!</p>
                                    <h4 class="text-xl font-bold text-white mb-3">Job Description: Growth Marketing Specialist</h4>
                                    <p class="text-gray-300 leading-relaxed">Our company is a leading B2B SaaS provider for the self-storage industry, serving clients in over 40 countries worldwide. Our company helps marketing businesses automate operations, streamline processes, and grow the business. As a fully remote company, our team is spread across multiple continents.</p>
                                </div>

                                <div class="bg-slate-800/50 rounded-2xl p-6 border border-white/10">
                                    <h3 class="text-2xl font-bold text-blue-400 mb-4">Job Salary</h3>
                                    <p class="text-gray-300 mb-4 font-semibold">Our salary structure is: daily commission income + base salary</p>

                                    <div class="space-y-4">
                                        <div>
                                            <h4 class="font-bold text-lg mb-2 text-emerald-400">A. Daily commission income:</h4>
                                            <p class="text-gray-300 leading-relaxed text-sm mb-2">For every set of promotion tasks completed, you will receive corresponding commission income. You must complete 2 sets of 38 app promotion projects [76] every day to withdraw all funds + commission income from your work account</p>
                                            <p class="text-gray-300 leading-relaxed text-sm">You need to complete 2 sets of 38 brand rating tasks in your account every day, and then you can withdraw all funds + commission income from your work account.</p>
                                        </div>

                                        <div>
                                            <h4 class="font-bold text-lg mb-2 text-emerald-400">B. Basic salary:</h4>
                                            <p class="text-gray-300 leading-relaxed text-sm mb-3">Basic salary is paid according to different stages of consecutive work record days. Two sets of rating tasks need to be completed every day to fully record one work record day.</p>
                                            <ul class="space-y-2 text-gray-300 text-sm">
                                                <li class="flex items-start">
                                                    <span class="mr-2 text-emerald-400">•</span>
                                                    <span>Earn $750 for 5 consecutive days of work</span>
                                                </li>
                                                <li class="flex items-start">
                                                    <span class="mr-2 text-emerald-400">•</span>
                                                    <span>Earn $1550 for 14 consecutive days of work</span>
                                                </li>
                                                <li class="flex items-start">
                                                    <span class="mr-2 text-emerald-400">•</span>
                                                    <span>Earn $3000 for 30 consecutive days of work</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-800/50 rounded-2xl p-6 border border-white/10">
                                <h3 class="text-2xl font-bold text-blue-400 mb-4">Earnings Structure</h3>
                                <p class="text-gray-300 mb-4">Earn money by identifying brand names from images</p>

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="bg-slate-700/50 rounded-xl p-5 border border-white/5">
                                        <h4 class="font-bold text-xl mb-3 text-emerald-400">A. Brand Identification Tasks</h4>
                                        <p class="text-gray-300 leading-relaxed text-sm">Complete brand identification tasks daily based on your VIP level. VIP 1 members complete 35 tasks per day and earn approximately $75. Each task pays a variable amount. Complete all tasks to unlock withdrawals.</p>
                                    </div>

                                    <div class="bg-slate-700/50 rounded-xl p-5 border border-white/5">
                                        <h4 class="font-bold text-xl mb-3 text-emerald-400">B. Weekly Earnings Limit</h4>
                                        <p class="text-gray-300 leading-relaxed text-sm">Weekly earning limits increase with VIP level: VIP 1 earns $100+, VIP 2 earns $500+, VIP 3 earns $1000+, VIP 4 earns $2000+, and VIP 5 earns $5000+ per week. Upgrade for higher earnings.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button onclick="closeModal()" class="w-full mt-6 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white font-semibold py-3 px-6 rounded-xl transition-all">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('modalContainer').innerHTML = modal;
        }

        function closeModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('modalContainer').innerHTML = '';
        }

        <?php if (isset($_SESSION['message'])): ?>
        alert('<?php echo addslashes($_SESSION['message']); ?>');
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
