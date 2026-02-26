<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Check which columns exist
$hasTrainingCompleted = false;
$hasUsername = false;
try {
    $checkTraining = $db->query("SHOW COLUMNS FROM users LIKE 'training_completed'");
    $hasTrainingCompleted = $checkTraining->rowCount() > 0;

    $checkUsername = $db->query("SHOW COLUMNS FROM users LIKE 'username'");
    $hasUsername = $checkUsername->rowCount() > 0;
} catch (PDOException $e) {
    // Column check failed
}

// Fetch user data with conditional column selection
try {
    $selectFields = ['id', 'email', 'created_at'];

    if ($hasUsername) {
        $selectFields[] = 'username';
    }

    // Check for other common columns
    $possibleColumns = ['balance', 'referral_code', 'full_name', 'name'];
    foreach ($possibleColumns as $col) {
        try {
            $checkCol = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
            if ($checkCol->rowCount() > 0) {
                $selectFields[] = $col;
            }
        } catch (PDOException $e) {}
    }

    if ($hasTrainingCompleted) {
        $selectFields[] = 'training_completed';
    }

    $query = "SELECT " . implode(', ', $selectFields) . " FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Set defaults for missing fields
    if (!isset($user['username'])) {
        $user['username'] = isset($user['full_name']) ? $user['full_name'] : (isset($user['name']) ? $user['name'] : explode('@', $user['email'])[0]);
    }
    if (!isset($user['balance'])) {
        $user['balance'] = 0;
    }
    if (!isset($user['referral_code']) || empty($user['referral_code'])) {
        // Generate a referral code if it doesn't exist
        $referralCode = strtoupper(substr(md5($user['id'] . time()), 0, 10));
        try {
            $updateCode = $db->prepare("UPDATE users SET referral_code = :code WHERE id = :id");
            $updateCode->execute([':code' => $referralCode, ':id' => $user['id']]);
            $user['referral_code'] = $referralCode;
        } catch (PDOException $e) {
            // If column doesn't exist, show setup message
            $user['referral_code'] = null;
        }
    }
    if (!isset($user['training_completed'])) {
        $user['training_completed'] = false;
    }
} catch (PDOException $e) {
    die("Error loading dashboard: " . $e->getMessage());
}

// Fetch referral stats
try {
    $referralQuery = "SELECT COUNT(*) as total_referrals FROM referrals WHERE referrer_id = :user_id";
    $referralStmt = $db->prepare($referralQuery);
    $referralStmt->bindParam(':user_id', $_SESSION['user_id']);
    $referralStmt->execute();
    $referralStats = $referralStmt->fetch(PDO::FETCH_ASSOC);
    $totalReferrals = $referralStats['total_referrals'] ?? 0;
} catch (PDOException $e) {
    $totalReferrals = 0;
}

// Fetch training progress if tables exist
$trainingProgress = 0;
$trainingEarnings = 0;
try {
    $trainingQuery = "SELECT COUNT(*) as completed FROM user_training_submissions WHERE user_id = :user_id AND status = 'approved'";
    $trainingStmt = $db->prepare($trainingQuery);
    $trainingStmt->bindParam(':user_id', $_SESSION['user_id']);
    $trainingStmt->execute();
    $trainingData = $trainingStmt->fetch(PDO::FETCH_ASSOC);
    $trainingProgress = $trainingData['completed'] ?? 0;

    // Calculate training earnings
    $earningsQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM training_wallets WHERE user_id = :user_id";
    $earningsStmt = $db->prepare($earningsQuery);
    $earningsStmt->bindParam(':user_id', $_SESSION['user_id']);
    $earningsStmt->execute();
    $earningsData = $earningsStmt->fetch(PDO::FETCH_ASSOC);
    $trainingEarnings = $earningsData['total'] ?? 0;
} catch (PDOException $e) {
    // Training tables don't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EarningsLLC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }

        .training-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 16px;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.4);
            margin-left: 10px;
        }

        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #64748b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-slideInRight {
            animation: slideInRight 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-delay-1 { animation-delay: 0.1s; }
        .animate-delay-2 { animation-delay: 0.2s; }
        .animate-delay-3 { animation-delay: 0.3s; }
        .animate-delay-4 { animation-delay: 0.4s; }
        .animate-delay-5 { animation-delay: 0.5s; }
        .animate-delay-6 { animation-delay: 0.6s; }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-value.success {
            color: #10b981;
        }

        .alert {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .alert-title {
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 10px;
        }

        .alert-text {
            color: #991b1b;
        }

        .alert a {
            color: #dc2626;
            text-decoration: underline;
        }

        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-box p {
            color: #1e40af;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #1e293b;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .action-btn .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .container {
                padding: 0 15px;
            }

            .welcome h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">💰 EarningsLLC</div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['username']); ?>!
            <?php if (!$user['training_completed']): ?>
                <span class="training-badge">
                    <span>🎓</span>
                    <span>TRAINING</span>
                </span>
            <?php endif; ?>
            </span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome animate-slideInRight">
            <h1>Dashboard</h1>
            <p>Track your earnings, referrals, and training progress</p>
        </div>


        <div class="stats-grid">
            <div class="stat-card animate-fadeInUp animate-delay-1">
                <div class="stat-icon">💵</div>
                <div class="stat-label">Account Balance</div>
                <div class="stat-value success">$<?php echo number_format($user['balance'], 2); ?></div>
            </div>

            <div class="stat-card animate-fadeInUp animate-delay-2">
                <div class="stat-icon">🎓</div>
                <div class="stat-label">Training Earnings</div>
                <div class="stat-value success">$<?php echo number_format($trainingEarnings, 2); ?></div>
            </div>

            <div class="stat-card animate-fadeInUp animate-delay-3">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Referrals</div>
                <div class="stat-value"><?php echo $totalReferrals; ?></div>
            </div>
        </div>

        <div class="info-box animate-fadeInUp animate-delay-5" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #10b981;">
            <?php if ($user['referral_code']): ?>
                <?php
                $referralLink = 'https://' . $_SERVER['HTTP_HOST'] . '/register.php?ref=' . urlencode($user['referral_code']);
                ?>
                <p style="margin-bottom: 15px;"><strong style="color: #065f46;">Your Referral Code:</strong> <span style="font-size: 1.3em; color: #10b981; font-weight: bold; font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($user['referral_code']); ?></span></p>

                <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 2px dashed #10b981;">
                    <p style="margin-bottom: 8px; color: #065f46; font-weight: 600;">Your Referral Link:</p>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="referralLink" value="<?php echo htmlspecialchars($referralLink); ?>" readonly style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9em; background: #f9fafb; color: #374151;">
                        <button onclick="copyReferralLink()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: all 0.3s ease;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            📋 Copy Link
                        </button>
                    </div>
                    <p id="copyMessage" style="color: #10b981; margin-top: 8px; font-size: 0.85em; font-weight: 600; opacity: 0; transition: opacity 0.3s ease;"></p>
                </div>

                <p style="margin-top: 10px; color: #065f46;">Share this link with friends to earn commissions on their earnings!</p>
            <?php else: ?>
                <p><strong>Referral System Not Set Up</strong></p>
                <p style="margin-top: 10px;">
                    <a href="setup_referral_system.php" style="color: #10b981; text-decoration: underline;">Click here to set up your referral system</a> and start earning commissions from referrals!
                </p>
            <?php endif; ?>
        </div>

        <script>
        function copyReferralLink() {
            const linkInput = document.getElementById('referralLink');
            const message = document.getElementById('copyMessage');

            linkInput.select();
            linkInput.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                message.textContent = '✅ Link copied to clipboard!';
                message.style.opacity = '1';

                setTimeout(() => {
                    message.style.opacity = '0';
                }, 3000);
            } catch (err) {
                message.textContent = '❌ Failed to copy. Please copy manually.';
                message.style.opacity = '1';
            }
        }
        </script>

        <h2 style="margin-bottom: 20px; color: #1e293b;" class="animate-slideInRight animate-delay-6">Quick Actions</h2>
        <div class="quick-actions">
            <a href="training.php" class="action-btn animate-fadeInUp animate-delay-1">
                <div class="icon">📚</div>
                <div>Start Training</div>
            </a>
            <a href="tasks.php" class="action-btn animate-fadeInUp animate-delay-2">
                <div class="icon">✅</div>
                <div>View Tasks</div>
            </a>
            <a href="referrals.php" class="action-btn animate-fadeInUp animate-delay-3">
                <div class="icon">👥</div>
                <div>My Referrals</div>
            </a>
            <a href="withdraw.php" class="action-btn animate-fadeInUp animate-delay-4">
                <div class="icon">💳</div>
                <div>Withdraw</div>
            </a>
        </div>
    </div>
</body>
</html>
