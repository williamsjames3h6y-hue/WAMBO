<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Register new user and create training account
    public function register($email, $password, $fullName, $referralCode = '') {
        try {
            // Check if user already exists
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Generate training account credentials
            $trainingEmail = $this->generateTrainingEmail();
            $trainingPassword = $this->generateRandomPassword();

            // Create training account first
            $trainingResult = $this->createTrainingAccount($trainingEmail, $trainingPassword, $fullName);
            if (!$trainingResult['success']) {
                return ['success' => false, 'message' => 'Failed to create training account'];
            }

            $trainingAccountId = $trainingResult['user_id'];

            // Validate referral code if provided
            $referrerId = null;
            if (!empty($referralCode)) {
                try {
                    $query = "SELECT id FROM users WHERE referral_code = :code LIMIT 1";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':code', $referralCode);
                    $stmt->execute();
                    $referrer = $stmt->fetch();
                    if ($referrer) {
                        $referrerId = $referrer['id'];
                    }
                } catch (PDOException $e) {
                    // Referral code column might not exist, continue without error
                }
            }

            // Create user
            $userId = generateUUID();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Check if training_completed column exists
            $hasTrainingColumn = false;
            try {
                $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'training_completed'");
                $hasTrainingColumn = $checkCol->rowCount() > 0;
            } catch (PDOException $e) {
                // Column doesn't exist
            }

            // Check if username column exists
            $hasUsernameColumn = false;
            try {
                $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'username'");
                $hasUsernameColumn = $checkCol->rowCount() > 0;
            } catch (PDOException $e) {
                // Column doesn't exist
            }

            // Check for referral system columns
            $hasReferralCodeColumn = false;
            $hasReferredByColumn = false;
            $hasTrainingAccountIdColumn = false;
            try {
                $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
                $hasReferralCodeColumn = $checkCol->rowCount() > 0;
                $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
                $hasReferredByColumn = $checkCol->rowCount() > 0;
                $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'training_account_id'");
                $hasTrainingAccountIdColumn = $checkCol->rowCount() > 0;
            } catch (PDOException $e) {
                // Columns don't exist
            }

            // Generate unique referral code for new user
            $userReferralCode = null;
            if ($hasReferralCodeColumn) {
                $userReferralCode = strtoupper(substr(md5($userId . time() . rand()), 0, 10));
            }

            // Build INSERT query based on available columns
            $fields = ['id', 'email', 'password_hash', 'email_confirmed'];
            $values = [':id', ':email', ':password_hash', 'TRUE'];

            if ($hasTrainingColumn) {
                $fields[] = 'training_completed';
                $values[] = '1';  // Set to 1 (true) for personal accounts
            }

            if ($hasUsernameColumn) {
                $fields[] = 'username';
                $values[] = ':username';
            }

            if ($hasReferralCodeColumn) {
                $fields[] = 'referral_code';
                $values[] = ':referral_code';
            }

            if ($hasReferredByColumn && $referrerId) {
                $fields[] = 'referred_by';
                $values[] = ':referred_by';
            }

            if ($hasTrainingAccountIdColumn) {
                $fields[] = 'training_account_id';
                $values[] = ':training_account_id';
            }

            $query = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $passwordHash);

            if ($hasUsernameColumn) {
                // Extract username from email
                $username = explode('@', $email)[0];
                $stmt->bindParam(':username', $username);
            }

            if ($hasReferralCodeColumn) {
                $stmt->bindParam(':referral_code', $userReferralCode);
            }

            if ($hasReferredByColumn && $referrerId) {
                $stmt->bindParam(':referred_by', $referrerId);
            }

            if ($hasTrainingAccountIdColumn) {
                $stmt->bindParam(':training_account_id', $trainingAccountId);
            }

            $stmt->execute();

            // Get VIP 1 tier
            $query = "SELECT id FROM vip_tiers WHERE level = 1 LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $vipTier = $stmt->fetch();

            // Create user profile
            $query = "INSERT INTO user_profiles (id, email, full_name, vip_tier_id) VALUES (:id, :email, :full_name, :vip_tier_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':full_name', $fullName);
            $stmt->bindParam(':vip_tier_id', $vipTier['id']);
            $stmt->execute();

            // Create wallet
            $walletId = generateUUID();
            $query = "INSERT INTO wallets (id, user_id, balance, total_earnings) VALUES (:id, :user_id, 0.00, 0.00)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $walletId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            // Create referral record if referred by someone
            if ($referrerId) {
                try {
                    // Check if referrals table exists
                    $checkTable = $this->db->query("SHOW TABLES LIKE 'referrals'");
                    if ($checkTable->rowCount() > 0) {
                        $referralId = generateUUID();
                        $query = "INSERT INTO referrals (id, referrer_id, referred_id, status) VALUES (:id, :referrer_id, :referred_id, 'active')";
                        $stmt = $this->db->prepare($query);
                        $stmt->bindParam(':id', $referralId);
                        $stmt->bindParam(':referrer_id', $referrerId);
                        $stmt->bindParam(':referred_id', $userId);
                        $stmt->execute();
                    }
                } catch (PDOException $e) {
                    // Referrals table might not exist, continue without error
                }
            }

            // Send notification to Telegram
            require_once __DIR__ . '/telegram.php';
            $telegram = new TelegramNotifier();
            $telegram->sendTrainingCredentials($fullName, $email, $trainingEmail, $trainingPassword);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId,
                'training_account' => [
                    'email' => $trainingEmail,
                    'password' => $trainingPassword
                ]
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    // Generate unique training email
    private function generateTrainingEmail() {
        $randomString = strtolower(substr(md5(uniqid(rand(), true)), 0, 10));
        return $randomString . '@training.com';
    }

    // Generate random password
    private function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    // Create training account
    private function createTrainingAccount($email, $password, $fullName) {
        try {
            $userId = generateUUID();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Check available columns
            $hasTrainingColumn = false;
            try {
                $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'training_completed'");
                $hasTrainingColumn = $checkCol->rowCount() > 0;
            } catch (PDOException $e) {}

            $hasUsernameColumn = false;
            try {
                $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'username'");
                $hasUsernameColumn = $checkCol->rowCount() > 0;
            } catch (PDOException $e) {}

            // Build INSERT query
            $fields = ['id', 'email', 'password_hash', 'email_confirmed'];
            $values = [':id', ':email', ':password_hash', 'TRUE'];

            if ($hasTrainingColumn) {
                $fields[] = 'training_completed';
                $values[] = '0';  // Not completed training yet
            }

            if ($hasUsernameColumn) {
                $fields[] = 'username';
                $values[] = ':username';
            }

            $query = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $passwordHash);

            if ($hasUsernameColumn) {
                $username = explode('@', $email)[0];
                $stmt->bindParam(':username', $username);
            }

            $stmt->execute();

            // Get VIP 1 tier
            $query = "SELECT id FROM vip_tiers WHERE level = 1 LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $vipTier = $stmt->fetch();

            // Create user profile
            $query = "INSERT INTO user_profiles (id, email, full_name, vip_tier_id) VALUES (:id, :email, :full_name, :vip_tier_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':full_name', $fullName);
            $stmt->bindParam(':vip_tier_id', $vipTier['id']);
            $stmt->execute();

            // Create wallet
            $walletId = generateUUID();
            $query = "INSERT INTO wallets (id, user_id, balance, total_earnings) VALUES (:id, :user_id, 0.00, 0.00)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $walletId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            return ['success' => true, 'user_id' => $userId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Training account creation failed: ' . $e->getMessage()];
        }
    }

    // Login user
    public function login($email, $password) {
        try {
            $query = "SELECT id, email, password_hash FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            $user = $stmt->fetch();

            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            return ['success' => true, 'message' => 'Login successful', 'user_id' => $user['id']];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    // Logout user
    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }

    // Get current user profile
    public function getUserProfile($userId) {
        try {
            $query = "SELECT up.*, vt.* FROM user_profiles up
                     LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id
                     WHERE up.id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return null;
            }

            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    // Check if user is admin
    public function isAdmin($userId) {
        try {
            $query = "SELECT id FROM admin_users WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
