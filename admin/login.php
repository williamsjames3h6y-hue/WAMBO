<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in as admin, redirect to admin dashboard
if (isLoggedIn()) {
    $auth = new Auth();
    $userId = getCurrentUserId();
    if ($auth->isAdmin($userId)) {
        redirect('/admin/');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $auth = new Auth();
        $result = $auth->login($email, $password);

        if ($result['success']) {
            // Check if user is admin
            if ($auth->isAdmin($result['user_id'])) {
                redirect('/admin/');
            } else {
                $error = 'Access denied. Admin privileges required.';
                // Log them out since they're not an admin
                $auth->logout();
            }
        } else {
            $error = $result['message'] ?? 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-yellow-700 via-amber-600 to-yellow-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-yellow-900/50 backdrop-blur-sm rounded-2xl border border-yellow-600 p-8 shadow-2xl">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center bg-gradient-to-r from-emerald-500 to-cyan-500 p-3 rounded-xl mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Admin Panel</h1>
                <p class="text-yellow-200">Sign in to access the admin dashboard</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/50 rounded-lg p-4">
                <p class="text-red-300 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-yellow-100 text-sm font-semibold mb-2">Email Address</label>
                    <input type="email" name="email" required
                        class="w-full px-4 py-3 rounded-lg bg-yellow-800/50 border border-yellow-600 text-white placeholder-yellow-300 focus:border-emerald-500 focus:outline-none transition-colors"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-yellow-100 text-sm font-semibold mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 rounded-lg bg-yellow-800/50 border border-yellow-600 text-white placeholder-yellow-300 focus:border-emerald-500 focus:outline-none transition-colors"
                        placeholder="Enter your password">
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 text-white font-bold px-6 py-3 rounded-lg transition-all shadow-lg">
                    Sign In to Admin Panel
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="/" class="text-yellow-200 hover:text-white text-sm transition-colors">
                    Back to Main Site
                </a>
            </div>
        </div>

        <div class="mt-6 text-center">
            <p class="text-yellow-200 text-sm">Admin access only. Unauthorized access is prohibited.</p>
        </div>
    </div>
</body>
</html>
