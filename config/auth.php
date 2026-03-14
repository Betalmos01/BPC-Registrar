<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap database schema + demo seeds on first request.
db();

function resolve_role_from_path(): string
{
    $path = strtolower($_SERVER['REQUEST_URI'] ?? '');
    if (str_contains($path, '/admin/')) {
        return 'Administrator';
    }
    if (str_contains($path, '/staff/')) {
        return 'Registrar Staff';
    }
    if (str_contains($path, 'admin.php')) {
        return 'Administrator';
    }
    if (str_contains($path, 'staff.php')) {
        return 'Registrar Staff';
    }
    return 'Registrar Staff';
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/index.php?error=login');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    $userRole = strtolower($user['role'] ?? '');
    $required = strtolower($role);

    // Admin should be able to access staff modules.
    if ($required === 'registrar staff' && $userRole === 'administrator') {
        return;
    }

    if (!$user || $userRole !== $required) {
        header('Location: ' . BASE_URL . '/access_denied.php');
        exit;
    }
}

function login_user(array $user): void
{
    $_SESSION['user'] = $user;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
