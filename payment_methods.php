<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/login');
}

$auth = new Auth();
$userId = getCurrentUserId();

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get payment method
$stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$paymentMethod = $stmt->fetch();

// Get user profile to check withdrawal password
$stmt = $db->prepare("SELECT withdrawal_password_hash, phone FROM user_profiles WHERE id = :user_id");
$stmt->execute([':user_id' => $userId]);
$userProfile = $stmt->fetch();
$hasWithdrawalPassword = !empty($userProfile['withdrawal_password_hash']);

// Decode account details if exists
$accountDetails = null;
if ($paymentMethod && !empty($paymentMethod['account_details'])) {
    $accountDetails = json_decode($paymentMethod['account_details'], true);
}

$message = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? 'update_payment');

    if ($action === 'set_withdrawal_password') {
        $withdrawalPassword = $_POST['withdrawal_password'] ?? '';
        $confirmPassword = $_POST['confirm_withdrawal_password'] ?? '';

        if (empty($withdrawalPassword) || empty($confirmPassword)) {
            $message = ['type' => 'error', 'text' => 'Please fill in all password fields'];
        } elseif (strlen($withdrawalPassword) < 6) {
            $message = ['type' => 'error', 'text' => 'Withdrawal password must be at least 6 characters'];
        } elseif ($withdrawalPassword !== $confirmPassword) {
            $message = ['type' => 'error', 'text' => 'Passwords do not match'];
        } else {
            try {
                $passwordHash = password_hash($withdrawalPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE user_profiles SET withdrawal_password_hash = :password_hash WHERE id = :user_id");
                $stmt->execute([
                    ':password_hash' => $passwordHash,
                    ':user_id' => $userId
                ]);

                $message = ['type' => 'success', 'text' => 'Withdrawal password set successfully!'];
                $hasWithdrawalPassword = true;

                // Reload user profile
                $stmt = $db->prepare("SELECT withdrawal_password_hash, phone FROM user_profiles WHERE id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $userProfile = $stmt->fetch();
            } catch (Exception $e) {
                $message = ['type' => 'error', 'text' => 'Failed to set withdrawal password: ' . $e->getMessage()];
            }
        }
    } else {
        $wallet = sanitizeInput($_POST['wallet'] ?? '');
        $network = sanitizeInput($_POST['network'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');

        if (empty($wallet) || empty($network) || empty($address)) {
            $message = ['type' => 'error', 'text' => 'Please fill in all fields'];
        } else {
            try {
                // Create JSON object for account details
                $details = json_encode([
                    'wallet' => $wallet,
                    'network' => $network,
                    'address' => $address
                ]);

                if ($paymentMethod) {
                    // Update existing
                    $stmt = $db->prepare("UPDATE payment_methods SET method_type = 'crypto', account_details = :details, updated_at = NOW() WHERE user_id = :user_id");
                    $stmt->execute([
                        ':details' => $details,
                        ':user_id' => $userId
                    ]);
                } else {
                    // Insert new
                    $paymentMethodId = generateUUID();
                    $stmt = $db->prepare("INSERT INTO payment_methods (id, user_id, method_type, account_details, is_primary) VALUES (:id, :user_id, 'crypto', :details, TRUE)");
                    $stmt->execute([
                        ':id' => $paymentMethodId,
                        ':user_id' => $userId,
                        ':details' => $details
                    ]);
                }

                $message = ['type' => 'success', 'text' => 'Payment method updated successfully!'];

                // Reload payment method
                $stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $paymentMethod = $stmt->fetch();

                // Decode account details
                if ($paymentMethod && !empty($paymentMethod['account_details'])) {
                    $accountDetails = json_decode($paymentMethod['account_details'], true);
                }
            } catch (Exception $e) {
                $message = ['type' => 'error', 'text' => 'Failed to update payment method: ' . $e->getMessage()];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/50 backdrop-blur-sm shadow-sm border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center space-x-4">
                <a href="/dashboard" class="text-white p-2 hover:bg-slate-700 rounded-lg transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-white">Payment Methods</h1>
            </div>
        </div>
    </header>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alert Message -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700 mb-6">
            <div class="flex items-start space-x-3 mb-4">
                <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-gray-300 leading-relaxed">
                    Dear user, please fill in your TRC-20/ERC-20 address. Please do not enter your bank account detail and password.
                </p>
            </div>
        </div>

        <!-- Success/Error Message -->
        <?php if ($message): ?>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-4 border mb-6 flex items-center space-x-3 <?php echo $message['type'] === 'success' ? 'border-green-500/50 bg-green-500/10' : 'border-red-500/50 bg-red-500/10'; ?>">
            <?php if ($message['type'] === 'success'): ?>
            <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?php else: ?>
            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?php endif; ?>
            <p class="text-sm <?php echo $message['type'] === 'success' ? 'text-green-300' : 'text-red-300'; ?>">
                <?php echo htmlspecialchars($message['text']); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Withdrawal Password Section -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-bold text-white">Withdrawal Password</h2>
                    <p class="text-gray-400 text-sm">Secure your withdrawals with a password</p>
                </div>
                <?php if ($hasWithdrawalPassword): ?>
                <div class="flex items-center space-x-2 bg-green-500/20 px-3 py-1 rounded-full">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-400 text-sm font-semibold">Set</span>
                </div>
                <?php else: ?>
                <div class="flex items-center space-x-2 bg-yellow-500/20 px-3 py-1 rounded-full">
                    <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-yellow-400 text-sm font-semibold">Not Set</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($hasWithdrawalPassword): ?>
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-blue-300 text-sm font-semibold mb-1">Withdrawal Password Active</p>
                        <p class="text-gray-400 text-sm">Your withdrawals are protected. To reset your withdrawal password, please contact support.</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="action" value="set_withdrawal_password">

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-2">
                        Withdrawal Password (min. 6 characters)
                    </label>
                    <input
                        type="password"
                        name="withdrawal_password"
                        placeholder="Enter withdrawal password"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        required
                    />
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-2">
                        Confirm Withdrawal Password
                    </label>
                    <input
                        type="password"
                        name="confirm_withdrawal_password"
                        placeholder="Confirm withdrawal password"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        required
                    />
                </div>

                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg"
                >
                    Set Withdrawal Password
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Payment Form -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700">
            <h2 class="text-xl font-bold text-white mb-4">Payment Information</h2>
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="action" value="update_payment">

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-3">
                        Wallet
                    </label>
                    <input
                        type="text"
                        name="wallet"
                        value="<?php echo htmlspecialchars($accountDetails['wallet'] ?? ''); ?>"
                        placeholder="Wallet"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-3">
                        Network
                    </label>
                    <input
                        type="text"
                        name="network"
                        value="<?php echo htmlspecialchars($accountDetails['network'] ?? ''); ?>"
                        placeholder="Network"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-3">
                        Address
                    </label>
                    <input
                        type="text"
                        name="address"
                        value="<?php echo htmlspecialchars($accountDetails['address'] ?? ''); ?>"
                        placeholder="Address"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg"
                >
                    Update Payment Method
                </button>
            </form>
        </div>

        <!-- Current Payment Method Display -->
        <?php if ($accountDetails && !empty($accountDetails['wallet'])): ?>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700 mt-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-cyan-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-white font-bold">Current Payment Method</h3>
                    <p class="text-gray-400 text-sm">Configured and ready</p>
                </div>
            </div>

            <div class="space-y-3 bg-slate-700/30 rounded-xl p-4">
                <div>
                    <p class="text-gray-400 text-xs mb-1">Wallet</p>
                    <p class="text-white font-mono text-sm break-all"><?php echo htmlspecialchars($accountDetails['wallet']); ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs mb-1">Network</p>
                    <p class="text-white font-mono text-sm"><?php echo htmlspecialchars($accountDetails['network']); ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs mb-1">Address</p>
                    <p class="text-white font-mono text-sm break-all"><?php echo htmlspecialchars($accountDetails['address']); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
