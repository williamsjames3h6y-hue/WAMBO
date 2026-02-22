<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$auth = new Auth();
$userId = getCurrentUserId();
$isAdmin = $auth->isAdmin($userId);

if (!$isAdmin) {
    redirect('dashboard.php');
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

$stmt = $db->query("SELECT COUNT(*) as active_users FROM users WHERE last_sign_in_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$activeUsers = $stmt->fetch()['active_users'];

$stmt = $db->query("SELECT COUNT(*) as total_products FROM product_images WHERE is_active = TRUE");
$totalProducts = $stmt->fetch()['total_products'];

$stmt = $db->query("SELECT SUM(balance) as total_balance FROM wallets");
$totalBalance = $stmt->fetch()['total_balance'] ?? 0;

$stmt = $db->query("SELECT SUM(total_earnings) as total_earnings FROM wallets");
$totalEarnings = $stmt->fetch()['total_earnings'] ?? 0;

$stmt = $db->query("SELECT u.*, up.full_name, up.phone, up.vip_tier_id, vt.name as vip_name, vt.level as vip_level, w.balance, w.total_earnings FROM users u LEFT JOIN user_profiles up ON u.id = up.id LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id LEFT JOIN wallets w ON u.id = w.user_id ORDER BY u.created_at DESC LIMIT 100");
$users = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM product_images ORDER BY display_order ASC");
$products = $stmt->fetchAll();

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
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <nav class="bg-slate-800/50 backdrop-blur-sm border-b border-slate-700">
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
            <div class="flex border-b border-slate-700 overflow-x-auto">
                <a href="?tab=stats" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all whitespace-nowrap <?php echo $activeTab === 'stats' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 text-emerald-400 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="font-semibold">Dashboard</span>
                </a>
                <a href="?tab=users" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all whitespace-nowrap <?php echo $activeTab === 'users' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 text-emerald-400 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-semibold">Users</span>
                </a>
                <a href="?tab=products" class="flex-1 flex items-center justify-center space-x-2 px-6 py-4 transition-all whitespace-nowrap <?php echo $activeTab === 'products' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 text-emerald-400 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span class="font-semibold">Products</span>
                </a>
            </div>

            <div class="p-6">
                <?php if ($activeTab === 'stats'): ?>
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

                    <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 rounded-xl p-6 border border-orange-500/30">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-slate-300 font-semibold">Total Products</h3>
                            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <p class="text-4xl font-bold text-white"><?php echo number_format($totalProducts); ?></p>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-500/20 to-amber-500/20 rounded-xl p-6 border border-yellow-500/30">
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
                <div class="bg-slate-700/30 rounded-xl border border-slate-600 overflow-hidden">
                    <div class="p-4 border-b border-slate-600">
                        <h3 class="text-xl font-bold text-white">User Management</h3>
                        <p class="text-gray-400 text-sm">Manage user accounts, balances, and VIP tiers</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">VIP</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-semibold">
                                        $<?php echo number_format($user['balance'] ?? 0, 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="editUser('<?php echo $user['id']; ?>')" class="text-blue-400 hover:text-blue-300 mr-3">Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php elseif ($activeTab === 'products'): ?>
                <div class="mb-6">
                    <button onclick="showAddProductModal()" class="bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 text-white font-bold px-6 py-3 rounded-xl transition-all shadow-lg">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add New Product
                    </button>
                </div>

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
                            <?php if (!empty($product['description'])): ?>
                            <p class="text-gray-500 text-xs mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
                            <?php endif; ?>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-emerald-400 font-semibold">$<?php echo number_format($product['price'] ?? 0, 2); ?></span>
                                <span class="text-cyan-400 text-sm">Com: $<?php echo number_format($product['commission'] ?? 0, 2); ?></span>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="editProduct('<?php echo $product['id']; ?>')" class="flex-1 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 px-3 py-2 rounded-lg transition-all text-sm">Edit</button>
                                <button onclick="deleteProduct('<?php echo $product['id']; ?>')" class="flex-1 bg-red-500/20 hover:bg-red-500/30 text-red-400 px-3 py-2 rounded-lg transition-all text-sm">Delete</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="editUserModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-2xl max-w-md w-full p-6 border border-slate-700">
            <h3 class="text-2xl font-bold text-white mb-6">Edit User</h3>
            <form id="editUserForm" class="space-y-4">
                <input type="hidden" id="edit_user_id" name="user_id">

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Balance Adjustment</label>
                    <div class="flex space-x-2">
                        <input type="number" step="0.01" id="balance_amount" placeholder="Amount" class="flex-1 bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                        <select id="balance_operation" class="bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                            <option value="add">Add</option>
                            <option value="subtract">Subtract</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-2">VIP Tier</label>
                    <select id="vip_tier_id" class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                        <?php foreach ($vipTiers as $tier): ?>
                        <option value="<?php echo $tier['id']; ?>"><?php echo htmlspecialchars($tier['name']); ?> (Level <?php echo $tier['level']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="updateUserBalance()" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg transition-all">Update Balance</button>
                    <button type="button" onclick="updateUserVIP()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg transition-all">Update VIP</button>
                </div>
                <button type="button" onclick="closeEditUserModal()" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-lg transition-all">Close</button>
            </form>
        </div>
    </div>

    <div id="productModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-2xl max-w-2xl w-full p-6 border border-slate-700 max-h-[90vh] overflow-y-auto">
            <h3 id="productModalTitle" class="text-2xl font-bold text-white mb-6">Add Product</h3>
            <form id="productForm" class="space-y-4">
                <input type="hidden" id="product_id" name="product_id">

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 text-sm font-semibold mb-2">Brand Name *</label>
                        <input type="text" id="brand_name" required class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                    </div>

                    <div>
                        <label class="block text-gray-300 text-sm font-semibold mb-2">Product Name</label>
                        <input type="text" id="product_name" class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Description</label>
                    <textarea id="description" rows="3" class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white"></textarea>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-300 text-sm font-semibold mb-2">Price</label>
                        <input type="number" step="0.01" id="price" class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                    </div>

                    <div>
                        <label class="block text-gray-300 text-sm font-semibold mb-2">Commission</label>
                        <input type="number" step="0.01" id="commission" class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                    </div>

                    <div>
                        <label class="block text-gray-300 text-sm font-semibold mb-2">Display Order</label>
                        <input type="number" id="display_order" class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Image URL *</label>
                    <input type="text" id="image_url" required placeholder="/public/products/P1.jpg" class="w-full bg-slate-700/50 border border-slate-600 rounded-lg px-4 py-2 text-white">
                    <p class="text-gray-500 text-xs mt-1">Use paths like: /public/products/P1.jpg</p>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 text-white font-bold py-3 rounded-lg transition-all">Save Product</button>
                    <button type="button" onclick="closeProductModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-lg transition-all">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editUser(userId) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('editUserModal').classList.remove('hidden');
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }

        async function updateUserBalance() {
            const userId = document.getElementById('edit_user_id').value;
            const amount = document.getElementById('balance_amount').value;
            const operation = document.getElementById('balance_operation').value;

            if (!amount) {
                alert('Please enter an amount');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_user_balance');
            formData.append('user_id', userId);
            formData.append('amount', amount);
            formData.append('operation', operation);

            const response = await fetch('/api/admin_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                alert('Balance updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        }

        async function updateUserVIP() {
            const userId = document.getElementById('edit_user_id').value;
            const vipTierId = document.getElementById('vip_tier_id').value;

            const formData = new FormData();
            formData.append('action', 'update_user_vip');
            formData.append('user_id', userId);
            formData.append('vip_tier_id', vipTierId);

            const response = await fetch('/api/admin_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                alert('VIP tier updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        }

        function showAddProductModal() {
            document.getElementById('productModalTitle').textContent = 'Add Product';
            document.getElementById('productForm').reset();
            document.getElementById('product_id').value = '';
            document.getElementById('productModal').classList.remove('hidden');
        }

        function editProduct(productId) {
            document.getElementById('productModalTitle').textContent = 'Edit Product';
            document.getElementById('product_id').value = productId;
            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const productId = document.getElementById('product_id').value;
            const formData = new FormData();

            formData.append('action', productId ? 'update_product' : 'add_product');
            if (productId) formData.append('product_id', productId);
            formData.append('brand_name', document.getElementById('brand_name').value);
            formData.append('product_name', document.getElementById('product_name').value);
            formData.append('description', document.getElementById('description').value);
            formData.append('price', document.getElementById('price').value);
            formData.append('commission', document.getElementById('commission').value);
            formData.append('image_url', document.getElementById('image_url').value);
            formData.append('display_order', document.getElementById('display_order').value);

            const response = await fetch('/api/admin_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                alert(productId ? 'Product updated successfully!' : 'Product added successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });

        async function deleteProduct(productId) {
            if (!confirm('Are you sure you want to delete this product?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_product');
            formData.append('product_id', productId);

            const response = await fetch('/api/admin_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                alert('Product deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        }
    </script>
</body>
</html>
