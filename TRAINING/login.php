<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Query for training accounts (identified by email pattern or just allow login)
            $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['username'] = $user['username'] ?? explode('@', $user['email'])[0];
                $_SESSION['is_training'] = true;
                header('Location: /training/tasks.php');
                exit;
            } else {
                $error = 'Invalid email or password. Please use your training account credentials.';
            }
        } catch (PDOException $e) {
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Login - EarningsLLC</title>
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
            background: radial-gradient(circle, rgba(249, 115, 22, 0.15), transparent);
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
            background: radial-gradient(circle, rgba(245, 158, 11, 0.15), transparent);
            border-radius: 50%;
            bottom: -200px;
            right: -200px;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .login-container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            min-height: 650px;
            animation: fadeIn 0.6s ease-out;
            position: relative;
            z-index: 1;
        }

        .login-form-section {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-image-section {
            flex: 1;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-image-section::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: drift 25s linear infinite;
        }

        @keyframes drift {
            from { transform: translate(0, 0) rotate(0deg); }
            to { transform: translate(30px, 30px) rotate(360deg); }
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

        .training-logo {
            width: 250px;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0 4px 20px rgba(0, 0, 0, 0.3));
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.9; transform: scale(1.02); }
        }

        .image-content h2 {
            font-size: 1.9rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .image-content p {
            font-size: 0.95rem;
            line-height: 1.6;
            opacity: 0.95;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            text-align: center;
        }

        .training-badge {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
        }

        h1 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 8px;
            font-weight: 700;
            text-align: center;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 0.85rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 500;
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
            color: #94a3b8;
            font-size: 1.1rem;
        }

        input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        input:focus {
            outline: none;
            border-color: #f97316;
            background: white;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(249, 115, 22, 0.5);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #dc2626;
        }

        .links {
            text-align: center;
            margin-top: 30px;
        }

        .links a {
            color: #f97316;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #ea580c;
            text-decoration: underline;
        }

        .divider {
            margin: 20px 0;
            text-align: center;
            color: #94a3b8;
        }

        .support-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #f97316;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            transition: color 0.3s ease;
        }

        .support-link:hover {
            color: #ea580c;
        }

        .back-to-main {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .back-to-main a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-to-main a:hover {
            color: #475569;
        }

        @media (max-width: 968px) {
            .login-container {
                flex-direction: column;
            }

            .login-image-section {
                order: -1;
                min-height: 250px;
            }

            .login-form-section {
                padding: 40px 30px;
            }

            .image-content h2 {
                font-size: 1.5rem;
            }

            h1 {
                font-size: 1.3rem;
            }

            body {
                align-items: flex-start;
            }

            .login-container {
                margin: 20px auto;
                min-height: auto;
            }

            .training-logo {
                width: 180px;
            }
        }

        @media (max-width: 640px) {
            body::before,
            body::after {
                display: none;
            }

            .login-container {
                width: 100%;
                padding: 15px;
            }

            .login-form-section {
                padding: 30px 25px;
                border-radius: 16px;
            }

            .login-image-section {
                min-height: 200px;
                border-radius: 16px;
            }

            .logo {
                font-size: 1rem;
                flex-direction: column;
                gap: 8px;
            }

            h1 {
                font-size: 1.2rem;
            }

            .subtitle {
                font-size: 0.8rem;
            }

            .image-content h2 {
                font-size: 1.3rem;
            }

            .image-content p {
                font-size: 0.85rem;
            }

            .training-logo {
                width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Login Form -->
        <div class="login-form-section">
            <div class="logo">
                <span>🎓 EarningsLLC</span>
                <span class="training-badge">Training</span>
            </div>
            <h1>Training Account Login</h1>
            <p class="subtitle">Use your training account credentials provided by your instructor</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Training Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="training@example.com"
                            required
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Training Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn-login">Sign In to Training</button>
            </form>

            <div class="links">
                <a href="#" class="support-link">
                    <span>✅</span>
                    <span>Need help? Contact your instructor</span>
                </a>
            </div>

            <div class="back-to-main">
                <a href="../login.php">← Back to Personal Account Login</a>
            </div>
        </div>

        <!-- Right Side - Image/Graphic -->
        <div class="login-image-section">
            <div class="floating-shapes">
                <div class="shape"></div>
                <div class="shape"></div>
                <div class="shape"></div>
            </div>
            <div class="image-content">
                <img src="public/image.png" alt="Training Logo" class="training-logo">
                <h2>Welcome to Training</h2>
                <p>Learn the skills you need to succeed and earn while you train. Complete your training modules and start earning rewards.</p>
            </div>
        </div>
    </div>
</body>
</html>
