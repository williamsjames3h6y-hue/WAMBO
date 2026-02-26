<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$auth->logout();

// Check if there's a redirect parameter
$redirectTo = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
redirect($redirectTo);
?>
