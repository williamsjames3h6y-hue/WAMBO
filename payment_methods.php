<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
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

$message = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wallet = sanitizeInput($_POST['wallet'] ?? '');
    $network = sanitizeInput($_POST['network'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');

    if (empty($wallet) || empty($network) || empty($address)) {
        $message = ['type' => 'error', 'text' => 'Please fill in all fields'];
    } else {
        try {
            if ($paymentMethod) {
                // Update existing
                $stmt = $db->prepare("UPDATE payment_methods SET wallet_address = :wallet, network = :network, address = :address, updated_at = NOW() WHERE user_id = :user_id");
                $stmt->execute([
                    ':wallet' => $wallet,
                    ':network' => $network,
                    ':address' => $address,
                    ':user_id' => $userId
                ]);
            } else {
                // Insert new
                $paymentMethodId = generateUUID();
                $stmt = $db->prepare("INSERT INTO payment_methods (id, user_id, wallet_address, network, address) VALUES (:id, :user_id, :wallet, :network, :address)");
                $stmt->execute([
                    ':id' => $paymentMethodId,
                    ':user_id' => $userId,
                    ':wallet' => $wallet,
                    ':network' => $network,
                    ':address' => $address
                ]);
            }

            $message = ['type' => 'success', 'text' => 'Payment method updated successfully!'];

            // Reload payment method
            $stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $paymentMethod = $stmt->fetch();
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'Failed to update payment method'];
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
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/50 backdrop-blur-sm shadow-sm border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-white p-2 hover:bg-slate-700 rounded-lg transition-all">
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

        <!-- Payment Form -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700">
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-3">
                        Wallet
                    </label>
                    <input
                        type="text"
                        name="wallet"
                        value="<?php echo htmlspecialchars($paymentMethod['wallet_address'] ?? ''); ?>"
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
                        value="<?php echo htmlspecialchars($paymentMethod['network'] ?? ''); ?>"
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
                        value="<?php echo htmlspecialchars($paymentMethod['address'] ?? ''); ?>"
                        placeholder="Address"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg"
                >
                    Update
                </button>
            </form>
        </div>

        <!-- Current Payment Method Display -->
        <?php if ($paymentMethod && !empty($paymentMethod['wallet_address'])): ?>
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
                    <p class="text-white font-mono text-sm break-all"><?php echo htmlspecialchars($paymentMethod['wallet_address']); ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs mb-1">Network</p>
                    <p class="text-white font-mono text-sm"><?php echo htmlspecialchars($paymentMethod['network']); ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs mb-1">Address</p>
                    <p class="text-white font-mono text-sm break-all"><?php echo htmlspecialchars($paymentMethod['address']); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
