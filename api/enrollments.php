<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Registrar Staff');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $enrollments = $pdo->query('SELECT * FROM enrollments ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['enrollments' => $enrollments]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/staff/enrollments.php');
}

if ($action === 'create') {
    $studentId = (int)($input['student_id'] ?? 0);
    $classId = (int)($input['class_id'] ?? 0);
    $status = trim($input['status'] ?? 'Enrolled');

    if ($studentId <= 0 || $classId <= 0) {
        api_error('Select a student and class.', 422, BASE_URL . '/staff/enrollments.php');
    }

    $stmt = $pdo->prepare('INSERT INTO enrollments (student_id, class_id, status, created_at) VALUES (:student_id, :class_id, :status, NOW())');
    $stmt->execute([
        'student_id' => $studentId,
        'class_id' => $classId,
        'status' => $status,
    ]);

    log_action((int)$user['id'], 'Create', 'Enrollment', 'Enrolled student ID ' . $studentId . ' to class ID ' . $classId);
    api_success('Enrollment recorded successfully.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/staff/enrollments.php');
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $status = trim($input['status'] ?? '');

    if ($id <= 0 || $status === '') {
        api_error('Missing enrollment id or status.', 422, BASE_URL . '/staff/enrollments.php');
    }

    $stmt = $pdo->prepare('UPDATE enrollments SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);

    log_action((int)$user['id'], 'Update', 'Enrollment', 'Updated enrollment ID ' . $id);
    api_success('Enrollment updated.', ['id' => $id], BASE_URL . '/staff/enrollments.php');
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing enrollment id.', 422, BASE_URL . '/staff/enrollments.php');
    }

    $stmt = $pdo->prepare('DELETE FROM enrollments WHERE id = :id');
    $stmt->execute(['id' => $id]);

    log_action((int)$user['id'], 'Delete', 'Enrollment', 'Deleted enrollment ID ' . $id);
    api_success('Enrollment deleted.', ['id' => $id], BASE_URL . '/staff/enrollments.php');
}

api_error('Unknown action.', 400, BASE_URL . '/staff/enrollments.php');

