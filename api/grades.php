<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Registrar Staff');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $grades = $pdo->query('SELECT * FROM grades ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['grades' => $grades]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/staff/grades.php');
}

if ($action === 'create') {
    $studentId = (int)($input['student_id'] ?? 0);
    $classId = (int)($input['class_id'] ?? 0);
    $grade = trim($input['grade'] ?? '');
    $remarks = trim($input['remarks'] ?? '');

    if ($studentId <= 0 || $classId <= 0 || $grade === '') {
        api_error('Complete grade fields.', 422, BASE_URL . '/staff/grades.php');
    }

    $stmt = $pdo->prepare('INSERT INTO grades (student_id, class_id, grade, remarks, created_at) VALUES (:student_id, :class_id, :grade, :remarks, NOW())');
    $stmt->execute([
        'student_id' => $studentId,
        'class_id' => $classId,
        'grade' => $grade,
        'remarks' => $remarks,
    ]);

    log_action((int)$user['id'], 'Create', 'Grades', 'Recorded grade for student ID ' . $studentId);
    api_success('Grade record added successfully.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/staff/grades.php');
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $grade = trim($input['grade'] ?? '');
    $remarks = trim($input['remarks'] ?? '');

    if ($id <= 0 || $grade === '') {
        api_error('Missing grade id or grade value.', 422, BASE_URL . '/staff/grades.php');
    }

    $stmt = $pdo->prepare('UPDATE grades SET grade = :grade, remarks = :remarks WHERE id = :id');
    $stmt->execute([
        'grade' => $grade,
        'remarks' => $remarks,
        'id' => $id,
    ]);

    log_action((int)$user['id'], 'Update', 'Grades', 'Updated grade ID ' . $id);
    api_success('Grade record updated.', ['id' => $id], BASE_URL . '/staff/grades.php');
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing grade id.', 422, BASE_URL . '/staff/grades.php');
    }

    $stmt = $pdo->prepare('DELETE FROM grades WHERE id = :id');
    $stmt->execute(['id' => $id]);

    log_action((int)$user['id'], 'Delete', 'Grades', 'Deleted grade ID ' . $id);
    api_success('Grade record deleted.', ['id' => $id], BASE_URL . '/staff/grades.php');
}

api_error('Unknown action.', 400, BASE_URL . '/staff/grades.php');

