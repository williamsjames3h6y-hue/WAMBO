<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Register new user
    public function register($email, $password, $fullName) {
        try {
            // Check if user already exists
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Create user
            $userId = generateUUID();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO users (id, email, password_hash, email_confirmed) VALUES (:id, :email, :password_hash, TRUE)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $passwordHash);
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

            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $userId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
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
