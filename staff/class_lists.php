<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();

$search = trim($_GET['q'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$params = [];
$where = '';
if ($search) {
    $where = 'WHERE classes.class_code LIKE :q OR classes.title LIKE :q';
    $params['q'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM classes $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, PER_PAGE);
$params['limit'] = $pagination['perPage'];
$params['offset'] = $pagination['offset'];

$sql = "SELECT classes.id, classes.class_code, classes.title,
        SUM(CASE WHEN enrollments.status = 'Enrolled' THEN 1 ELSE 0 END) AS enrolled_students,
        COUNT(enrollments.id) AS total_students
        FROM classes
        LEFT JOIN enrollments ON classes.id = enrollments.class_id
        $where
        GROUP BY classes.id
        ORDER BY classes.class_code
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $type = in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key === 'q' ? ':q' : ':' . $key, $value, $type);
}
$stmt->execute();
$classLists = $stmt->fetchAll();

$pageTitle = 'Class Lists';
$activeNav = 'Class Lists';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Class Lists</h2>
      <p>Faculty access to class lists and enrollment totals.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Class Code</th>
          <th>Title</th>
          <th>Total Students</th>
          <th>Enrolled</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$classLists): ?>
          <tr><td colspan="5" class="empty">No class list data available.</td></tr>
        <?php endif; ?>
        <?php foreach ($classLists as $class): ?>
          <tr>
            <td><?php echo e($class['class_code']); ?></td>
            <td><?php echo e($class['title']); ?></td>
            <td><?php echo e($class['total_students']); ?></td>
            <td><?php echo e($class['enrolled_students']); ?></td>
            <td>
              <a class="secondary btn-sm" href="<?php echo BASE_URL; ?>/staff/class_list_view.php?class_id=<?php echo (int)$class['id']; ?>">View Roster</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php echo build_pagination(BASE_URL . '/staff/class_lists.php', $pagination, ['q' => $search]); ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
