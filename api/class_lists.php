<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Registrar Staff');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'GET') {
    api_error('Unsupported method.', 405, BASE_URL . '/staff/class_lists.php');
}

if (!api_wants_json()) {
    api_error('JSON required.', 406, BASE_URL . '/staff/class_lists.php');
}

$classId = (int)($_GET['class_id'] ?? 0);
if ($classId > 0) {
    $classStmt = $pdo->prepare('SELECT classes.id, classes.class_code, classes.title, classes.course, classes.units, schedules.day, schedules.time, schedules.room FROM classes LEFT JOIN schedules ON classes.id = schedules.class_id WHERE classes.id = :id LIMIT 1');
    $classStmt->execute(['id' => $classId]);
    $class = $classStmt->fetch();
    if (!$class) {
        api_json(['ok' => false, 'error' => 'Class not found.'], 404);
    }

    $rosterStmt = $pdo->prepare(
        "SELECT enrollments.id AS enrollment_id, enrollments.status AS enrollment_status,
                students.id AS student_id, students.student_no, students.first_name, students.last_name, students.program, students.year_level,
                grades.id AS grade_id, grades.grade, grades.remarks
         FROM enrollments
         JOIN students ON enrollments.student_id = students.id
         LEFT JOIN grades ON grades.student_id = enrollments.student_id AND grades.class_id = enrollments.class_id
         WHERE enrollments.class_id = :class_id
         ORDER BY students.last_name, students.first_name"
    );
    $rosterStmt->execute(['class_id' => $classId]);
    $roster = $rosterStmt->fetchAll();

    api_json(['ok' => true, 'data' => ['class' => $class, 'roster' => $roster]], 200);
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($q !== '') {
    $where = 'WHERE classes.class_code LIKE :q OR classes.title LIKE :q';
    $params['q'] = '%' . $q . '%';
}

$sql = "SELECT classes.id, classes.class_code, classes.title,
        SUM(CASE WHEN enrollments.status = 'Enrolled' THEN 1 ELSE 0 END) AS enrolled_students,
        COUNT(enrollments.id) AS total_students
        FROM classes
        LEFT JOIN enrollments ON classes.id = enrollments.class_id
        $where
        GROUP BY classes.id
        ORDER BY classes.class_code";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

api_json(['ok' => true, 'data' => ['classes' => $rows]], 200);

