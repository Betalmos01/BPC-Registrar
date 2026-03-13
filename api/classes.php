<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Registrar Staff');
$pdo = db();

// Ensure course column exists (self-heal on older DB schema).
try {
    $pdo->query('SELECT course FROM classes LIMIT 1');
} catch (PDOException $e) {
    if ($e->getCode() === '42S22') {
        $pdo->exec("ALTER TABLE classes ADD COLUMN course VARCHAR(120) DEFAULT ''");
    } else {
        throw $e;
    }
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $classes = $pdo->query('SELECT classes.id, classes.class_code, classes.title, classes.course, classes.units, schedules.day, schedules.time, schedules.room FROM classes LEFT JOIN schedules ON classes.id = schedules.class_id ORDER BY classes.created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['classes' => $classes]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/staff/classes.php');
}

if ($action === 'create') {
    $code = trim($input['class_code'] ?? '');
    $title = trim($input['class_title'] ?? '');
    $course = trim($input['course'] ?? '');
    $units = (int)($input['units'] ?? 0);
    $day = trim($input['day'] ?? '');
    $time = trim($input['time'] ?? '');
    $room = trim($input['room'] ?? '');

    if (!$code || !$title) {
        api_error('Class code and title are required.', 422, BASE_URL . '/staff/classes.php');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO classes (class_code, title, course, units, created_at) VALUES (:class_code, :title, :course, :units, NOW())');
        $stmt->execute([
            'class_code' => $code,
            'title' => $title,
            'course' => $course,
            'units' => $units,
        ]);
        $classId = (int)$pdo->lastInsertId();

        $sched = $pdo->prepare('INSERT INTO schedules (class_id, day, time, room, created_at) VALUES (:class_id, :day, :time, :room, NOW())');
        $sched->execute([
            'class_id' => $classId,
            'day' => $day,
            'time' => $time,
            'room' => $room,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        api_error('Unable to save class schedule.', 500, BASE_URL . '/staff/classes.php');
    }

    log_action((int)$user['id'], 'Create', 'Classes & Schedules', 'Added class ' . $code);
    api_success('Class schedule added successfully.', ['id' => $classId], BASE_URL . '/staff/classes.php');
}

if ($action === 'update') {
    $classId = (int)($input['class_id'] ?? 0);
    $code = trim($input['class_code'] ?? '');
    $title = trim($input['class_title'] ?? '');
    $course = trim($input['course'] ?? '');
    $units = (int)($input['units'] ?? 0);
    $day = trim($input['day'] ?? '');
    $time = trim($input['time'] ?? '');
    $room = trim($input['room'] ?? '');

    if ($classId <= 0 || !$code || !$title) {
        api_error('Class id, code, and title are required.', 422, BASE_URL . '/staff/classes.php');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE classes SET class_code = :class_code, title = :title, course = :course, units = :units WHERE id = :id');
        $stmt->execute([
            'class_code' => $code,
            'title' => $title,
            'course' => $course,
            'units' => $units,
            'id' => $classId,
        ]);

        $existing = $pdo->prepare('SELECT id FROM schedules WHERE class_id = :class_id LIMIT 1');
        $existing->execute(['class_id' => $classId]);
        $scheduleId = (int)($existing->fetchColumn() ?: 0);
        if ($scheduleId > 0) {
            $sched = $pdo->prepare('UPDATE schedules SET day = :day, time = :time, room = :room WHERE id = :id');
            $sched->execute(['day' => $day, 'time' => $time, 'room' => $room, 'id' => $scheduleId]);
        } else {
            $sched = $pdo->prepare('INSERT INTO schedules (class_id, day, time, room, created_at) VALUES (:class_id, :day, :time, :room, NOW())');
            $sched->execute(['class_id' => $classId, 'day' => $day, 'time' => $time, 'room' => $room]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        api_error('Unable to update class schedule.', 500, BASE_URL . '/staff/classes.php');
    }

    log_action((int)$user['id'], 'Update', 'Classes & Schedules', 'Updated class ID ' . $classId);
    api_success('Class schedule updated.', ['id' => $classId], BASE_URL . '/staff/classes.php');
}

if ($action === 'delete') {
    $classId = (int)($input['class_id'] ?? 0);
    if ($classId <= 0) {
        api_error('Missing class id.', 422, BASE_URL . '/staff/classes.php');
    }

    $stmt = $pdo->prepare('DELETE FROM classes WHERE id = :id');
    $stmt->execute(['id' => $classId]);

    log_action((int)$user['id'], 'Delete', 'Classes & Schedules', 'Deleted class ID ' . $classId);
    api_success('Class schedule deleted.', ['id' => $classId], BASE_URL . '/staff/classes.php');
}

api_error('Unknown action.', 400, BASE_URL . '/staff/classes.php');
