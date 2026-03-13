<?php
require_once __DIR__ . '/../config/auth.php';

function api_wants_json(): bool
{
    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
}

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function api_input(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function api_redirect_back(string $fallback): void
{
    $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? $fallback);
    if (!$redirect) {
        $redirect = BASE_URL . '/index.php';
    }
    header('Location: ' . $redirect);
    exit;
}

function api_require_role(string $role): array
{
    if (!is_logged_in()) {
        if (api_wants_json()) {
            api_json(['ok' => false, 'error' => 'Unauthenticated'], 401);
        }
        header('Location: ' . BASE_URL . '/index.php?error=login');
        exit;
    }

    $user = current_user();
    $userRole = strtolower($user['role'] ?? '');
    $required = strtolower($role);

    if ($required === 'registrar staff' && $userRole === 'administrator') {
        return $user;
    }

    if ($userRole !== $required) {
        if (api_wants_json()) {
            api_json(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        header('Location: ' . BASE_URL . '/access_denied.php');
        exit;
    }

    return $user;
}

function api_success(string $message, array $data = [], ?string $fallbackRedirect = null): void
{
    if (api_wants_json()) {
        api_json(['ok' => true, 'message' => $message, 'data' => $data], 200);
    }
    set_flash($message, 'success');
    api_redirect_back($fallbackRedirect ?? (BASE_URL . '/index.php'));
}

function api_error(string $message, int $status = 400, ?string $fallbackRedirect = null): void
{
    if (api_wants_json()) {
        api_json(['ok' => false, 'error' => $message], $status);
    }
    set_flash($message, 'error');
    api_redirect_back($fallbackRedirect ?? (BASE_URL . '/index.php'));
}

