<?php
require_once __DIR__ . '/../config/database.php';

$pdo = db();

$pdo->exec("INSERT INTO roles (name) VALUES ('Administrator'), ('Registrar Staff') ON DUPLICATE KEY UPDATE name = name");

$roles = $pdo->query('SELECT id, name FROM roles')->fetchAll();
$roleMap = [];
foreach ($roles as $role) {
    $roleMap[$role['name']] = $role['id'];
}

$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$staffPass = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (role_id, username, password_hash, first_name, last_name, is_active) VALUES (:role_id, :username, :password_hash, :first_name, :last_name, 1)');

$users = [
    ['role' => 'Administrator', 'username' => 'adminaccount@gmail.com', 'first_name' => 'Admin', 'last_name' => 'Account', 'password' => $adminPass],
    ['role' => 'Registrar Staff', 'username' => 'staffaccount@gmail.com', 'first_name' => 'Staff', 'last_name' => 'Account', 'password' => $staffPass],
];

foreach ($users as $user) {
    $exists = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $exists->execute(['username' => $user['username']]);
    if ($exists->fetch()) {
        continue;
    }

    $stmt->execute([
        'role_id' => $roleMap[$user['role']],
        'username' => $user['username'],
        'password_hash' => $user['password'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
    ]);
}

echo "Seed completed.\n";
