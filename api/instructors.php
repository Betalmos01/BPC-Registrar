<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Registrar Staff');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $instructors = $pdo->query('SELECT * FROM instructors ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['instructors' => $instructors]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/staff/instructors.php');
}

if ($action === 'create') {
    $employeeNo = trim($input['employee_no'] ?? '');
    $first = trim($input['first_name'] ?? '');
    $last = trim($input['last_name'] ?? '');
    $department = trim($input['department'] ?? '');

    if (!$employeeNo || !$first || !$last) {
        api_error('Employee No, First Name, and Last Name are required.', 422, BASE_URL . '/staff/instructors.php');
    }

    $exists = $pdo->prepare('SELECT id FROM instructors WHERE employee_no = :employee_no');
    $exists->execute(['employee_no' => $employeeNo]);
    if ($exists->fetchColumn()) {
        api_error('Employee No already exists.', 409, BASE_URL . '/staff/instructors.php');
    }

    $stmt = $pdo->prepare('INSERT INTO instructors (employee_no, first_name, last_name, department, created_at) VALUES (:employee_no, :first_name, :last_name, :department, NOW())');
    $stmt->execute([
        'employee_no' => $employeeNo,
        'first_name' => $first,
        'last_name' => $last,
        'department' => $department,
    ]);

    log_action((int)$user['id'], 'Create', 'Instructors', 'Added instructor ' . $employeeNo);
    api_success('Instructor added successfully.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/staff/instructors.php');
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $employeeNo = trim($input['employee_no'] ?? '');
    $first = trim($input['first_name'] ?? '');
    $last = trim($input['last_name'] ?? '');
    $department = trim($input['department'] ?? '');

    if ($id <= 0 || !$employeeNo || !$first || !$last) {
        api_error('All required fields must be provided.', 422, BASE_URL . '/staff/instructors.php');
    }

    $exists = $pdo->prepare('SELECT id FROM instructors WHERE employee_no = :employee_no AND id <> :id');
    $exists->execute(['employee_no' => $employeeNo, 'id' => $id]);
    if ($exists->fetchColumn()) {
        api_error('Employee No already exists.', 409, BASE_URL . '/staff/instructors.php');
    }

    $stmt = $pdo->prepare('UPDATE instructors SET employee_no = :employee_no, first_name = :first_name, last_name = :last_name, department = :department WHERE id = :id');
    $stmt->execute([
        'employee_no' => $employeeNo,
        'first_name' => $first,
        'last_name' => $last,
        'department' => $department,
        'id' => $id,
    ]);

    log_action((int)$user['id'], 'Update', 'Instructors', 'Updated instructor ID ' . $id);
    api_success('Instructor updated.', ['id' => $id], BASE_URL . '/staff/instructors.php');
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing instructor id.', 422, BASE_URL . '/staff/instructors.php');
    }

    $stmt = $pdo->prepare('DELETE FROM instructors WHERE id = :id');
    $stmt->execute(['id' => $id]);

    log_action((int)$user['id'], 'Delete', 'Instructors', 'Deleted instructor ID ' . $id);
    api_success('Instructor deleted.', ['id' => $id], BASE_URL . '/staff/instructors.php');
}

api_error('Unknown action.', 400, BASE_URL . '/staff/instructors.php');

