<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$auth = new Auth();
$userId = getCurrentUserId();
$isAdmin = $auth->isAdmin($userId);

// Redirect if not admin
if (!$isAdmin) {
    redirect('dashboard.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get admin role
$stmt = $db->prepare("SELECT * FROM admin_users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$adminUser = $stmt->fetch();
$adminRole = $adminUser['role'] ?? 'admin';

// Get active tab
$activeTab = $_GET['tab'] ?? 'stats';

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $stmt->fetch()['total_users'];

$stmt = $db->query("SELECT COUNT(*) as active_users FROM users WHERE last_sign_in_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$activeUsers = $stmt->fetch()['active_users'];

$stmt = $db->query("SELECT COUNT(*) as total_products FROM product_images WHERE is_active = TRUE");
$totalProducts = $stmt->fetch()['total_products'];

$stmt = $db->query("SELECT SUM(balance) as total_balance FROM wallets");
$totalBalance = $stmt->fetch()['total_balance'] ?? 0;

$stmt = $db->query("SELECT SUM(total_earnings) as total_earnings FROM wallets");
$totalEarnings = $stmt->fetch()['total_earnings'] ?? 0;

// Get users for user management
$stmt = $db->query("SELECT u.*, up.full_name, up.vip_tier_id, vt.name as vip_name FROM users u LEFT JOIN user_profiles up ON u.id = up.id LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id ORDER BY u.created_at DESC LIMIT 50");
$users = $stmt->fetchAll();

// Get products
$stmt = $db->query("SELECT * FROM product_images ORDER BY display_order ASC LIMIT 50");
$products = $stmt->fetchAll();

// Get VIP tiers
$stmt = $db->query("SELECT * FROM vip_tiers ORDER BY level ASC");
$vipTiers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Navigation -->
    <nav class="bg-slate-800/50 backdrop-blur-sm border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-emerald-500 to-cyan-500 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Admin Panel</h1>
                        <p class="text-xs text-emerald-400"><?php echo strtoupper(str_replace('_', ' ', $adminRole)); ?></p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                    <a href="logout.php" class="flex items-center space-x-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 px-4 py-2 rounded-lg transition-colors border border-red-500/30">
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
        <div class="bg-slate-800/30 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-slate-700">
                <a href="?tab=stats" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all <?php echo $activeTab === 'stats' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 text-emerald-400 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="font-semibold">Dashboard</span>
                </a>
                <a href="?tab=users" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all <?php echo $activeTab === 'users' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 text-emerald-400 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-semibold">Users</span>
                </a>
                <a href="?tab=products" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all <?php echo $activeTab === 'products' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 text-emerald-400 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span class="font-semibold">Products</span>
                </a>
                <a href="?tab=settings" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all <?php echo $activeTab === 'settings' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 text-emerald-400 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-semibold">Settings</span>
                </a>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <?php if ($activeTab === 'stats'): ?>
                <!-- Dashboard Statistics -->
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl p-6 border border-blue-500/30">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-slate-300 font-semibold">Total Users</h3>
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <p class="text-4xl font-bold text-white"><?php echo number_format($totalUsers); ?></p>
                    </div>

                    <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-xl p-6 border border-green-500/30">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-slate-300 font-semibold">Active Users</h3>
                            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-4xl font-bold text-white"><?php echo number_format($activeUsers); ?></p>
                    </div>

                    <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-xl p-6 border border-purple-500/30">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-slate-300 font-semibold">Total Products</h3>
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <p class="text-4xl font-bold text-white"><?php echo number_format($totalProducts); ?></p>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 rounded-xl p-6 border border-yellow-500/30">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-slate-300 font-semibold">Total Earnings</h3>
                            <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-4xl font-bold text-white">$<?php echo number_format($totalEarnings, 2); ?></p>
                    </div>
                </div>

                <div class="bg-slate-700/30 rounded-xl p-6 border border-slate-600">
                    <h3 class="text-xl font-bold text-white mb-4">System Overview</h3>
                    <p class="text-gray-300">Platform is running smoothly with <?php echo $activeUsers; ?> active users in the last 7 days.</p>
                </div>

                <?php elseif ($activeTab === 'users'): ?>
                <!-- User Management -->
                <div class="bg-slate-700/30 rounded-xl border border-slate-600 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">VIP Tier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Joined</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-slate-700/20 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($user['full_name'] ?? 'Unknown'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-300"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-400">
                                            <?php echo htmlspecialchars($user['vip_name'] ?? 'VIP 1'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php elseif ($activeTab === 'products'): ?>
                <!-- Product Management -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                    <div class="bg-slate-700/30 rounded-xl border border-slate-600 overflow-hidden hover:border-emerald-500/50 transition-all">
                        <div class="aspect-video bg-slate-800 flex items-center justify-center">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['brand_name']); ?>" class="w-full h-full object-cover" />
                        </div>
                        <div class="p-4">
                            <h4 class="text-white font-bold text-lg mb-1"><?php echo htmlspecialchars($product['brand_name']); ?></h4>
                            <?php if (!empty($product['product_name'])): ?>
                            <p class="text-gray-400 text-sm mb-2"><?php echo htmlspecialchars($product['product_name']); ?></p>
                            <?php endif; ?>
                            <div class="flex justify-between items-center">
                                <span class="text-emerald-400 font-semibold">$<?php echo number_format($product['price'] ?? 0, 2); ?></span>
                                <span class="text-xs text-gray-500">Order: <?php echo $product['display_order']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php else: ?>
                <!-- Settings -->
                <div class="space-y-6">
                    <div class="bg-slate-700/30 rounded-xl p-6 border border-slate-600">
                        <h3 class="text-xl font-bold text-white mb-4">VIP Tiers</h3>
                        <div class="space-y-4">
                            <?php foreach ($vipTiers as $tier): ?>
                            <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="text-white font-bold"><?php echo htmlspecialchars($tier['name']); ?></h4>
                                        <p class="text-gray-400 text-sm">Level <?php echo $tier['level']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-emerald-400 font-semibold"><?php echo $tier['commission_rate']; ?>% Commission</p>
                                        <p class="text-gray-400 text-sm"><?php echo $tier['max_tasks_per_day'] == 999999 ? 'Unlimited' : $tier['max_tasks_per_day']; ?> tasks/day</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
