<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();
$logs = $pdo->query('SELECT audit_logs.*, users.first_name, users.last_name FROM audit_logs LEFT JOIN users ON audit_logs.user_id = users.id ORDER BY audit_logs.created_at DESC LIMIT 50')->fetchAll();

$pageTitle = 'System Activity';
$activeNav = 'System Activity';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>System Activity Logs</h2>
      <p>Audit trail of key system actions.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Action</th>
          <th>Module</th>
          <th>Details</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$logs): ?>
          <tr><td colspan="5" class="empty">No activity logs found.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><?php echo e(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'System'); ?></td>
            <td><?php echo e($log['action']); ?></td>
            <td><?php echo e($log['module']); ?></td>
            <td><?php echo e($log['details']); ?></td>
            <td><?php echo e($log['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
