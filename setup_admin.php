<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700">
        <h1 class="text-3xl font-bold text-white mb-6">Admin Setup</h1>

        <?php
        require_once __DIR__ . '/config/config.php';
        require_once __DIR__ . '/includes/auth.php';

        // Admin credentials
        $adminEmail = 'admin1@gmail.com';
        $adminPassword = 'ADMIN4040';
        $adminName = 'Admin User';

        $database = new Database();
        $db = $database->getConnection();

        if (!$db) {
            echo '<div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300">';
            echo '<p class="font-bold">Database connection failed!</p>';
            echo '</div>';
            exit();
        }

        try {
            // Check if admin user already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $adminEmail]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                echo '<div class="bg-blue-500/10 border border-blue-500/50 rounded-lg p-4 text-blue-300 mb-4">';
                echo '<p class="font-bold">Admin user already exists with email: ' . htmlspecialchars($adminEmail) . '</p>';
                echo '</div>';

                $userId = $existingUser['id'];

                // Check if user is in admin_users table
                $stmt = $db->prepare("SELECT id FROM admin_users WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $adminRecord = $stmt->fetch();

                if (!$adminRecord) {
                    // Add to admin_users table
                    $adminId = generateUUID();
                    $stmt = $db->prepare("INSERT INTO admin_users (id, user_id, role) VALUES (:id, :user_id, 'admin')");
                    $stmt->execute([
                        ':id' => $adminId,
                        ':user_id' => $userId
                    ]);
                    echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-4 text-green-300 mb-4">';
                    echo '<p class="font-bold">✓ Added existing user to admin_users table</p>';
                    echo '</div>';
                } else {
                    echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-4 text-green-300 mb-4">';
                    echo '<p class="font-bold">✓ User is already an admin</p>';
                    echo '</div>';
                }
            } else {
                // Create new admin user
                $auth = new Auth();
                $result = $auth->register($adminEmail, $adminPassword, $adminName);

                if ($result['success']) {
                    $userId = $result['user_id'];
                    echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-4 text-green-300 mb-4">';
                    echo '<p class="font-bold">✓ Created new admin user with email: ' . htmlspecialchars($adminEmail) . '</p>';
                    echo '</div>';

                    // Add to admin_users table
                    $adminId = generateUUID();
                    $stmt = $db->prepare("INSERT INTO admin_users (id, user_id, role) VALUES (:id, :user_id, 'admin')");
                    $stmt->execute([
                        ':id' => $adminId,
                        ':user_id' => $userId
                    ]);
                    echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-4 text-green-300 mb-4">';
                    echo '<p class="font-bold">✓ Added user to admin_users table</p>';
                    echo '</div>';
                } else {
                    echo '<div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300">';
                    echo '<p class="font-bold">Failed to create admin user: ' . htmlspecialchars($result['message']) . '</p>';
                    echo '</div>';
                    exit();
                }
            }

            echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-6 text-green-300">';
            echo '<p class="text-xl font-bold mb-4">✓ Admin setup complete!</p>';
            echo '<div class="space-y-2 bg-slate-700/30 rounded-lg p-4">';
            echo '<p><span class="text-gray-400">Email:</span> <span class="font-mono">' . htmlspecialchars($adminEmail) . '</span></p>';
            echo '<p><span class="text-gray-400">Password:</span> <span class="font-mono">' . htmlspecialchars($adminPassword) . '</span></p>';
            echo '</div>';
            echo '</div>';

            echo '<div class="mt-6 flex gap-4">';
            echo '<a href="/login" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all text-center">Go to Login</a>';
            echo '<a href="/admin" class="flex-1 bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-all text-center">Go to Admin Panel</a>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300">';
            echo '<p class="font-bold">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
