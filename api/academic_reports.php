<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Administrator');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $reports = $pdo->query('SELECT * FROM academic_reports ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['academic_reports' => $reports]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/admin/academic_reports.php');
}

if ($action === 'create') {
    $title = trim($input['title'] ?? '');
    $coverage = trim($input['coverage'] ?? '');
    $status = trim($input['status'] ?? 'Draft');

    if (!$title || !$coverage) {
        api_error('Title and coverage are required.', 422, BASE_URL . '/admin/academic_reports.php');
    }

    $stmt = $pdo->prepare('INSERT INTO academic_reports (title, coverage, status, created_at) VALUES (:title, :coverage, :status, NOW())');
    $stmt->execute([
        'title' => $title,
        'coverage' => $coverage,
        'status' => $status,
    ]);

    log_action((int)$user['id'], 'Create', 'Academic Reports', 'Created academic report ' . $title);
    api_success('Academic report created.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/admin/academic_reports.php');
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $coverage = trim($input['coverage'] ?? '');
    $status = trim($input['status'] ?? 'Draft');

    if ($id <= 0 || !$title || !$coverage) {
        api_error('All fields are required.', 422, BASE_URL . '/admin/academic_reports.php');
    }

    $stmt = $pdo->prepare('UPDATE academic_reports SET title = :title, coverage = :coverage, status = :status WHERE id = :id');
    $stmt->execute([
        'title' => $title,
        'coverage' => $coverage,
        'status' => $status,
        'id' => $id,
    ]);

    log_action((int)$user['id'], 'Update', 'Academic Reports', 'Updated academic report ID ' . $id);
    api_success('Academic report updated.', ['id' => $id], BASE_URL . '/admin/academic_reports.php');
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing academic report id.', 422, BASE_URL . '/admin/academic_reports.php');
    }

    $stmt = $pdo->prepare('DELETE FROM academic_reports WHERE id = :id');
    $stmt->execute(['id' => $id]);

    log_action((int)$user['id'], 'Delete', 'Academic Reports', 'Deleted academic report ID ' . $id);
    api_success('Academic report deleted.', ['id' => $id], BASE_URL . '/admin/academic_reports.php');
}

api_error('Unknown action.', 400, BASE_URL . '/admin/academic_reports.php');
