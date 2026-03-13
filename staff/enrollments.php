<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Enrolled');

    if ($studentId && $classId) {
        $stmt = $pdo->prepare('INSERT INTO enrollments (student_id, class_id, status, created_at) VALUES (:student_id, :class_id, :status, NOW())');
        $stmt->execute([
            'student_id' => $studentId,
            'class_id' => $classId,
            'status' => $status,
        ]);
        log_action((int)$user['id'], 'Create', 'Enrollment', 'Enrolled student ID ' . $studentId . ' to class ID ' . $classId);
        set_flash('Enrollment recorded successfully.');
    } else {
        set_flash('Select a student and class.', 'error');
    }
    header('Location: ' . BASE_URL . '/staff/enrollments.php');
    exit;
}

$students = $pdo->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$classes = $pdo->query('SELECT id, class_code, title FROM classes ORDER BY class_code')->fetchAll();
$enrollments = $pdo->query('SELECT enrollments.id, students.student_no, students.first_name, students.last_name, classes.class_code, classes.title, enrollments.status FROM enrollments JOIN students ON enrollments.student_id = students.id JOIN classes ON enrollments.class_id = classes.id ORDER BY enrollments.created_at DESC')->fetchAll();

$pageTitle = 'Enrollment';
$activeNav = 'Enrollment';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Enroll Students</h2>
      <p>Process course enrollment requests.</p>
    </div>
  </div>

  <form class="form-grid" method="post">
    <label>
      Student
      <select name="student_id" required>
        <option value="">Select student</option>
        <?php foreach ($students as $student): ?>
          <option value="<?php echo $student['id']; ?>"><?php echo e($student['student_no'] . ' - ' . $student['last_name'] . ', ' . $student['first_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Class
      <select name="class_id" required>
        <option value="">Select class</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?php echo $class['id']; ?>"><?php echo e($class['class_code'] . ' - ' . $class['title']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Status
      <select name="status">
        <option>Enrolled</option>
        <option>Pending</option>
        <option>Waitlisted</option>
      </select>
    </label>
    <button class="primary" type="submit">Add Enrollment</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Enrollment Records</h2>
      <p>Enrollment confirmations and statuses.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Class</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$enrollments): ?>
          <tr><td colspan="3" class="empty">No enrollment records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($enrollments as $enroll): ?>
          <tr>
            <td><?php echo e($enroll['student_no'] . ' - ' . $enroll['last_name'] . ', ' . $enroll['first_name']); ?></td>
            <td><?php echo e($enroll['class_code'] . ' - ' . $enroll['title']); ?></td>
            <td><span class="status <?php echo status_class($enroll['status']); ?>"><?php echo e($enroll['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

