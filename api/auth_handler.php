<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$auth = new Auth();

switch ($action) {
    case 'register':
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = sanitizeInput($_POST['fullName'] ?? '');

        if (empty($email) || empty($password) || empty($fullName)) {
            jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
        }

        if (strlen($password) < 6) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
        }

        $result = $auth->register($email, $password, $fullName);
        jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    case 'login':
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
        }

        $result = $auth->login($email, $password);
        jsonResponse($result, $result['success'] ? 200 : 401);
        break;

    case 'logout':
        $result = $auth->logout();
        jsonResponse($result);
        break;

    case 'profile':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $profile = $auth->getUserProfile(getCurrentUserId());
        jsonResponse(['success' => true, 'profile' => $profile]);
        break;

    case 'check':
        jsonResponse([
            'success' => true,
            'isLoggedIn' => isLoggedIn(),
            'userId' => getCurrentUserId()
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}
?>
