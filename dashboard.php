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

// Fetch user data with profile
try {
    $query = "SELECT u.*, up.full_name, w.balance
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
    $referralCode = $user['referral_code'] ?? null;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
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
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 50%, #2563eb 100%);
            min-height: 100vh;
            color: white;
            padding-bottom: 80px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: rgba(30, 58, 138, 0.8);
            backdrop-filter: blur(10px);
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(37, 99, 235, 0.3);
            padding: 8px 16px;
            border-radius: 20px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .menu-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
        }

        .container {
            max-width: 480px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .carousel-container {
            margin: 20px 0;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 16px;
            padding: 20px;
            min-height: 200px;
            position: relative;
        }

        .carousel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .carousel-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
        }

        .carousel-content {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 120px;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .carousel-nav.prev {
            left: 10px;
        }

        .carousel-nav.next {
            right: 10px;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
        }

        .dot.active {
            background: white;
            width: 24px;
            border-radius: 4px;
        }

        .welcome-section {
            margin: 24px 0;
        }

        .welcome-text {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .wave-emoji {
            animation: wave 1s ease-in-out infinite;
        }

        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(20deg); }
            75% { transform: rotate(-20deg); }
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin: 24px 0;
        }

        .action-btn {
            background: rgba(37, 99, 235, 0.9);
            border: none;
            border-radius: 12px;
            padding: 16px 12px;
            color: white;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .action-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .action-icon {
            width: 32px;
            height: 32px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-label {
            font-size: 11px;
            font-weight: 500;
            text-align: center;
        }

        .membership-section {
            margin: 32px 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
        }

        .view-more {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }

        .membership-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .membership-card {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
        }

        .membership-card:hover {
            background: rgba(15, 23, 42, 0.8);
            transform: translateY(-4px);
        }

        .membership-icon {
            width: 48px;
            height: 48px;
            background: rgba(37, 99, 235, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .membership-label {
            font-size: 12px;
            font-weight: 500;
            text-align: center;
        }

        .referrals-section {
            margin: 32px 0;
        }

        .referral-list {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 16px;
            max-height: 300px;
            overflow-y: auto;
        }

        .referral-item {
            padding: 12px;
            background: rgba(37, 99, 235, 0.2);
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .referral-name {
            font-weight: 600;
            font-size: 14px;
        }

        .referral-date {
            font-size: 12px;
            opacity: 0.7;
        }

        .no-referrals {
            text-align: center;
            padding: 32px;
            opacity: 0.7;
        }

        @media (max-width: 480px) {
            .quick-actions {
                gap: 12px;
            }

            .action-btn {
                padding: 12px 8px;
            }

            .action-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="user-badge">
            <div class="user-avatar">
                <?php echo strtoupper(substr($fullName, 0, 1)); ?>
            </div>
            <span>Personal</span>
        </div>
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
    </div>

    <div class="container">
        <!-- Carousel -->
        <div class="carousel-container">
            <div class="carousel-header">
                <div class="carousel-title">
                    <span>🎯</span>
                    <span>Automation 2</span>
                </div>
            </div>
            <div class="carousel-content">
                <p style="opacity: 0.8; text-align: center;">Complete tasks to earn rewards</p>
            </div>
            <button class="carousel-nav prev" onclick="prevSlide()">‹</button>
            <button class="carousel-nav next" onclick="nextSlide()">›</button>
            <div class="carousel-dots">
                <div class="dot active"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
        </div>

        <!-- Welcome -->
        <div class="welcome-section">
            <div class="welcome-text">
                Welcome, <?php echo htmlspecialchars($fullName); ?>
                <span class="wave-emoji">👋</span>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="/profile.php" class="action-btn">
                <div class="action-icon">⚙️</div>
                <div class="action-label">Customer Care</div>
            </a>
            <a href="/tasks.php" class="action-btn">
                <div class="action-icon">🎯</div>
                <div class="action-label">Affiliate</div>
            </a>
            <a href="/payment_methods.php" class="action-btn">
                <div class="action-icon">💳</div>
                <div class="action-label">Payment Method</div>
            </a>
            <a href="#" class="action-btn">
                <div class="action-icon">❓</div>
                <div class="action-label">FAQ</div>
            </a>
            <a href="/profile.php" class="action-btn">
                <div class="action-icon">ℹ️</div>
                <div class="action-label">About Us</div>
            </a>
        </div>

        <!-- Membership Level -->
        <div class="membership-section">
            <div class="section-header">
                <div class="section-title">Membership Level</div>
                <a href="#" class="view-more">View More</a>
            </div>
            <div class="membership-grid">
                <a href="/tasks.php" class="membership-card">
                    <div class="membership-icon">🏠</div>
                    <div class="membership-label">Home</div>
                </a>
                <a href="/profile.php" class="membership-card">
                    <div class="membership-icon">⭐</div>
                    <div class="membership-label">Stats</div>
                </a>
                <a href="/payment_methods.php" class="membership-card">
                    <div class="membership-icon">💰</div>
                    <div class="membership-label">Wallet</div>
                </a>
                <a href="/profile.php" class="membership-card">
                    <div class="membership-icon">📊</div>
                    <div class="membership-label">Record</div>
                </a>
            </div>
        </div>

        <!-- Referrals -->
        <div class="referrals-section">
            <div class="section-header">
                <div class="section-title">My Referrals (<?php echo $totalReferrals; ?>)</div>
                <?php if ($referralCode): ?>
                    <span class="view-more">Code: <?php echo $referralCode; ?></span>
                <?php endif; ?>
            </div>
            <div class="referral-list">
                <?php if (count($referrals) > 0): ?>
                    <?php foreach ($referrals as $referral): ?>
                        <div class="referral-item">
                            <div>
                                <div class="referral-name">
                                    <?php echo htmlspecialchars($referral['referred_name'] ?? explode('@', $referral['referred_email'])[0]); ?>
                                </div>
                                <div class="referral-date">
                                    <?php echo date('M d, Y', strtotime($referral['created_at'])); ?>
                                </div>
                            </div>
                            <div style="font-size: 12px; opacity: 0.8;">
                                <?php echo ucfirst($referral['status']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-referrals">
                        <p>No referrals yet</p>
                        <p style="font-size: 14px; margin-top: 8px;">Share your referral code to earn commissions!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let currentSlide = 0;
        const dots = document.querySelectorAll('.dot');

        function updateDots() {
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % dots.length;
            updateDots();
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + dots.length) % dots.length;
            updateDots();
        }

        function toggleMenu() {
            alert('Menu clicked');
        }

        // Auto-advance carousel
        setInterval(nextSlide, 5000);
    </script>
</body>
</html>
