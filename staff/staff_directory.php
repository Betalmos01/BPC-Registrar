<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$staff = $pdo->query('SELECT users.first_name, users.last_name, roles.name AS role, users.username FROM users JOIN roles ON users.role_id = roles.id ORDER BY roles.name, users.last_name')->fetchAll();

$pageTitle = 'Staff Directory';
$activeNav = 'Staff Directory';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Registrar Staff Directory</h2>
      <p>Active staff and administrative accounts.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Role</th>
          <th>Username</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$staff): ?>
          <tr><td colspan="3" class="empty">No staff records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($staff as $member): ?>
          <tr>
            <td><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></td>
            <td><?php echo e($member['role']); ?></td>
            <td><?php echo e($member['username']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
