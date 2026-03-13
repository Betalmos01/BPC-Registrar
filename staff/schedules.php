<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();

$schedules = $pdo->query('SELECT classes.class_code, classes.title, schedules.day, schedules.time, schedules.room FROM schedules JOIN classes ON schedules.class_id = classes.id ORDER BY schedules.created_at DESC')->fetchAll();

$pageTitle = 'Class Schedules';
$activeNav = 'Class Schedules';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Class Schedules</h2>
      <p>Review schedules for students and instructors.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Class Code</th>
          <th>Title</th>
          <th>Day</th>
          <th>Time</th>
          <th>Room</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$schedules): ?>
          <tr><td colspan="5" class="empty">No schedules available.</td></tr>
        <?php endif; ?>
        <?php foreach ($schedules as $schedule): ?>
          <tr>
            <td><?php echo e($schedule['class_code']); ?></td>
            <td><?php echo e($schedule['title']); ?></td>
            <td><?php echo e($schedule['day']); ?></td>
            <td><?php echo e($schedule['time']); ?></td>
            <td><?php echo e($schedule['room']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
