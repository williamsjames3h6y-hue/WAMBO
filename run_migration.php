<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Run Database Migration - EarningsLLC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700">
        <h1 class="text-3xl font-bold text-white mb-6">Database Migration</h1>

        <?php
        require_once __DIR__ . '/config/config.php';

        $database = new Database();
        $db = $database->getConnection();

        if (!$db) {
            echo '<div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300">';
            echo '<p class="font-bold">Database connection failed!</p>';
            echo '</div>';
            exit();
        }

        try {
            echo '<div class="space-y-4">';
            echo '<h2 class="text-xl font-semibold text-blue-300">Adding withdrawal password and phone fields...</h2>';

            // Check if columns already exist
            $stmt = $db->query("SHOW COLUMNS FROM user_profiles LIKE 'phone'");
            $phoneExists = $stmt->rowCount() > 0;

            $stmt = $db->query("SHOW COLUMNS FROM user_profiles LIKE 'withdrawal_password_hash'");
            $withdrawalPasswordExists = $stmt->rowCount() > 0;

            $stmt = $db->query("SHOW COLUMNS FROM user_profiles LIKE 'is_active'");
            $isActiveExists = $stmt->rowCount() > 0;

            // Add phone column if it doesn't exist
            if (!$phoneExists) {
                $db->exec("ALTER TABLE user_profiles ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER full_name");
                echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300">';
                echo '<p>✓ Added "phone" column to user_profiles table</p>';
                echo '</div>';
            } else {
                echo '<div class="bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 text-blue-300">';
                echo '<p>- "phone" column already exists</p>';
                echo '</div>';
            }

            // Add withdrawal_password_hash column if it doesn't exist
            if (!$withdrawalPasswordExists) {
                $db->exec("ALTER TABLE user_profiles ADD COLUMN withdrawal_password_hash VARCHAR(255) DEFAULT NULL AFTER phone");
                echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300">';
                echo '<p>✓ Added "withdrawal_password_hash" column to user_profiles table</p>';
                echo '</div>';
            } else {
                echo '<div class="bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 text-blue-300">';
                echo '<p>- "withdrawal_password_hash" column already exists</p>';
                echo '</div>';
            }

            // Add is_active column if it doesn't exist
            if (!$isActiveExists) {
                $db->exec("ALTER TABLE user_profiles ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER annotations_this_month");
                echo '<div class="bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300">';
                echo '<p>✓ Added "is_active" column to user_profiles table</p>';
                echo '</div>';
            } else {
                echo '<div class="bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 text-blue-300">';
                echo '<p>- "is_active" column already exists</p>';
                echo '</div>';
            }

            echo '</div>';

            echo '<div class="mt-8 bg-green-500/10 border border-green-500/50 rounded-lg p-6 text-green-300">';
            echo '<p class="text-xl font-bold">✓ Migration completed successfully!</p>';
            echo '</div>';

            echo '<div class="mt-6 flex gap-4">';
            echo '<a href="/dashboard.php" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all text-center">Go to Dashboard</a>';
            echo '<a href="/admin.php" class="flex-1 bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-yellow-700 transition-all text-center">Admin Panel</a>';
            echo '</div>';

        } catch (PDOException $e) {
            echo '<div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300">';
            echo '<p class="font-bold">Migration failed:</p>';
            echo '<p class="mt-2">' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
