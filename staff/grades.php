<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $grade = trim($_POST['grade'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($studentId && $classId && $grade !== '') {
        $stmt = $pdo->prepare('INSERT INTO grades (student_id, class_id, grade, remarks, created_at) VALUES (:student_id, :class_id, :grade, :remarks, NOW())');
        $stmt->execute([
            'student_id' => $studentId,
            'class_id' => $classId,
            'grade' => $grade,
            'remarks' => $remarks,
        ]);
        log_action((int)$user['id'], 'Create', 'Grades', 'Recorded grade for student ID ' . $studentId);
        set_flash('Grade record added successfully.');
    } else {
        set_flash('Complete grade fields.', 'error');
    }
    header('Location: ' . BASE_URL . '/staff/grades.php');
    exit;
}

$students = $pdo->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$classes = $pdo->query('SELECT id, class_code, title FROM classes ORDER BY class_code')->fetchAll();
$grades = $pdo->query('SELECT grades.id, students.student_no, students.first_name, students.last_name, classes.class_code, classes.title, grades.grade, grades.remarks FROM grades JOIN students ON grades.student_id = students.id JOIN classes ON grades.class_id = classes.id ORDER BY grades.created_at DESC')->fetchAll();

$pageTitle = 'Grade Management';
$activeNav = 'Grade Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Record Grades</h2>
      <p>Submit final grades and remarks.</p>
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
      Grade
      <input type="text" name="grade" placeholder="1.00 / A / 95" required />
    </label>
    <label>
      Remarks
      <input type="text" name="remarks" placeholder="Passed" />
    </label>
    <button class="primary" type="submit">Submit Grade</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Grade Records</h2>
      <p>Instructor submissions and evaluations.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Class</th>
          <th>Grade</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$grades): ?>
          <tr><td colspan="4" class="empty">No grade records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($grades as $grade): ?>
          <tr>
            <td><?php echo e($grade['student_no'] . ' - ' . $grade['last_name'] . ', ' . $grade['first_name']); ?></td>
            <td><?php echo e($grade['class_code'] . ' - ' . $grade['title']); ?></td>
            <td><?php echo e($grade['grade']); ?></td>
            <td><?php echo e($grade['remarks']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
