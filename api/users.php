<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Administrator');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $users = $pdo->query('SELECT users.id, users.role_id, users.username, users.first_name, users.last_name, users.is_active, roles.name AS role FROM users JOIN roles ON users.role_id = roles.id ORDER BY users.created_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['users' => $users]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/admin/users.php');
}

if ($action === 'create') {
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $firstName = trim($input['first_name'] ?? '');
    $lastName = trim($input['last_name'] ?? '');
    $roleId = (int)($input['role_id'] ?? 0);

    if (!$username || !$password || !$firstName || !$lastName || $roleId <= 0) {
        api_error('All fields are required.', 422, BASE_URL . '/admin/users.php');
    }

    $exists = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $exists->execute(['username' => $username]);
    if ($exists->fetchColumn()) {
        api_error('Username already exists.', 409, BASE_URL . '/admin/users.php');
    }

    $stmt = $pdo->prepare('INSERT INTO users (role_id, username, password_hash, first_name, last_name, is_active) VALUES (:role_id, :username, :password_hash, :first_name, :last_name, 1)');
    $stmt->execute([
        'role_id' => $roleId,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'first_name' => $firstName,
        'last_name' => $lastName,
    ]);

    log_action((int)$user['id'], 'Create', 'Users', 'Created user ' . $username);
    api_success('User created successfully.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/admin/users.php');
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $username = trim($input['username'] ?? '');
    $firstName = trim($input['first_name'] ?? '');
    $lastName = trim($input['last_name'] ?? '');
    $roleId = (int)($input['role_id'] ?? 0);

    if ($id <= 0 || !$username || !$firstName || !$lastName || $roleId <= 0) {
        api_error('All fields are required.', 422, BASE_URL . '/admin/users.php');
    }

    $exists = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id <> :id');
    $exists->execute(['username' => $username, 'id' => $id]);
    if ($exists->fetchColumn()) {
        api_error('Username already exists.', 409, BASE_URL . '/admin/users.php');
    }

    $stmt = $pdo->prepare('UPDATE users SET role_id = :role_id, username = :username, first_name = :first_name, last_name = :last_name WHERE id = :id');
    $stmt->execute([
        'role_id' => $roleId,
        'username' => $username,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'id' => $id,
    ]);

    log_action((int)$user['id'], 'Update', 'Users', 'Updated user ID ' . $id);
    api_success('User updated.', ['id' => $id], BASE_URL . '/admin/users.php');
}

if ($action === 'toggle_active') {
    $id = (int)($input['id'] ?? 0);
    $isActive = (int)($input['is_active'] ?? -1);

    if ($id <= 0 || ($isActive !== 0 && $isActive !== 1)) {
        api_error('Missing user id or status.', 422, BASE_URL . '/admin/users.php');
    }

    if ($id === (int)$user['id']) {
        api_error('You cannot change your own active status.', 409, BASE_URL . '/admin/users.php');
    }

    $stmt = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
    $stmt->execute(['is_active' => $isActive, 'id' => $id]);

    log_action((int)$user['id'], 'Update', 'Users', 'Set active=' . $isActive . ' for user ID ' . $id);
    api_success('User status updated.', ['id' => $id, 'is_active' => $isActive], BASE_URL . '/admin/users.php');
}

if ($action === 'reset_password') {
    $id = (int)($input['id'] ?? 0);
    $password = $input['password'] ?? '';

    if ($id <= 0 || !$password) {
        api_error('Missing user id or password.', 422, BASE_URL . '/admin/users.php');
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmt->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => $id,
    ]);

    log_action((int)$user['id'], 'Update', 'Users', 'Reset password for user ID ' . $id);
    api_success('Password reset successful.', ['id' => $id], BASE_URL . '/admin/users.php');
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        api_error('Missing user id.', 422, BASE_URL . '/admin/users.php');
    }
    if ($id === (int)$user['id']) {
        api_error('You cannot delete your own account.', 409, BASE_URL . '/admin/users.php');
    }

    // Do not allow deleting the last active admin.
    $roleCheck = $pdo->prepare('SELECT roles.name AS role, users.is_active FROM users JOIN roles ON users.role_id = roles.id WHERE users.id = :id');
    $roleCheck->execute(['id' => $id]);
    $target = $roleCheck->fetch();
    if (!$target) {
        api_error('User not found.', 404, BASE_URL . '/admin/users.php');
    }
    if (strtolower($target['role'] ?? '') === 'administrator' && (int)$target['is_active'] === 1) {
        $admins = $pdo->query("SELECT COUNT(*) FROM users JOIN roles ON users.role_id = roles.id WHERE roles.name = 'Administrator' AND users.is_active = 1")->fetchColumn();
        if ((int)$admins <= 1) {
            api_error('Cannot delete the last active administrator.', 409, BASE_URL . '/admin/users.php');
        }
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);

    log_action((int)$user['id'], 'Delete', 'Users', 'Deleted user ID ' . $id);
    api_success('User deleted.', ['id' => $id], BASE_URL . '/admin/users.php');
}

api_error('Unknown action.', 400, BASE_URL . '/admin/users.php');

