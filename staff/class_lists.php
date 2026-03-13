<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();

$classLists = $pdo->query('SELECT classes.class_code, classes.title, COUNT(enrollments.id) AS total_students FROM classes LEFT JOIN enrollments ON classes.id = enrollments.class_id GROUP BY classes.id ORDER BY classes.class_code')->fetchAll();

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
        </tr>
      </thead>
      <tbody>
        <?php if (!$classLists): ?>
          <tr><td colspan="3" class="empty">No class list data available.</td></tr>
        <?php endif; ?>
        <?php foreach ($classLists as $class): ?>
          <tr>
            <td><?php echo e($class['class_code']); ?></td>
            <td><?php echo e($class['title']); ?></td>
            <td><?php echo e($class['total_students']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
