<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();

$search = trim($_GET['q'] ?? '');
$module = trim($_GET['module'] ?? '');
$action = trim($_GET['action'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$params = [];
$whereParts = [];

if ($search) {
    $whereParts[] = '(audit_logs.details LIKE :q OR audit_logs.module LIKE :q OR audit_logs.action LIKE :q OR users.first_name LIKE :q OR users.last_name LIKE :q)';
    $params['q'] = '%' . $search . '%';
}
if ($module) {
    $whereParts[] = 'audit_logs.module = :module';
    $params['module'] = $module;
}
if ($action) {
    $whereParts[] = 'audit_logs.action = :action';
    $params['action'] = $action;
}

$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs LEFT JOIN users ON audit_logs.user_id = users.id $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, PER_PAGE);
$params['limit'] = $pagination['perPage'];
$params['offset'] = $pagination['offset'];

$sql = "SELECT audit_logs.*, users.first_name, users.last_name
        FROM audit_logs
        LEFT JOIN users ON audit_logs.user_id = users.id
        $where
        ORDER BY audit_logs.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $type = in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key === 'q' ? ':q' : ':' . $key, $value, $type);
}
$stmt->execute();
$logs = $stmt->fetchAll();

$modules = $pdo->query('SELECT DISTINCT module FROM audit_logs ORDER BY module')->fetchAll(PDO::FETCH_COLUMN);
$actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

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
    <div class="panel-actions">
      <form method="get" class="inline-form">
        <input type="hidden" name="q" value="<?php echo e($search); ?>" />
        <select name="module" onchange="this.form.submit()">
          <option value="">All Modules</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?php echo e($m); ?>" <?php echo $module === $m ? 'selected' : ''; ?>><?php echo e($m); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="action" onchange="this.form.submit()">
          <option value="">All Actions</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?php echo e($a); ?>" <?php echo $action === $a ? 'selected' : ''; ?>><?php echo e($a); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($module || $action): ?>
          <a class="secondary btn-sm" href="<?php echo BASE_URL; ?>/admin/activity.php<?php echo $search ? '?q=' . urlencode($search) : ''; ?>">Clear</a>
        <?php endif; ?>
      </form>
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
  <?php echo build_pagination(BASE_URL . '/admin/activity.php', $pagination, ['q' => $search, 'module' => $module, 'action' => $action]); ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
