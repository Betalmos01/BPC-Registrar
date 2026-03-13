<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Administrator');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $reports = $pdo->query('SELECT * FROM reports ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['reports' => $reports]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/admin/reports.php');
}

if ($action === 'create') {
    $title = trim($input['title'] ?? '');
    $department = trim($input['department'] ?? '');
    $status = trim($input['status'] ?? 'Pending');
    $due = trim($input['due_date'] ?? '');

    if (!$title || !$department) {
        api_error('Title and department are required.', 422, BASE_URL . '/admin/reports.php');
    }

    $stmt = $pdo->prepare('INSERT INTO reports (title, department, status, due_date, created_at) VALUES (:title, :department, :status, :due_date, NOW())');
    $stmt->execute([
        'title' => $title,
        'department' => $department,
        'status' => $status,
        'due_date' => $due ?: null,
    ]);

    log_action((int)$user['id'], 'Create', 'Reports', 'Generated report ' . $title);
    api_success('Report created successfully.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/admin/reports.php');
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $department = trim($input['department'] ?? '');
    $status = trim($input['status'] ?? 'Pending');
    $due = trim($input['due_date'] ?? '');

    if ($id <= 0 || !$title || !$department) {
        api_error('All fields are required.', 422, BASE_URL . '/admin/reports.php');
    }

    $stmt = $pdo->prepare('UPDATE reports SET title = :title, department = :department, status = :status, due_date = :due_date WHERE id = :id');
    $stmt->execute([
        'title' => $title,
        'department' => $department,
        'status' => $status,
        'due_date' => $due ?: null,
        'id' => $id,
    ]);

    log_action((int)$user['id'], 'Update', 'Reports', 'Updated report ID ' . $id);
    api_success('Report updated.', ['id' => $id], BASE_URL . '/admin/reports.php');
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing report id.', 422, BASE_URL . '/admin/reports.php');
    }

    $stmt = $pdo->prepare('DELETE FROM reports WHERE id = :id');
    $stmt->execute(['id' => $id]);

    log_action((int)$user['id'], 'Delete', 'Reports', 'Deleted report ID ' . $id);
    api_success('Report deleted.', ['id' => $id], BASE_URL . '/admin/reports.php');
}

api_error('Unknown action.', 400, BASE_URL . '/admin/reports.php');
