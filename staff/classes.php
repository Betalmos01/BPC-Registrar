<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

// Ensure course column exists (self-heal on older DB schema).
try {
    $pdo->query('SELECT course FROM classes LIMIT 1');
} catch (PDOException $e) {
    if ($e->getCode() === '42S22') {
        $pdo->exec("ALTER TABLE classes ADD COLUMN course VARCHAR(120) DEFAULT ''");
    } else {
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $classId = (int)($_POST['class_id'] ?? 0);
        if ($classId > 0) {
            $stmt = $pdo->prepare('DELETE FROM classes WHERE id = :id');
            $stmt->execute(['id' => $classId]);
            log_action((int)$user['id'], 'Delete', 'Classes & Schedules', 'Deleted class ID ' . $classId);
            set_flash('Class schedule deleted.');
        }
        header('Location: ' . BASE_URL . '/staff/classes.php');
        exit;
    }

    $code = trim($_POST['class_code'] ?? '');
    $title = trim($_POST['class_title'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $units = (int)($_POST['units'] ?? 0);
    $day = trim($_POST['day'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $room = trim($_POST['room'] ?? '');

    if ($code && $title) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO classes (class_code, title, course, units, created_at) VALUES (:class_code, :title, :course, :units, NOW())');
            $stmt->execute([
                'class_code' => $code,
                'title' => $title,
                'course' => $course,
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

$filterCourse = trim($_GET['course'] ?? '');
$query = 'SELECT classes.id, classes.class_code, classes.title, classes.course, classes.units, schedules.day, schedules.time, schedules.room FROM classes LEFT JOIN schedules ON classes.id = schedules.class_id';
$params = [];
if ($filterCourse !== '') {
    $query .= ' WHERE classes.course = :course';
    $params['course'] = $filterCourse;
}
$query .= ' ORDER BY classes.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$classes = $stmt->fetchAll();

$pageTitle = 'Manage Classes & Schedules';
$activeNav = 'Manage Classes & Schedules';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$times = [
    '7:00 AM - 9:00 AM',
    '9:00 AM - 11:00 AM',
    '11:00 AM - 1:00 PM',
    '1:00 PM - 3:00 PM',
    '3:00 PM - 5:00 PM',
    '5:00 PM - 7:00 PM',
];
$rooms = ['Room 101', 'Room 102', 'Room 201', 'Room 202', 'Room 301', 'Room 302', 'Lab 1', 'Lab 2'];
$courses = ['BSIT', 'BSCS', 'BSBA', 'BSED', 'BEED', 'BSECE'];
$classCatalog = [
    'IT 101' => 'Introduction to Computing',
    'IT 102' => 'Programming Fundamentals',
    'IT 201' => 'Data Structures',
    'IT 202' => 'Database Systems',
    'CS 101' => 'Discrete Mathematics',
    'CS 201' => 'Algorithms',
    'BA 101' => 'Principles of Management',
    'ED 101' => 'Foundations of Education',
];
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
      <input type="text" name="class_code" list="class-codes" required />
      <datalist id="class-codes">
        <?php foreach ($classCatalog as $code => $label): ?>
          <option value="<?php echo e($code); ?>"><?php echo e($label); ?></option>
        <?php endforeach; ?>
      </datalist>
    </label>
    <label>
      Class Title
      <input type="text" name="class_title" required />
    </label>
    <label>
      Course
      <select name="course">
        <option value="">Select course</option>
        <?php foreach ($courses as $course): ?>
          <option value="<?php echo e($course); ?>"><?php echo e($course); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Units
      <input type="number" name="units" min="1" max="6" value="3" />
    </label>
    <label>
      Day
      <select name="day">
        <option value="">Select day</option>
        <?php foreach ($days as $day): ?>
          <option value="<?php echo e($day); ?>"><?php echo e($day); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Time (2 hrs)
      <select name="time">
        <option value="">Select time</option>
        <?php foreach ($times as $slot): ?>
          <option value="<?php echo e($slot); ?>"><?php echo e($slot); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Room
      <select name="room">
        <option value="">Select room</option>
        <?php foreach ($rooms as $room): ?>
          <option value="<?php echo e($room); ?>"><?php echo e($room); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="primary" type="submit">Add Class</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header" style="align-items: center; gap: 12px;">
    <div>
      <h2>Class List</h2>
      <p>View current schedule offerings.</p>
    </div>
    <form method="get" style="display: flex; gap: 8px; align-items: center;">
      <label style="margin: 0;">
        <select name="course" onchange="this.form.submit()">
          <option value="">All Courses</option>
          <?php foreach ($courses as $course): ?>
            <option value="<?php echo e($course); ?>" <?php echo $filterCourse === $course ? 'selected' : ''; ?>><?php echo e($course); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Class Code</th>
          <th>Title</th>
          <th>Course</th>
          <th>Units</th>
          <th>Day</th>
          <th>Time</th>
          <th>Room</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$classes): ?>
          <tr><td colspan="8" class="empty">No classes scheduled yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($classes as $class): ?>
          <tr>
            <td><?php echo e($class['class_code']); ?></td>
            <td><?php echo e($class['title']); ?></td>
            <td><?php echo e($class['course']); ?></td>
            <td><?php echo e($class['units']); ?></td>
            <td><?php echo e($class['day']); ?></td>
            <td><?php echo e($class['time']); ?></td>
            <td><?php echo e($class['room']); ?></td>
            <td>
              <form class="inline-form" method="post" onsubmit="return confirm('Delete this class schedule?');">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="class_id" value="<?php echo (int)$class['id']; ?>" />
                <button class="secondary" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  const classCatalog = <?php echo json_encode($classCatalog); ?>;
  const codeInput = document.querySelector('input[name="class_code"]');
  const titleInput = document.querySelector('input[name="class_title"]');
  if (codeInput && titleInput) {
    codeInput.addEventListener('input', () => {
      const match = classCatalog[codeInput.value.trim()];
      if (match) {
        titleInput.value = match;
      }
    });
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>


