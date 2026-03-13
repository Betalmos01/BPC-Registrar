<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Registrar Staff');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $students = $pdo->query('SELECT * FROM students ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['students' => $students]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/staff/students.php');
}

if ($action === 'create') {
    $studentNo = trim($input['student_no'] ?? '');
    $first = trim($input['first_name'] ?? '');
    $last = trim($input['last_name'] ?? '');
    $program = trim($input['program'] ?? '');
    $year = trim($input['year_level'] ?? '');
    $status = trim($input['status'] ?? 'Active');

    if (!$studentNo || !$first || !$last) {
        api_error('Student No, First Name, and Last Name are required.', 422, BASE_URL . '/staff/students.php');
    }

    $exists = $pdo->prepare('SELECT id FROM students WHERE student_no = :student_no');
    $exists->execute(['student_no' => $studentNo]);
    if ($exists->fetchColumn()) {
        api_error('Student No already exists.', 409, BASE_URL . '/staff/students.php');
    }

    $stmt = $pdo->prepare('INSERT INTO students (student_no, first_name, last_name, program, year_level, status, created_at) VALUES (:student_no, :first_name, :last_name, :program, :year_level, :status, NOW())');
    $stmt->execute([
        'student_no' => $studentNo,
        'first_name' => $first,
        'last_name' => $last,
        'program' => $program,
        'year_level' => $year,
        'status' => $status,
    ]);

    log_action((int)$user['id'], 'Create', 'Student Records', 'Added student ' . $studentNo);
    api_success('Student record added successfully.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/staff/students.php');
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $studentNo = trim($input['student_no'] ?? '');
    $first = trim($input['first_name'] ?? '');
    $last = trim($input['last_name'] ?? '');
    $program = trim($input['program'] ?? '');
    $year = trim($input['year_level'] ?? '');
    $status = trim($input['status'] ?? 'Active');

    if ($id <= 0) {
        api_error('Missing student id.', 422, BASE_URL . '/staff/students.php');
    }
    if (!$studentNo || !$first || !$last) {
        api_error('Student No, First Name, and Last Name are required.', 422, BASE_URL . '/staff/students.php');
    }

    $exists = $pdo->prepare('SELECT id FROM students WHERE student_no = :student_no AND id <> :id');
    $exists->execute(['student_no' => $studentNo, 'id' => $id]);
    if ($exists->fetchColumn()) {
        api_error('Student No already exists.', 409, BASE_URL . '/staff/students.php');
    }

    $stmt = $pdo->prepare('UPDATE students SET student_no = :student_no, first_name = :first_name, last_name = :last_name, program = :program, year_level = :year_level, status = :status WHERE id = :id');
    $stmt->execute([
        'student_no' => $studentNo,
        'first_name' => $first,
        'last_name' => $last,
        'program' => $program,
        'year_level' => $year,
        'status' => $status,
        'id' => $id,
    ]);

    log_action((int)$user['id'], 'Update', 'Student Records', 'Updated student ID ' . $id);
    api_success('Student record updated.', ['id' => $id], BASE_URL . '/staff/students.php');
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing student id.', 422, BASE_URL . '/staff/students.php');
    }

    $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
    $stmt->execute(['id' => $id]);

    log_action((int)$user['id'], 'Delete', 'Student Records', 'Deleted student ID ' . $id);
    api_success('Student record deleted.', ['id' => $id], BASE_URL . '/staff/students.php');
}

api_error('Unknown action.', 400, BASE_URL . '/staff/students.php');

