<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

$search = trim($_GET['q'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$params = [];
$where = '';
if ($search) {
    $where = 'WHERE users.username LIKE :q OR users.first_name LIKE :q OR users.last_name LIKE :q OR roles.name LIKE :q';
    $params['q'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users JOIN roles ON users.role_id = roles.id $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, PER_PAGE);
$params['limit'] = $pagination['perPage'];
$params['offset'] = $pagination['offset'];

$listStmt = $pdo->prepare("SELECT users.first_name, users.last_name, roles.name AS role, users.username, users.is_active FROM users JOIN roles ON users.role_id = roles.id $where ORDER BY roles.name, users.last_name LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $type = in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $listStmt->bindValue($key === 'q' ? ':q' : ':' . $key, $value, $type);
}
$listStmt->execute();
$staff = $listStmt->fetchAll();

$activeCount = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users JOIN roles ON users.role_id = roles.id WHERE roles.name = 'Administrator'")->fetchColumn();

$pageTitle = 'Staff Directory';
$activeNav = 'Staff Directory';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Accounts</div>
    <div class="module-value"><?php echo (int)$total; ?></div>
    <div class="module-note">System users with registrar access.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Active</div>
    <div class="module-value"><?php echo (int)$activeCount; ?></div>
    <div class="module-note">Accounts currently enabled for sign-in.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Administrators</div>
    <div class="module-value"><?php echo (int)$adminCount; ?></div>
    <div class="module-note">Users with full administrative privileges.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Registrar Staff Directory</h2>
      <p>Active staff and administrative accounts.</p>
    </div>
    <div class="panel-actions">
      <?php if (strtolower((string)($user['role'] ?? '')) === 'administrator'): ?>
        <a class="primary btn-sm" href="<?php echo BASE_URL; ?>/admin/users.php">Manage Users</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Role</th>
          <th>Username</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$staff): ?>
          <tr><td colspan="4" class="empty">No staff records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($staff as $member): ?>
          <tr>
            <td><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></td>
            <td><?php echo e($member['role']); ?></td>
            <td><?php echo e($member['username']); ?></td>
            <td><span class="status <?php echo (int)$member['is_active'] === 1 ? 'active' : 'inactive'; ?>"><?php echo (int)$member['is_active'] === 1 ? 'Active' : 'Inactive'; ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php echo build_pagination(BASE_URL . '/staff/staff_directory.php', $pagination, ['q' => $search]); ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
