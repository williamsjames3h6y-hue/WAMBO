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
    if (!isset($user['referral_code'])) {
        $user['referral_code'] = 'N/A';
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
        <div class="welcome">
            <h1>Dashboard</h1>
            <p>Track your earnings, referrals, and training progress</p>
        </div>


        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-label">Account Balance</div>
                <div class="stat-value success">$<?php echo number_format($user['balance'], 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-label">Training Earnings</div>
                <div class="stat-value success">$<?php echo number_format($trainingEarnings, 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Referrals</div>
                <div class="stat-value"><?php echo $totalReferrals; ?></div>
            </div>
        </div>

        <div class="info-box">
            <p><strong>Your Referral Code:</strong> <?php echo htmlspecialchars($user['referral_code']); ?></p>
            <p style="margin-top: 10px;">Share this code with friends to earn commissions!</p>
        </div>

        <h2 style="margin-bottom: 20px; color: #1e293b;">Quick Actions</h2>
        <div class="quick-actions">
            <a href="training.php" class="action-btn">
                <div class="icon">📚</div>
                <div>Start Training</div>
            </a>
            <a href="tasks.php" class="action-btn">
                <div class="icon">✅</div>
                <div>View Tasks</div>
            </a>
            <a href="referrals.php" class="action-btn">
                <div class="icon">👥</div>
                <div>My Referrals</div>
            </a>
            <a href="withdraw.php" class="action-btn">
                <div class="icon">💳</div>
                <div>Withdraw</div>
            </a>
        </div>
    </div>
</body>
</html>
