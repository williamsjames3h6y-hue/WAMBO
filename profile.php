<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/vip_badge.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$database = new Database();
$db = $database->getConnection();

$userId = $_SESSION['user_id'];

$query = "SELECT up.*, vt.level as vip_level, vt.name as vip_name, vt.daily_task_limit, vt.commission_rate,
          w.balance, w.total_earnings, u.email, u.referral_code, u.created_at as joined_date
          FROM user_profiles up
          LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id
          LEFT JOIN wallets w ON w.user_id = up.id
          LEFT JOIN users u ON u.id = up.id
          WHERE up.id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$profile = $stmt->fetch();

if (!$profile) {
    redirect('/dashboard.php');
}

$vipLevel = $profile['vip_level'] ?? 1;
$referralQuery = "SELECT COUNT(*) as total FROM referrals WHERE referrer_id = :user_id";
$referralStmt = $db->prepare($referralQuery);
$referralStmt->bindParam(':user_id', $userId);
$referralStmt->execute();
$referralData = $referralStmt->fetch();
$totalReferrals = $referralData['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - EarningsLLC</title>
    <link rel="icon" type="image/jpeg" href="/public/logo.jpg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            color: white;
        }

        .header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #3b82f6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .profile-header {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .badge-container {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }

        .profile-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .info-icon {
            font-size: 1.8rem;
        }

        .info-title {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .info-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .info-value.success {
            color: #10b981;
        }

        .vip-benefits {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .vip-benefits h2 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .benefit-icon {
            font-size: 1.5rem;
            min-width: 40px;
            text-align: center;
        }

        .benefit-text {
            flex: 1;
        }

        .benefit-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .benefit-value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .profile-header {
                padding: 30px 20px;
            }

            .profile-header h1 {
                font-size: 1.5rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="back-btn">
            <span>←</span>
            <span>Back to Dashboard</span>
        </a>
    </div>

    <div class="container">
        <div class="profile-header">
            <div class="badge-container">
                <?php echo renderVipBadge($vipLevel, 'large'); ?>
            </div>
            <h1><?php echo htmlspecialchars($profile['full_name']); ?></h1>
            <p><?php echo htmlspecialchars($profile['email']); ?></p>
            <p style="margin-top: 5px;">Member since <?php echo date('F Y', strtotime($profile['joined_date'])); ?></p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-icon">💰</div>
                    <div class="info-title">Account Balance</div>
                </div>
                <div class="info-value success">$<?php echo number_format($profile['balance'], 2); ?></div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-icon">📊</div>
                    <div class="info-title">Total Earnings</div>
                </div>
                <div class="info-value success">$<?php echo number_format($profile['total_earnings'], 2); ?></div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-icon">👥</div>
                    <div class="info-title">Referrals</div>
                </div>
                <div class="info-value"><?php echo $totalReferrals; ?></div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-icon">🎁</div>
                    <div class="info-title">Referral Code</div>
                </div>
                <div class="info-value" style="font-size: 1.2rem;"><?php echo htmlspecialchars($profile['referral_code']); ?></div>
            </div>
        </div>

        <div class="vip-benefits">
            <h2>
                <?php echo renderVipBadgeInline($vipLevel); ?>
                <span>VIP Benefits</span>
            </h2>

            <div class="benefit-item">
                <div class="benefit-icon">📋</div>
                <div class="benefit-text">
                    <div class="benefit-label">Daily Task Limit</div>
                    <div class="benefit-value"><?php echo $profile['daily_task_limit'] ?? 40; ?> tasks/day</div>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon">💵</div>
                <div class="benefit-text">
                    <div class="benefit-label">Commission Rate</div>
                    <div class="benefit-value"><?php echo number_format($profile['commission_rate'] ?? 0.5, 2); ?>%</div>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon">🎯</div>
                <div class="benefit-text">
                    <div class="benefit-label">VIP Status</div>
                    <div class="benefit-value"><?php echo htmlspecialchars($profile['vip_name'] ?? 'VIP Level ' . $vipLevel); ?></div>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon">⚡</div>
                <div class="benefit-text">
                    <div class="benefit-label">Priority Support</div>
                    <div class="benefit-value">24/7 Available</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
