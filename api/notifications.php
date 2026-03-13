<?php
require_once __DIR__ . '/../includes/api_helpers.php';

api_require_role('Registrar Staff');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/index.php');
}

$input = api_input();
$action = strtolower(trim($input['action'] ?? ''));

if ($action === 'mark_read') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing notification id.', 422, BASE_URL . '/index.php');
    }

    $stmt = $pdo->prepare("UPDATE notifications SET status = 'Read' WHERE id = :id");
    $stmt->execute(['id' => $id]);
    api_success('Marked as read.', ['id' => $id], BASE_URL . '/index.php');
}

if ($action === 'mark_all_read') {
    $pdo->exec("UPDATE notifications SET status = 'Read' WHERE status = 'Unread'");
    api_success('All notifications marked as read.', [], BASE_URL . '/index.php');
}

api_error('Unknown action.', 400, BASE_URL . '/index.php');

