<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 0);

    if ($username && $password && $firstName && $lastName && $roleId) {
        $exists = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $exists->execute(['username' => $username]);
        if ($exists->fetch()) {
            set_flash('Username already exists.', 'error');
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (role_id, username, password_hash, first_name, last_name, is_active) VALUES (:role_id, :username, :password_hash, :first_name, :last_name, 1)');
            $stmt->execute([
                'role_id' => $roleId,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
            log_action((int)$user['id'], 'Create', 'Users', 'Created user ' . $username);
            set_flash('User created successfully.');
        }
    } else {
        set_flash('All fields are required.', 'error');
    }

    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$roles = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
$users = $pdo->query('SELECT users.id, users.username, users.first_name, users.last_name, users.is_active, roles.name AS role FROM users JOIN roles ON users.role_id = roles.id ORDER BY users.created_at DESC')->fetchAll();

$pageTitle = 'User Management';
$activeNav = 'User Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>User Management</h2>
      <p>Create and manage system accounts.</p>
    </div>
  </div>

  <form class="form-grid" method="post">
    <label>
      First Name
      <input type="text" name="first_name" required />
    </label>
    <label>
      Last Name
      <input type="text" name="last_name" required />
    </label>
    <label>
      Email / Username
      <input type="text" name="username" required />
    </label>
    <label>
      Role
      <select name="role_id" required>
        <option value="">Select role</option>
        <?php foreach ($roles as $role): ?>
          <option value="<?php echo (int)$role['id']; ?>"><?php echo e($role['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Password
      <input type="password" name="password" required />
    </label>
    <button class="primary" type="submit">Add User</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>All Users</h2>
      <p>Active accounts and assigned roles.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$users): ?>
          <tr>
            <td colspan="4" class="empty">No users found.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($users as $entry): ?>
          <tr>
            <td><?php echo e($entry['first_name'] . ' ' . $entry['last_name']); ?></td>
            <td><?php echo e($entry['username']); ?></td>
            <td><?php echo e($entry['role']); ?></td>
            <td>
              <span class="status <?php echo $entry['is_active'] ? 'active' : 'inactive'; ?>">
                <?php echo $entry['is_active'] ? 'Active' : 'Inactive'; ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
