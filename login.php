<?php
require_once __DIR__ . '/config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$loading = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        require_once __DIR__ . '/includes/auth.php';
        $auth = new Auth();
        $result = $auth->login($email, $password);

        if ($result['success']) {
            redirect('dashboard.php');
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please enter both email and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative">
            <a href="index.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>

            <div class="p-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                    Welcome Back
                </h2>
                <p class="text-gray-600 mb-8">
                    Sign in to access your annotation workspace
                </p>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input
                                type="email"
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="you@example.com"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input
                                type="password"
                                name="password"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="••••••••"
                                required
                                minlength="6"
                            />
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a
                        href="register.php"
                        class="text-blue-600 hover:text-blue-700 font-medium transition-colors"
                    >
                        Don't have an account? Sign up
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
