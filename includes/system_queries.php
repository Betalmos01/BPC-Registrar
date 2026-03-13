<?php
require_once __DIR__ . '/../config/auth.php';

$pdo = db();

$systemData = [
    'roles' => $pdo->query('SELECT * FROM roles')->fetchAll(),
    'users' => $pdo->query('SELECT * FROM users')->fetchAll(),
    'students' => $pdo->query('SELECT * FROM students')->fetchAll(),
    'instructors' => $pdo->query('SELECT * FROM instructors')->fetchAll(),
    'classes' => $pdo->query('SELECT * FROM classes')->fetchAll(),
    'schedules' => $pdo->query('SELECT * FROM schedules')->fetchAll(),
    'enrollments' => $pdo->query('SELECT * FROM enrollments')->fetchAll(),
    'grades' => $pdo->query('SELECT * FROM grades')->fetchAll(),
    'documents' => $pdo->query('SELECT * FROM documents')->fetchAll(),
    'reports' => $pdo->query('SELECT * FROM reports')->fetchAll(),
    'academic_reports' => $pdo->query('SELECT * FROM academic_reports')->fetchAll(),
    'audit_logs' => $pdo->query('SELECT * FROM audit_logs')->fetchAll(),
    'notifications' => $pdo->query('SELECT * FROM notifications')->fetchAll(),
];
