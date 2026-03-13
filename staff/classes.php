<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['class_code'] ?? '');
    $title = trim($_POST['class_title'] ?? '');
    $units = (int)($_POST['units'] ?? 0);
    $day = trim($_POST['day'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $room = trim($_POST['room'] ?? '');

    if ($code && $title) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO classes (class_code, title, units, created_at) VALUES (:class_code, :title, :units, NOW())');
            $stmt->execute([
                'class_code' => $code,
                'title' => $title,
                'units' => $units,
            ]);
            $classId = (int)$pdo->lastInsertId();
            $sched = $pdo->prepare('INSERT INTO schedules (class_id, day, time, room, created_at) VALUES (:class_id, :day, :time, :room, NOW())');
            $sched->execute([
                'class_id' => $classId,
                'day' => $day,
                'time' => $time,
                'room' => $room,
            ]);
            $pdo->commit();
            log_action((int)$user['id'], 'Create', 'Classes & Schedules', 'Added class ' . $code);
            set_flash('Class schedule added successfully.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            set_flash('Unable to save class schedule.', 'error');
        }
    } else {
        set_flash('Class code and title are required.', 'error');
    }
    header('Location: ' . BASE_URL . '/staff/classes.php');
    exit;
}

$classes = $pdo->query('SELECT classes.id, classes.class_code, classes.title, classes.units, schedules.day, schedules.time, schedules.room FROM classes LEFT JOIN schedules ON classes.id = schedules.class_id ORDER BY classes.created_at DESC')->fetchAll();

$pageTitle = 'Manage Classes & Schedules';
$activeNav = 'Manage Classes & Schedules';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Class Scheduling</h2>
      <p>Create and maintain class offerings.</p>
    </div>
  </div>

  <form class="form-grid" method="post">
    <label>
      Class Code
      <input type="text" name="class_code" required />
    </label>
    <label>
      Class Title
      <input type="text" name="class_title" required />
    </label>
    <label>
      Units
      <input type="number" name="units" min="1" max="6" value="3" />
    </label>
    <label>
      Day
      <input type="text" name="day" placeholder="Mon/Wed" />
    </label>
    <label>
      Time
      <input type="text" name="time" placeholder="9:00 AM - 10:30 AM" />
    </label>
    <label>
      Room
      <input type="text" name="room" placeholder="Room 201" />
    </label>
    <button class="primary" type="submit">Add Class</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Class List</h2>
      <p>View current schedule offerings.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Class Code</th>
          <th>Title</th>
          <th>Units</th>
          <th>Day</th>
          <th>Time</th>
          <th>Room</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$classes): ?>
          <tr><td colspan="6" class="empty">No classes scheduled yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($classes as $class): ?>
          <tr>
            <td><?php echo e($class['class_code']); ?></td>
            <td><?php echo e($class['title']); ?></td>
            <td><?php echo e($class['units']); ?></td>
            <td><?php echo e($class['day']); ?></td>
            <td><?php echo e($class['time']); ?></td>
            <td><?php echo e($class['room']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
