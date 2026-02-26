<?php
require_once __DIR__ . '/config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('/dashboard');
}

$error = '';
$loading = false;

// Capture referral code from URL
$referralCode = isset($_GET['ref']) ? sanitizeInput($_GET['ref']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = sanitizeInput($_POST['fullName'] ?? '');
    $referralCode = sanitizeInput($_POST['referral_code'] ?? '');

    if (!empty($email) && !empty($password) && !empty($fullName)) {
        require_once __DIR__ . '/includes/auth.php';
        $auth = new Auth();
        $result = $auth->register($email, $password, $fullName, $referralCode);

        if ($result['success']) {
            // Auto-login after registration
            $loginResult = $auth->login($email, $password);
            if ($loginResult['success']) {
                redirect('/dashboard');
            }
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - EarningsLLC</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
            padding: 30px 15px;
        }

        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15), transparent);
            border-radius: 50%;
            top: -250px;
            left: -250px;
            animation: float 8s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.15), transparent);
            border-radius: 50%;
            bottom: -200px;
            right: -200px;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes drift {
            from { transform: translate(0, 0) rotate(0deg); }
            to { transform: translate(30px, 30px) rotate(360deg); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .register-container {
            display: flex;
            width: 95%;
            max-width: 1400px;
            background: transparent;
            border-radius: 24px;
            overflow: visible;
            min-height: 700px;
            animation: fadeIn 0.6s ease-out;
            position: relative;
            z-index: 1;
            gap: 40px;
        }

        .register-form-section {
            flex: 0 0 450px;
            max-width: 450px;
            padding: 45px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            animation: slideInLeft 0.8s ease-out;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            margin: auto 0;
        }

        .register-image-section {
            flex: 1;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            animation: slideInRight 0.8s ease-out;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .register-image-section::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: drift 25s linear infinite;
        }

        .register-image-section::after {
            content: '💰';
            position: absolute;
            font-size: 12rem;
            opacity: 0.08;
            animation: float 7s ease-in-out infinite;
            filter: blur(2px);
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 15%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            left: 80%;
            animation: float 8s ease-in-out infinite reverse;
        }

        .shape:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 80%;
            left: 30%;
            animation: float 7s ease-in-out infinite;
        }

        .image-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            padding: 50px;
            animation: fadeIn 1.2s ease-out;
        }

        .image-content h2 {
            font-size: 1.9rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: pulse 4s ease-in-out infinite;
            background: linear-gradient(to right, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .image-content p {
            font-size: 0.95rem;
            line-height: 1.6;
            opacity: 0.95;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .feature-list {
            margin-top: 40px;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            animation: fadeIn 1.5s ease-out;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(10px);
        }

        .feature-icon {
            font-size: 1.8rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
            height: 50px;
        }

        .logo {
            font-size: 1.2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 10px;
            animation: slideInLeft 0.6s ease-out;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        h1 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 8px;
            font-weight: 800;
            animation: slideInLeft 0.7s ease-out;
            text-align: center;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
            font-size: 0.85rem;
            animation: slideInLeft 0.8s ease-out;
            text-align: center;
        }

        .form-group {
            margin-bottom: 22px;
            animation: fadeIn 1s ease-out;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }

        label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            z-index: 1;
        }

        input {
            width: 100%;
            padding: 13px 16px 13px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
            position: relative;
        }

        input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        input:focus ~ .input-icon {
            transform: translateY(-50%) scale(1.15);
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 10px;
            animation: fadeIn 1.2s ease-out;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.5);
        }

        .btn-register:active {
            transform: translateY(-1px);
        }

        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            border-left: 5px solid #dc2626;
            animation: fadeIn 0.5s ease-out;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.2);
        }

        .links {
            text-align: center;
            margin-top: 25px;
            animation: fadeIn 1.3s ease-out;
        }

        .links a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            font-size: 0.9rem;
        }

        .links a:hover {
            color: #2563eb;
            transform: translateY(-2px);
        }

        .divider {
            margin: 15px 0;
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .support-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
            margin-top: 12px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .support-link:hover {
            color: #059669;
            transform: scale(1.05);
        }

        @media (max-width: 1024px) {
            .register-container {
                flex-direction: column;
                max-width: 90%;
                gap: 30px;
                padding: 20px 0;
            }

            .register-form-section {
                flex: none;
                max-width: 100%;
                width: 100%;
                padding: 35px 30px;
                margin: 0;
            }

            .register-image-section {
                order: -1;
                min-height: 300px;
                width: 100%;
            }

            .image-content {
                padding: 40px 30px;
            }

            .image-content h2 {
                font-size: 2rem;
            }

            h1 {
                font-size: 1.7rem;
            }

            .feature-list {
                margin-top: 25px;
            }

            .feature-item {
                font-size: 0.95rem;
                padding: 12px 15px;
            }
        }

        @media (max-width: 640px) {
            body::before,
            body::after {
                display: none;
            }

            .register-container {
                width: 100%;
                padding: 15px;
                gap: 20px;
            }

            .register-form-section {
                padding: 30px 25px;
                border-radius: 16px;
            }

            .register-image-section {
                min-height: 250px;
                border-radius: 16px;
            }

            .logo {
                font-size: 1.2rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .subtitle {
                font-size: 0.9rem;
                margin-bottom: 25px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            input {
                padding: 12px 14px 12px 44px;
                font-size: 0.9rem;
            }

            .input-icon {
                font-size: 1rem;
                left: 14px;
            }

            .btn-register {
                padding: 13px;
                font-size: 0.95rem;
            }

            .image-content {
                padding: 30px 20px;
            }

            .image-content h2 {
                font-size: 1.6rem;
            }

            .image-content p {
                font-size: 1rem;
            }

            .feature-list {
                margin-top: 20px;
            }

            .feature-item {
                font-size: 0.9rem;
                padding: 10px 12px;
                gap: 12px;
            }

            .feature-icon {
                font-size: 1.4rem;
                min-width: 40px;
                height: 40px;
                padding: 8px;
            }

            .links {
                margin-top: 20px;
            }

            .links a {
                font-size: 0.85rem;
            }

            body {
                align-items: flex-start;
                min-height: 100vh;
            }

            .register-container {
                margin: 20px auto;
                min-height: auto;
            }

            html {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Left Side - Registration Form -->
        <div class="register-form-section">
            <div class="logo">
                <span>💰</span>
                <span>EarningsLLC</span>
            </div>
            <h1>Create Account</h1>
            <p class="subtitle">Join thousands of users earning money today</p>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Hidden field for referral code -->
                <input type="hidden" name="referral_code" value="<?php echo htmlspecialchars($referralCode); ?>">

                <?php if (!empty($referralCode)): ?>
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 600;">
                        🎁 You're signing up with a referral code!
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            id="fullName"
                            name="fullName"
                            placeholder="John Doe"
                            required
                            value="<?php echo htmlspecialchars($_POST['fullName'] ?? ''); ?>"
                        >
                        <span class="input-icon">👤</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            required
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                        <span class="input-icon">📧</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            required
                            minlength="6"
                        >
                        <span class="input-icon">🔒</span>
                    </div>
                </div>

                <button type="submit" class="btn-register">Create Account</button>
            </form>

            <div class="links">
                <a href="login.php">Already have an account? Sign in</a>
                <div class="divider">or</div>
                <a href="https://t.me/EARNINGSLLCONLINECS1" target="_blank" class="support-link">
                    <span>✅</span>
                    <span>Need help? Contact Support</span>
                </a>
            </div>
        </div>

        <!-- Right Side - Image/Graphic -->
        <div class="register-image-section">
            <div class="floating-shapes">
                <div class="shape"></div>
                <div class="shape"></div>
                <div class="shape"></div>
            </div>
            <div class="image-content">
                <h2>Start Earning Today</h2>
                <p>Join thousands of users making money with our platform. Complete tasks, refer friends, and watch your earnings grow.</p>

                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <span>Complete simple tasks and earn</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">👥</span>
                        <span>Refer friends for bonuses</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">💳</span>
                        <span>Easy and secure withdrawals</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🎓</span>
                        <span>Free training included</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
