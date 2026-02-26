<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 p-4">
    <div class="max-w-6xl mx-auto py-8">
        <h1 class="text-4xl font-bold text-white mb-8 text-center">Admin Setup Panel</h1>

        <?php
        require_once __DIR__ . '/config/config.php';
        require_once __DIR__ . '/includes/auth.php';

        $database = new Database();
        $db = $database->getConnection();

        if (!$db) {
            echo '<div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300">';
            echo '<p class="font-bold">Database connection failed!</p>';
            echo '</div>';
            exit();
        }

        $message = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                if ($_POST['action'] === 'add_admin') {
                    $adminEmail = trim($_POST['email']);
                    $adminPassword = trim($_POST['password']);
                    $adminName = trim($_POST['name']);

                    if (empty($adminEmail) || empty($adminPassword) || empty($adminName)) {
                        $message = 'All fields are required!';
                        $messageType = 'error';
                    } else {
                        // Check if user already exists
                        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                        $stmt->execute([':email' => $adminEmail]);
                        $existingUser = $stmt->fetch();

                        if ($existingUser) {
                            $userId = $existingUser['id'];

                            // Check if already an admin
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
                                $message = "Existing user '$adminEmail' has been promoted to admin!";
                                $messageType = 'success';
                            } else {
                                $message = "User '$adminEmail' is already an admin!";
                                $messageType = 'info';
                            }
                        } else {
                            // Create new admin user
                            $auth = new Auth();
                            $result = $auth->register($adminEmail, $adminPassword, $adminName);

                            if ($result['success']) {
                                $userId = $result['user_id'];

                                // Add to admin_users table
                                $adminId = generateUUID();
                                $stmt = $db->prepare("INSERT INTO admin_users (id, user_id, role) VALUES (:id, :user_id, 'admin')");
                                $stmt->execute([
                                    ':id' => $adminId,
                                    ':user_id' => $userId
                                ]);
                                $message = "New admin user '$adminEmail' created successfully!";
                                $messageType = 'success';
                            } else {
                                $message = "Failed to create admin: " . $result['message'];
                                $messageType = 'error';
                            }
                        }
                    }
                } elseif ($_POST['action'] === 'remove_admin') {
                    $userId = trim($_POST['user_id']);

                    $stmt = $db->prepare("DELETE FROM admin_users WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $userId]);

                    $message = "Admin privileges removed successfully!";
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = 'error';
            }
        }

        if ($message) {
            $colors = [
                'success' => 'bg-green-500/10 border-green-500/50 text-green-300',
                'error' => 'bg-red-500/10 border-red-500/50 text-red-300',
                'info' => 'bg-blue-500/10 border-blue-500/50 text-blue-300'
            ];
            echo '<div class="mb-6 ' . $colors[$messageType] . ' border rounded-lg p-4">';
            echo '<p class="font-bold">' . htmlspecialchars($message) . '</p>';
            echo '</div>';
        }
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Add Admin Form -->
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700">
                <h2 class="text-2xl font-bold text-white mb-6">Add New Admin</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_admin">

                    <div>
                        <label class="block text-gray-300 mb-2">Full Name</label>
                        <input type="text" name="name" required
                            class="w-full px-4 py-3 rounded-lg bg-slate-700/50 border border-slate-600 text-white focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-gray-300 mb-2">Email</label>
                        <input type="email" name="email" required
                            class="w-full px-4 py-3 rounded-lg bg-slate-700/50 border border-slate-600 text-white focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-gray-300 mb-2">Password</label>
                        <input type="text" name="password" required
                            class="w-full px-4 py-3 rounded-lg bg-slate-700/50 border border-slate-600 text-white focus:border-blue-500 focus:outline-none">
                        <p class="text-gray-400 text-sm mt-1">Use a strong password for security</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-all">
                        Create Admin Account
                    </button>
                </form>
            </div>

            <!-- Existing Admins List -->
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700">
                <h2 class="text-2xl font-bold text-white mb-6">Current Admins</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php
                    try {
                        $stmt = $db->query("
                            SELECT u.id, u.email, up.full_name, a.role, a.created_at as admin_since
                            FROM admin_users a
                            JOIN users u ON a.user_id = u.id
                            LEFT JOIN user_profiles up ON u.id = up.id
                            ORDER BY a.created_at DESC
                        ");
                        $admins = $stmt->fetchAll();

                        if (empty($admins)) {
                            echo '<p class="text-gray-400 text-center py-8">No admin users found</p>';
                        } else {
                            foreach ($admins as $admin) {
                                $displayName = $admin['full_name'] ?: explode('@', $admin['email'])[0];
                                echo '<div class="bg-slate-700/30 rounded-lg p-4 border border-slate-600">';
                                echo '<div class="flex justify-between items-start">';
                                echo '<div class="flex-1">';
                                echo '<p class="text-white font-semibold">' . htmlspecialchars($displayName) . '</p>';
                                echo '<p class="text-gray-400 text-sm">' . htmlspecialchars($admin['email']) . '</p>';
                                echo '<p class="text-gray-500 text-xs mt-1">Admin since: ' . date('M d, Y', strtotime($admin['admin_since'])) . '</p>';
                                echo '</div>';
                                echo '<form method="POST" class="ml-4" onsubmit="return confirm(\'Remove admin privileges for ' . htmlspecialchars($displayName) . '?\')">';
                                echo '<input type="hidden" name="action" value="remove_admin">';
                                echo '<input type="hidden" name="user_id" value="' . htmlspecialchars($admin['id']) . '">';
                                echo '<button type="submit" class="text-red-400 hover:text-red-300 text-sm font-medium">Remove</button>';
                                echo '</form>';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<p class="text-red-400">Error loading admins: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700">
            <h2 class="text-2xl font-bold text-white mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/admin.php" class="bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-yellow-700 transition-all text-center">
                    Go to Admin Panel
                </a>
                <a href="/dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all text-center">
                    User Dashboard
                </a>
                <a href="/run_migration.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition-all text-center">
                    Run Migrations
                </a>
            </div>
        </div>
    </div>
</body>
</html>
