<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$userId = $_SESSION['user_id'];

// Fetch user data
try {
    $query = "SELECT u.*, up.full_name, w.balance, w.total_earnings
              FROM users u
              LEFT JOIN user_profiles up ON up.id = u.id
              LEFT JOIN wallets w ON w.user_id = u.id
              WHERE u.id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    $fullName = $user['full_name'] ?? explode('@', $user['email'])[0];
    $balance = $user['balance'] ?? 0;
    $totalEarnings = $user['total_earnings'] ?? 0;
    $referralCode = $user['referral_code'] ?? null;
    $trainingCompleted = isset($user['training_completed']) ? (bool)$user['training_completed'] : false;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Fetch training earnings
$trainingEarnings = 0;
try {
    $trainingQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions
                     WHERE user_id = :user_id AND description LIKE '%training%'";
    $trainingStmt = $db->prepare($trainingQuery);
    $trainingStmt->bindParam(':user_id', $userId);
    $trainingStmt->execute();
    $trainingData = $trainingStmt->fetch(PDO::FETCH_ASSOC);
    $trainingEarnings = $trainingData['total'] ?? 0;
} catch (PDOException $e) {
    $trainingEarnings = 0;
}

// Fetch referrals with names
$referrals = [];
try {
    $refQuery = "SELECT r.*, up.full_name as referred_name, u.email as referred_email, r.created_at
                 FROM referrals r
                 LEFT JOIN users u ON r.referred_id = u.id
                 LEFT JOIN user_profiles up ON up.id = r.referred_id
                 WHERE r.referrer_id = :user_id
                 ORDER BY r.created_at DESC
                 LIMIT 10";
    $refStmt = $db->prepare($refQuery);
    $refStmt->bindParam(':user_id', $userId);
    $refStmt->execute();
    $referrals = $refStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $referrals = [];
}

$totalReferrals = count($referrals);

// Check for success message
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EarningsLLC</title>
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            min-height: 100vh;
            padding-bottom: 100px;
        }

        .header {
            background: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #0369a1;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .logout-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .success-banner {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-banner {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border-radius: 16px;
            padding: 40px;
            color: white;
            margin-bottom: 32px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3);
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-content h1 {
            font-size: 2rem;
            margin-bottom: 12px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-info h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-value.green {
            color: #10b981;
        }

        .referral-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 32px;
        }

        .referral-header {
            margin-bottom: 20px;
        }

        .referral-header h2 {
            font-size: 20px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .referral-link-box {
            background: linear-gradient(135deg, #ecfeff, #cffafe);
            border: 2px dashed #06b6d4;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .referral-link-input {
            display: flex;
            gap: 12px;
            margin-top: 12px;
        }

        .referral-link-input input {
            flex: 1;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        .copy-btn {
            background: #06b6d4;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: #0891b2;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .action-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #0f172a;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            position: relative;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .action-card.locked {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .action-card.locked::after {
            content: '🔒';
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 20px;
        }

        .action-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .action-label {
            font-weight: 600;
            font-size: 16px;
        }

        .referral-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .referral-item {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .referral-item:last-child {
            border-bottom: none;
        }

        .referral-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .referral-date {
            font-size: 13px;
            color: #64748b;
        }

        .referral-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .referral-status.active {
            background: #d1fae5;
            color: #065f46;
        }

        .referral-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .no-referrals {
            text-align: center;
            padding: 48px;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .hero-content h1 {
                font-size: 1.5rem;
            }

            .referral-link-input {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <span>💼</span>
            <span>EarningsLLC</span>
        </div>
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($fullName, 0, 1)); ?>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($successMessage): ?>
            <div class="success-banner">
                <span style="font-size: 24px;">🎉</span>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>

        <div class="hero-banner">
            <div class="hero-content">
                <h1>Welcome back, <?php echo htmlspecialchars($fullName); ?>!</h1>
                <p style="font-size: 1.1rem; opacity: 0.95;">Start earning by completing tasks today</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">💵</div>
                <div class="stat-info">
                    <h3>Account Balance</h3>
                    <div class="stat-value green">$<?php echo number_format($balance, 2); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">🎓</div>
                <div class="stat-info">
                    <h3>Training Earnings</h3>
                    <div class="stat-value">$<?php echo number_format($trainingEarnings, 2); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">👥</div>
                <div class="stat-info">
                    <h3>Total Referrals</h3>
                    <div class="stat-value"><?php echo $totalReferrals; ?></div>
                </div>
            </div>
        </div>

        <div class="referral-section">
            <div class="referral-header">
                <h2>Your Referral Link</h2>
                <p style="color: #64748b; font-size: 14px;">Share this link with friends to earn commissions on their earnings!</p>
            </div>
            <div class="referral-link-box">
                <div style="font-weight: 600; margin-bottom: 8px;">📎 Your Referral Code: <?php echo $referralCode ?? 'N/A'; ?></div>
                <div class="referral-link-input">
                    <input type="text" readonly id="referralLink" value="<?php echo 'https://earningsllc.online/register.php?ref=' . ($referralCode ?? ''); ?>">
                    <button class="copy-btn" onclick="copyReferralLink()">📋 Copy Link</button>
                </div>
            </div>
        </div>

        <h2 style="margin-bottom: 16px; color: #0f172a;">Quick Actions</h2>
        <div class="quick-actions">
            <a href="/training/tasks.php" class="action-card">
                <div class="action-icon">📚</div>
                <div class="action-label">Start Training</div>
            </a>

            <a href="<?php echo $trainingCompleted ? '/tasks.php' : '#'; ?>" class="action-card <?php echo !$trainingCompleted ? 'locked' : ''; ?>" <?php echo !$trainingCompleted ? 'onclick="return false;"' : ''; ?>>
                <div class="action-icon">✅</div>
                <div class="action-label">View Tasks</div>
            </a>

            <a href="/profile.php" class="action-card">
                <div class="action-icon">👥</div>
                <div class="action-label">My Referrals</div>
            </a>

            <a href="/payment_methods.php" class="action-card">
                <div class="action-icon">💳</div>
                <div class="action-label">Withdraw</div>
            </a>
        </div>

        <?php if (count($referrals) > 0): ?>
            <div class="referral-section">
                <h2 style="margin-bottom: 16px;">My Referrals (<?php echo $totalReferrals; ?>)</h2>
                <div class="referral-list">
                    <?php foreach ($referrals as $referral): ?>
                        <div class="referral-item">
                            <div>
                                <div class="referral-name">
                                    <?php echo htmlspecialchars($referral['referred_name'] ?? explode('@', $referral['referred_email'])[0]); ?>
                                </div>
                                <div class="referral-date">
                                    Joined: <?php echo date('M d, Y', strtotime($referral['created_at'])); ?>
                                </div>
                            </div>
                            <div class="referral-status <?php echo $referral['status']; ?>">
                                <?php echo ucfirst($referral['status']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyReferralLink() {
            const input = document.getElementById('referralLink');
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');

            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✓ Copied!';
            btn.style.background = '#10b981';

            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '#06b6d4';
            }, 2000);
        }
    </script>
</body>
</html>
