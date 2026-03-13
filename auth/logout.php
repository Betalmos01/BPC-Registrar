<?php
require_once __DIR__ . '/../config/auth.php';

$user = current_user();
if ($user) {
    log_action((int)$user['id'], 'Logout', 'Authentication', 'User logged out');
}

logout_user();
header('Location: ' . BASE_URL . '/index.php');
exit;
