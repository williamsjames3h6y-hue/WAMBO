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
        case 'add_task':
            $brandName = sanitizeInput($_POST['brand_name'] ?? '');
            $earningAmount = floatval($_POST['earning_amount'] ?? 0);
            $imageUrl = sanitizeInput($_POST['image_url'] ?? '');
            $taskOrder = intval($_POST['task_order'] ?? 0);
            $vipLevelRequired = intval($_POST['vip_level_required'] ?? 1);

            if (empty($brandName) || empty($imageUrl)) {
                throw new Exception('Brand name and image URL are required');
            }

            $taskId = generateUUID();
            $stmt = $db->prepare("INSERT INTO admin_tasks (id, brand_name, earning_amount, image_url, task_order, vip_level_required) VALUES (:id, :brand_name, :earning_amount, :image_url, :task_order, :vip_level_required)");
            $stmt->execute([
                ':id' => $taskId,
                ':brand_name' => $brandName,
                ':earning_amount' => $earningAmount,
                ':image_url' => $imageUrl,
                ':task_order' => $taskOrder,
                ':vip_level_required' => $vipLevelRequired
            ]);

            echo json_encode(['success' => true, 'message' => 'Task added successfully', 'task_id' => $taskId]);
            break;

        case 'update_task':
            $taskId = sanitizeInput($_POST['task_id'] ?? '');
            $brandName = sanitizeInput($_POST['brand_name'] ?? '');
            $earningAmount = floatval($_POST['earning_amount'] ?? 0);
            $imageUrl = sanitizeInput($_POST['image_url'] ?? '');
            $taskOrder = intval($_POST['task_order'] ?? 0);
            $vipLevelRequired = intval($_POST['vip_level_required'] ?? 1);

            if (empty($taskId)) {
                throw new Exception('Task ID is required');
            }

            $stmt = $db->prepare("UPDATE admin_tasks SET brand_name = :brand_name, earning_amount = :earning_amount, image_url = :image_url, task_order = :task_order, vip_level_required = :vip_level_required WHERE id = :id");
            $stmt->execute([
                ':id' => $taskId,
                ':brand_name' => $brandName,
                ':earning_amount' => $earningAmount,
                ':image_url' => $imageUrl,
                ':task_order' => $taskOrder,
                ':vip_level_required' => $vipLevelRequired
            ]);

            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
            break;

        case 'delete_task':
            $taskId = sanitizeInput($_POST['task_id'] ?? '');

            if (empty($taskId)) {
                throw new Exception('Task ID is required');
            }

            $stmt = $db->prepare("DELETE FROM admin_tasks WHERE id = :id");
            $stmt->execute([':id' => $taskId]);

            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
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

        case 'update_settings':
            $settingsType = sanitizeInput($_POST['settings_type'] ?? '');

            if (empty($settingsType)) {
                throw new Exception('Settings type is required');
            }

            $db->beginTransaction();

            if ($settingsType === 'site_info') {
                $settings = [
                    'site_name' => sanitizeInput($_POST['site_name'] ?? ''),
                    'site_description' => sanitizeInput($_POST['site_description'] ?? ''),
                    'support_email' => sanitizeInput($_POST['support_email'] ?? '')
                ];
            } elseif ($settingsType === 'payment') {
                $settings = [
                    'min_withdrawal' => sanitizeInput($_POST['min_withdrawal'] ?? ''),
                    'processing_fee' => sanitizeInput($_POST['processing_fee'] ?? ''),
                    'withdrawal_days' => sanitizeInput($_POST['withdrawal_days'] ?? '')
                ];
            } elseif ($settingsType === 'referral') {
                $settings = [
                    'referral_bonus' => sanitizeInput($_POST['referral_bonus'] ?? ''),
                    'referral_commission' => sanitizeInput($_POST['referral_commission'] ?? '')
                ];
            } elseif ($settingsType === 'task') {
                $settings = [
                    'default_task_earnings' => sanitizeInput($_POST['default_task_earnings'] ?? ''),
                    'task_review_required' => sanitizeInput($_POST['task_review_required'] ?? '')
                ];
            } else {
                throw new Exception('Invalid settings type');
            }

            $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");

            foreach ($settings as $key => $value) {
                $stmt->execute([':key' => $key, ':value' => $value]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
