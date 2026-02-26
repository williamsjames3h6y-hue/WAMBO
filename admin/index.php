<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    redirect('/admin/login');
}

$auth = new Auth();
$userId = getCurrentUserId();
$isAdmin = $auth->isAdmin($userId);

if (!$isAdmin) {
    redirect('/admin/login');
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM admin_users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$adminUser = $stmt->fetch();
$adminRole = $adminUser['role'] ?? 'admin';

$activeTab = $_GET['tab'] ?? 'stats';

$stmt = $db->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $stmt->fetch()['total_users'];

$stmt = $db->query("SELECT COUNT(*) as active_users FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$activeUsers = $stmt->fetch()['active_users'];

$stmt = $db->query("SELECT COUNT(*) as total_tasks FROM admin_tasks");
$totalTasks = $stmt->fetch()['total_tasks'];

$stmt = $db->query("SELECT SUM(balance) as total_balance FROM wallets");
$totalBalance = $stmt->fetch()['total_balance'] ?? 0;

$stmt = $db->query("SELECT SUM(total_earnings) as total_earnings FROM wallets");
$totalEarnings = $stmt->fetch()['total_earnings'] ?? 0;

$stmt = $db->query("SELECT u.*, up.full_name, up.vip_tier_id, vt.name as vip_name, vt.level as vip_level, w.balance, w.total_earnings FROM users u LEFT JOIN user_profiles up ON u.id = up.id LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id LEFT JOIN wallets w ON u.id = w.user_id ORDER BY u.created_at DESC LIMIT 100");
$users = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM admin_tasks ORDER BY task_order ASC");
$tasks = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM vip_tiers ORDER BY level ASC");
$vipTiers = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-yellow-700 via-amber-600 to-yellow-800">
    <nav class="bg-yellow-900/50 backdrop-blur-sm border-b border-yellow-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-emerald-500 to-cyan-500 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Admin Panel</h1>
                        <p class="text-xs text-emerald-400"><?php echo strtoupper(str_replace('_', ' ', $adminRole)); ?></p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                    <a href="/logout" class="flex items-center space-x-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 px-4 py-2 rounded-lg transition-colors border border-red-500/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-yellow-900/30 backdrop-blur-sm rounded-xl border border-yellow-600 overflow-hidden">
            <div class="flex border-b border-yellow-600 overflow-x-auto">
                <a href="?tab=stats" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all whitespace-nowrap <?php echo $activeTab === 'stats' ? 'bg-gradient-to-r from-yellow-500/30 to-amber-500/30 text-yellow-200 border-b-2 border-yellow-400' : 'text-yellow-100 hover:text-white hover:bg-yellow-800/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="font-semibold">Dashboard</span>
                </a>
                <a href="?tab=users" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all whitespace-nowrap <?php echo $activeTab === 'users' ? 'bg-gradient-to-r from-yellow-500/30 to-amber-500/30 text-yellow-200 border-b-2 border-yellow-400' : 'text-yellow-100 hover:text-white hover:bg-yellow-800/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-semibold">Users</span>
                </a>
                <a href="?tab=tasks" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all whitespace-nowrap <?php echo $activeTab === 'tasks' ? 'bg-gradient-to-r from-yellow-500/30 to-amber-500/30 text-yellow-200 border-b-2 border-yellow-400' : 'text-yellow-100 hover:text-white hover:bg-yellow-800/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="font-semibold">Tasks</span>
                </a>
                <a href="?tab=settings" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all whitespace-nowrap <?php echo $activeTab === 'settings' ? 'bg-gradient-to-r from-yellow-500/30 to-amber-500/30 text-yellow-200 border-b-2 border-yellow-400' : 'text-yellow-100 hover:text-white hover:bg-yellow-800/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-semibold">Settings</span>
                </a>
            </div>

            <div class="p-6">
                <?php include __DIR__ . '/partials/' . $activeTab . '.php'; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/modals.php'; ?>
    <script src="/admin/js/admin.js"></script>
</body>
</html>
