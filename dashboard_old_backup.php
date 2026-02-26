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

        .animation-showcase {
            position: relative;
            width: 100%;
            height: 400px;
            margin-bottom: 30px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .carousel-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .carousel-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transform: scale(0.9);
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
        }

        .carousel-slide.active {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }

        .ai-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carousel-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .carousel-dot.active {
            background: white;
            transform: scale(1.3);
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            color: #1e293b;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .carousel-arrow:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-arrow.prev {
            left: 20px;
        }

        .carousel-arrow.next {
            right: 20px;
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
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .action-btn.disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
            position: relative;
        }

        .action-btn.disabled:hover {
            transform: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-color: transparent;
        }

        .action-btn.disabled::after {
            content: '🔒';
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.2rem;
        }

        .action-btn .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 40px;
            border-radius: 16px;
            max-width: 600px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.4s ease;
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-header h2 {
            color: #1e293b;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .modal-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .modal-body {
            color: #475569;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .modal-body p {
            margin-bottom: 20px;
        }

        .modal-body strong {
            color: #1e293b;
            display: block;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .highlight-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .highlight-box p {
            color: #92400e;
            margin: 0;
            font-weight: 500;
        }

        .modal-footer {
            text-align: center;
        }

        .btn-close-modal {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .btn-close-modal:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
            }

            .logo {
                font-size: 1.2rem;
            }

            .user-info {
                flex-direction: column;
                gap: 10px;
                font-size: 0.9rem;
            }

            .container {
                padding: 0 15px;
                margin: 20px auto;
            }

            .welcome h1 {
                font-size: 1.5rem;
            }

            .animation-showcase {
                height: 250px;
                margin-bottom: 20px;
            }

            .carousel-arrow {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .carousel-arrow.prev {
                left: 10px;
            }

            .carousel-arrow.next {
                right: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-icon {
                font-size: 1.5rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .action-btn {
                padding: 15px 10px;
            }

            .action-btn .icon {
                font-size: 1.5rem;
                margin-bottom: 5px;
            }

            .modal-content {
                margin: 10% 20px;
                padding: 30px 20px;
            }

            .modal-header h2 {
                font-size: 1.4rem;
            }

            .modal-icon {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 12px;
            }

            .logo {
                font-size: 1rem;
            }

            .btn-logout {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .welcome h1 {
                font-size: 1.3rem;
            }

            .welcome p {
                font-size: 0.85rem;
            }

            .animation-showcase {
                height: 200px;
            }

            .carousel-arrow {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .carousel-controls {
                bottom: 10px;
            }

            .carousel-dot {
                width: 10px;
                height: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 1.3rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .action-btn {
                padding: 20px;
            }

            .info-box {
                padding: 15px;
            }

            #referralLink {
                font-size: 0.8rem;
                padding: 8px;
            }

            .modal-content {
                margin: 5% 10px;
                padding: 20px 15px;
            }

            .btn-close-modal {
                padding: 12px 30px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">💰 EarningsLLC</div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome animate-slideInRight">
            <h1>Dashboard</h1>
            <p>Track your earnings, referrals, and training progress</p>
        </div>

        <div class="animation-showcase">
            <div class="carousel-container">
                <div class="carousel-slide active">
                    <img src="public/AI.jpg" alt="AI Image 1" class="ai-image">
                </div>
                <div class="carousel-slide">
                    <img src="public/AI2.jpg" alt="AI Image 2" class="ai-image">
                </div>
                <div class="carousel-slide">
                    <img src="public/AI3.jpg" alt="AI Image 3" class="ai-image">
                </div>
                <div class="carousel-slide">
                    <img src="public/AI4.jpg" alt="AI Image 4" class="ai-image">
                </div>
                <div class="carousel-slide">
                    <img src="public/AI5.jpg" alt="AI Image 5" class="ai-image">
                </div>

                <button class="carousel-arrow prev" onclick="changeSlide(-1)">❮</button>
                <button class="carousel-arrow next" onclick="changeSlide(1)">❯</button>

                <div class="carousel-controls">
                    <span class="carousel-dot active" onclick="currentSlide(0)"></span>
                    <span class="carousel-dot" onclick="currentSlide(1)"></span>
                    <span class="carousel-dot" onclick="currentSlide(2)"></span>
                    <span class="carousel-dot" onclick="currentSlide(3)"></span>
                    <span class="carousel-dot" onclick="currentSlide(4)"></span>
                </div>
            </div>
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
            <a href="#" onclick="openTrainingModal(); return false;" class="action-btn animate-fadeInUp animate-delay-1">
                <div class="icon">📚</div>
                <div>Start Training</div>
            </a>
            <?php if ($user['training_completed']): ?>
                <a href="tasks.php" class="action-btn animate-fadeInUp animate-delay-2">
                    <div class="icon">✅</div>
                    <div>View Tasks</div>
                </a>
            <?php else: ?>
                <a href="#" onclick="showTrainingRequiredModal(); return false;" class="action-btn disabled animate-fadeInUp animate-delay-2">
                    <div class="icon">✅</div>
                    <div>View Tasks</div>
                </a>
            <?php endif; ?>
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

    <!-- Training Modal -->
    <div id="trainingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">🎓</div>
                <h2>Training Account Information</h2>
            </div>
            <div class="modal-body">
                <p><strong>CONTACT ADMIN OR YOUR INSTRUCTOR TO GUIDE YOU ON THE TRAINING.</strong></p>

                <div class="highlight-box">
                    <p>NB: YOU WILL BE GIVEN LOGINS FOR YOUR TRAINING ACCOUNT. MAKE SURE YOU SAVE YOUR PERSONAL ACCOUNT LOGINS AND NEVER SHARE YOUR TRAINING ACCOUNT INFO.</p>
                </div>

                <p><strong>COMPLETING TRAINING YOU WILL ALSO EARN</strong></p>
                <p>By completing your training, you will gain valuable skills and earn rewards for your progress!</p>
            </div>
            <div class="modal-footer">
                <button class="btn-close-modal" onclick="closeAndRedirect()">CLOSE</button>
            </div>
        </div>
    </div>

    <!-- Training Required Modal -->
    <div id="trainingRequiredModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">🔒</div>
                <h2>Training Required</h2>
            </div>
            <div class="modal-body">
                <p style="color: #dc2626; font-size: 1.2rem; font-weight: 600; text-align: center; margin-bottom: 20px;">
                    COMPLETE YOUR TRAINING TO START EARNING
                </p>

                <div class="highlight-box" style="background: #fee2e2; border-left-color: #dc2626;">
                    <p style="color: #7f1d1d;">You must complete your training before you can access tasks and start earning. Contact your instructor to begin your training journey!</p>
                </div>

                <p style="text-align: center; margin-top: 20px;">Once you complete your training, you'll unlock:</p>
                <ul style="list-style: none; padding: 0; margin-top: 15px;">
                    <li style="padding: 8px 0; color: #065f46;">✓ Access to all earning tasks</li>
                    <li style="padding: 8px 0; color: #065f46;">✓ Higher earning potential</li>
                    <li style="padding: 8px 0; color: #065f46;">✓ Advanced features and tools</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button class="btn-close-modal" onclick="closeTrainingRequiredModal()">UNDERSTOOD</button>
            </div>
        </div>
    </div>

    <script>
        // Carousel functionality
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');

        function showSlide(index) {
            // Remove active class from all slides and dots
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            // Add active class to current slide and dot
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function changeSlide(direction) {
            currentSlideIndex += direction;

            if (currentSlideIndex >= slides.length) {
                currentSlideIndex = 0;
            } else if (currentSlideIndex < 0) {
                currentSlideIndex = slides.length - 1;
            }

            showSlide(currentSlideIndex);
        }

        function currentSlide(index) {
            currentSlideIndex = index;
            showSlide(currentSlideIndex);
        }

        // Auto-advance carousel every 5 seconds
        setInterval(() => {
            changeSlide(1);
        }, 5000);

        // Training modal functions
        function openTrainingModal() {
            document.getElementById('trainingModal').style.display = 'block';
        }

        function closeAndRedirect() {
            // Log out from personal account
            window.location.href = 'logout.php?redirect=/TRAINING/login.php';
        }

        // Training Required modal functions
        function showTrainingRequiredModal() {
            document.getElementById('trainingRequiredModal').style.display = 'block';
        }

        function closeTrainingRequiredModal() {
            document.getElementById('trainingRequiredModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const trainingModal = document.getElementById('trainingModal');
            const trainingRequiredModal = document.getElementById('trainingRequiredModal');

            if (event.target == trainingModal) {
                trainingModal.style.display = 'none';
            }
            if (event.target == trainingRequiredModal) {
                trainingRequiredModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
