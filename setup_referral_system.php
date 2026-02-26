<?php
require_once __DIR__ . '/config/config.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Referral System - EarningsLLC</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 flex items-center justify-center p-4'>
    <div class='max-w-4xl w-full bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700'>
        <h1 class='text-3xl font-bold text-white mb-6'>🎁 Referral System Setup</h1>
        <div class='space-y-4'>";

try {
    $db->beginTransaction();

    echo "<div class='bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 text-blue-300'>";
    echo "<p>Starting referral system setup...</p>";
    echo "</div>";

    $checkReferrals = $db->query("SHOW TABLES LIKE 'referrals'");
    if ($checkReferrals->rowCount() == 0) {
        $createReferrals = "
        CREATE TABLE IF NOT EXISTS referrals (
          id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
          referrer_id CHAR(36) NOT NULL,
          referred_id CHAR(36) NOT NULL,
          status VARCHAR(50) DEFAULT 'pending',
          commission_earned DECIMAL(10,2) DEFAULT 0.00,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
          FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
          UNIQUE KEY unique_referral (referrer_id, referred_id),
          INDEX idx_referrals_referrer (referrer_id),
          INDEX idx_referrals_referred (referred_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->exec($createReferrals);
        echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
        echo "<p>✓ Created referrals table</p>";
        echo "</div>";
    } else {
        echo "<div class='bg-yellow-500/10 border border-yellow-500/50 rounded-lg p-3 text-yellow-300'>";
        echo "<p>⚠ Referrals table already exists</p>";
        echo "</div>";
    }

    $checkReferralCode = $db->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
    if ($checkReferralCode->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) UNIQUE DEFAULT NULL");
        echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
        echo "<p>✓ Added referral_code column to users table</p>";
        echo "</div>";

        $usersQuery = $db->query("SELECT id FROM users WHERE referral_code IS NULL");
        $users = $usersQuery->fetchAll();

        foreach ($users as $user) {
            $referralCode = strtoupper(substr(md5($user['id'] . time()), 0, 10));
            $updateStmt = $db->prepare("UPDATE users SET referral_code = :code WHERE id = :id");
            $updateStmt->execute([':code' => $referralCode, ':id' => $user['id']]);
        }

        echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
        echo "<p>✓ Generated referral codes for " . count($users) . " existing users</p>";
        echo "</div>";
    } else {
        echo "<div class='bg-yellow-500/10 border border-yellow-500/50 rounded-lg p-3 text-yellow-300'>";
        echo "<p>⚠ Referral code column already exists</p>";
        echo "</div>";
    }

    $checkReferredBy = $db->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
    if ($checkReferredBy->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN referred_by CHAR(36) DEFAULT NULL");
        $db->exec("ALTER TABLE users ADD FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "<div class='bg-green-500/10 border border-green-500/50 rounded-lg p-3 text-green-300'>";
        echo "<p>✓ Added referred_by column to users table</p>";
        echo "</div>";
    } else {
        echo "<div class='bg-yellow-500/10 border border-yellow-500/50 rounded-lg p-3 text-yellow-300'>";
        echo "<p>⚠ Referred_by column already exists</p>";
        echo "</div>";
    }

    $db->commit();

    echo "<div class='mt-6 bg-green-500/20 border border-green-500 rounded-lg p-6 text-green-300'>";
    echo "<p class='text-xl font-bold mb-2'>✓ Referral system setup complete!</p>";
    echo "<p>Users can now refer friends and earn commissions.</p>";
    echo "</div>";

    echo "<div class='mt-6 flex gap-4'>";
    echo "<a href='/dashboard' class='flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all text-center'>Go to Dashboard</a>";
    echo "<a href='/admin' class='flex-1 bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-all text-center'>Admin Panel</a>";
    echo "</div>";

} catch (Exception $e) {
    $db->rollBack();
    echo "<div class='bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-300'>";
    echo "<p class='font-bold'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "    </div>
    </div>
</body>
</html>";
?>
