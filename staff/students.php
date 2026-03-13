<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNo = trim($_POST['student_no'] ?? '');
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $year = trim($_POST['year_level'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if ($studentNo && $first && $last) {
        $stmt = $pdo->prepare('INSERT INTO students (student_no, first_name, last_name, program, year_level, status, created_at) VALUES (:student_no, :first_name, :last_name, :program, :year_level, :status, NOW())');
        $stmt->execute([
            'student_no' => $studentNo,
            'first_name' => $first,
            'last_name' => $last,
            'program' => $program,
            'year_level' => $year,
            'status' => $status,
        ]);
        log_action((int)$user['id'], 'Create', 'Student Records', 'Added student ' . $studentNo);
        set_flash('Student record added successfully.');
    } else {
        set_flash('Please complete required fields.', 'error');
    }
    header('Location: ' . BASE_URL . '/staff/students.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$params = [];
$where = '';
if ($search) {
    $where = "WHERE student_no LIKE :q OR first_name LIKE :q OR last_name LIKE :q";
    $params['q'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, PER_PAGE);
$params['limit'] = $pagination['perPage'];
$params['offset'] = $pagination['offset'];

$listStmt = $pdo->prepare("SELECT * FROM students $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $type = in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $listStmt->bindValue($key === 'q' ? ':q' : ':' . $key, $value, $type);
}
$listStmt->execute();
$students = $listStmt->fetchAll();

$pageTitle = 'Student Management';
$activeNav = 'Student Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Student Records</h2>
      <p>Manage student profiles and enrollment readiness.</p>
    </div>
  </div>

  <form class="form-grid" method="post">
    <label>
      Student No
      <input type="text" name="student_no" required />
    </label>
    <label>
      First Name
      <input type="text" name="first_name" required />
    </label>
    <label>
      Last Name
      <input type="text" name="last_name" required />
    </label>
    <label>
      Program
      <input type="text" name="program" />
    </label>
    <label>
      Year Level
      <input type="text" name="year_level" />
    </label>
    <label>
      Status
      <select name="status">
        <option>Active</option>
        <option>Inactive</option>
        <option>On Hold</option>
      </select>
    </label>
    <button class="primary" type="submit">Add Student</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Student List</h2>
      <p>Search and filter active student records.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student No</th>
          <th>Name</th>
          <th>Program</th>
          <th>Year</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$students): ?>
          <tr><td colspan="5" class="empty">No student records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $student): ?>
          <tr>
            <td><?php echo e($student['student_no']); ?></td>
            <td><?php echo e($student['first_name'] . ' ' . $student['last_name']); ?></td>
            <td><?php echo e($student['program']); ?></td>
            <td><?php echo e($student['year_level']); ?></td>
            <td><span class="status <?php echo status_class($student['status']); ?>"><?php echo e($student['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php echo build_pagination(BASE_URL . '/staff/students.php', $pagination, ['q' => $search]); ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>


