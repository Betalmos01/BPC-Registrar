<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = db()->prepare('SELECT users.id, users.username, users.password_hash, users.first_name, users.last_name, roles.name AS role FROM users JOIN roles ON users.role_id = roles.id WHERE users.username = :username AND users.is_active = 1');
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: ' . BASE_URL . '/index.php?error=1');
    exit;
}

login_user([
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'role' => $user['role'],
]);

log_action((int)$user['id'], 'Login', 'Authentication', 'User logged in');

$role = strtolower($user['role']);
if ($role === 'administrator') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/staff/dashboard.php');
exit;
