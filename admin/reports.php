<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');
    $due = trim($_POST['due_date'] ?? '');

    if ($title && $department) {
        $stmt = $pdo->prepare('INSERT INTO reports (title, department, status, due_date, created_at) VALUES (:title, :department, :status, :due_date, NOW())');
        $stmt->execute([
            'title' => $title,
            'department' => $department,
            'status' => $status,
            'due_date' => $due ?: null,
        ]);
        log_action((int)$user['id'], 'Create', 'Reports', 'Generated report ' . $title);
        set_flash('Report created successfully.');
    } else {
        set_flash('Title and department are required.', 'error');
    }
    header('Location: ' . BASE_URL . '/admin/reports.php');
    exit;
}

$reports = $pdo->query('SELECT * FROM reports ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Reports';
$activeNav = 'Reports';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Generate Reports</h2>
      <p>System and academic report compilation.</p>
    </div>
  </div>

  <form class="form-grid" method="post">
    <label>
      Report Title
      <input type="text" name="title" required />
    </label>
    <label>
      Department
      <input type="text" name="department" required />
    </label>
    <label>
      Status
      <select name="status">
        <option>Pending</option>
        <option>In Review</option>
        <option>Completed</option>
      </select>
    </label>
    <label>
      Due Date
      <input type="date" name="due_date" />
    </label>
    <button class="primary" type="submit">Create Report</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Report Queue</h2>
      <p>Track report progress and approvals.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Department</th>
          <th>Status</th>
          <th>Due Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reports): ?>
          <tr><td colspan="4" class="empty">No reports created.</td></tr>
        <?php endif; ?>
        <?php foreach ($reports as $report): ?>
          <tr>
            <td><?php echo e($report['title']); ?></td>
            <td><?php echo e($report['department']); ?></td>
            <td><span class="status <?php echo status_class($report['status']); ?>"><?php echo e($report['status']); ?></span></td>
            <td><?php echo e($report['due_date']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

