<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$auth = new Auth();
$userId = getCurrentUserId();
$isAdmin = $auth->isAdmin($userId);

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_product':
            $brandName = sanitizeInput($_POST['brand_name'] ?? '');
            $productName = sanitizeInput($_POST['product_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $commission = floatval($_POST['commission'] ?? 0);
            $imageUrl = sanitizeInput($_POST['image_url'] ?? '');
            $displayOrder = intval($_POST['display_order'] ?? 0);

            if (empty($brandName) || empty($imageUrl)) {
                throw new Exception('Brand name and image URL are required');
            }

            $productId = generateUUID();
            $stmt = $db->prepare("INSERT INTO product_images (id, brand_name, product_name, description, price, commission, image_url, display_order, is_active) VALUES (:id, :brand_name, :product_name, :description, :price, :commission, :image_url, :display_order, TRUE)");
            $stmt->execute([
                ':id' => $productId,
                ':brand_name' => $brandName,
                ':product_name' => $productName,
                ':description' => $description,
                ':price' => $price,
                ':commission' => $commission,
                ':image_url' => $imageUrl,
                ':display_order' => $displayOrder
            ]);

            echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $productId]);
            break;

        case 'update_product':
            $productId = sanitizeInput($_POST['product_id'] ?? '');
            $brandName = sanitizeInput($_POST['brand_name'] ?? '');
            $productName = sanitizeInput($_POST['product_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $commission = floatval($_POST['commission'] ?? 0);
            $imageUrl = sanitizeInput($_POST['image_url'] ?? '');
            $displayOrder = intval($_POST['display_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;

            if (empty($productId)) {
                throw new Exception('Product ID is required');
            }

            $stmt = $db->prepare("UPDATE product_images SET brand_name = :brand_name, product_name = :product_name, description = :description, price = :price, commission = :commission, image_url = :image_url, display_order = :display_order, is_active = :is_active WHERE id = :id");
            $stmt->execute([
                ':id' => $productId,
                ':brand_name' => $brandName,
                ':product_name' => $productName,
                ':description' => $description,
                ':price' => $price,
                ':commission' => $commission,
                ':image_url' => $imageUrl,
                ':display_order' => $displayOrder,
                ':is_active' => $isActive
            ]);

            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            break;

        case 'delete_product':
            $productId = sanitizeInput($_POST['product_id'] ?? '');

            if (empty($productId)) {
                throw new Exception('Product ID is required');
            }

            $stmt = $db->prepare("DELETE FROM product_images WHERE id = :id");
            $stmt->execute([':id' => $productId]);

            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            break;

        case 'update_user_balance':
            $targetUserId = sanitizeInput($_POST['user_id'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $operation = sanitizeInput($_POST['operation'] ?? 'add');

            if (empty($targetUserId)) {
                throw new Exception('User ID is required');
            }

            $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $targetUserId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                $walletId = generateUUID();
                $stmt = $db->prepare("INSERT INTO wallets (id, user_id, balance, total_earnings) VALUES (:id, :user_id, 0.00, 0.00)");
                $stmt->execute([':id' => $walletId, ':user_id' => $targetUserId]);
                $wallet = ['id' => $walletId, 'balance' => 0, 'total_earnings' => 0];
            }

            if ($operation === 'add') {
                $stmt = $db->prepare("UPDATE wallets SET balance = balance + :amount, total_earnings = total_earnings + :amount WHERE user_id = :user_id");
            } else {
                $stmt = $db->prepare("UPDATE wallets SET balance = GREATEST(0, balance - :amount) WHERE user_id = :user_id");
            }
            $stmt->execute([':amount' => abs($amount), ':user_id' => $targetUserId]);

            $transactionId = generateUUID();
            $stmt = $db->prepare("INSERT INTO transactions (id, user_id, wallet_id, type, amount, status, description) VALUES (:id, :user_id, :wallet_id, :type, :amount, 'completed', :description)");
            $stmt->execute([
                ':id' => $transactionId,
                ':user_id' => $targetUserId,
                ':wallet_id' => $wallet['id'],
                ':type' => $operation === 'add' ? 'admin_credit' : 'admin_debit',
                ':amount' => abs($amount),
                ':description' => 'Admin adjustment: ' . ($operation === 'add' ? 'Added' : 'Removed') . ' $' . number_format(abs($amount), 2)
            ]);

            echo json_encode(['success' => true, 'message' => 'Balance updated successfully']);
            break;

        case 'update_user_vip':
            $targetUserId = sanitizeInput($_POST['user_id'] ?? '');
            $vipTierId = sanitizeInput($_POST['vip_tier_id'] ?? '');

            if (empty($targetUserId) || empty($vipTierId)) {
                throw new Exception('User ID and VIP tier ID are required');
            }

            $stmt = $db->prepare("UPDATE user_profiles SET vip_tier_id = :vip_tier_id WHERE id = :user_id");
            $stmt->execute([':vip_tier_id' => $vipTierId, ':user_id' => $targetUserId]);

            echo json_encode(['success' => true, 'message' => 'VIP tier updated successfully']);
            break;

        case 'toggle_user_status':
            $targetUserId = sanitizeInput($_POST['user_id'] ?? '');
            $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;

            if (empty($targetUserId)) {
                throw new Exception('User ID is required');
            }

            $stmt = $db->prepare("UPDATE user_profiles SET is_active = :is_active WHERE id = :user_id");
            $stmt->execute([':is_active' => $isActive, ':user_id' => $targetUserId]);

            echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
            break;

        case 'get_user_details':
            $targetUserId = sanitizeInput($_GET['user_id'] ?? '');

            if (empty($targetUserId)) {
                throw new Exception('User ID is required');
            }

            $stmt = $db->prepare("SELECT u.*, up.full_name, up.phone, up.vip_tier_id, vt.name as vip_name, w.balance, w.total_earnings FROM users u LEFT JOIN user_profiles up ON u.id = up.id LEFT JOIN vip_tiers vt ON up.vip_tier_id = vt.id LEFT JOIN wallets w ON u.id = w.user_id WHERE u.id = :user_id");
            $stmt->execute([':user_id' => $targetUserId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('User not found');
            }

            echo json_encode(['success' => true, 'user' => $user]);
            break;

        case 'update_site_settings':
            $settingKey = sanitizeInput($_POST['setting_key'] ?? '');
            $settingValue = sanitizeInput($_POST['setting_value'] ?? '');

            if (empty($settingKey)) {
                throw new Exception('Setting key is required');
            }

            $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");
            $stmt->execute([':key' => $settingKey, ':value' => $settingValue]);

            echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
