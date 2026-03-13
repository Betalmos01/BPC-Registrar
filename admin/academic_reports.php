<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $coverage = trim($_POST['coverage'] ?? '');
    $status = trim($_POST['status'] ?? 'Draft');

    if ($title && $coverage) {
        $stmt = $pdo->prepare('INSERT INTO academic_reports (title, coverage, status, created_at) VALUES (:title, :coverage, :status, NOW())');
        $stmt->execute([
            'title' => $title,
            'coverage' => $coverage,
            'status' => $status,
        ]);
        log_action((int)$user['id'], 'Create', 'Academic Reports', 'Created academic report ' . $title);
        set_flash('Academic report created.');
    } else {
        set_flash('Title and coverage are required.', 'error');
    }
    header('Location: ' . BASE_URL . '/admin/academic_reports.php');
    exit;
}

$reports = $pdo->query('SELECT * FROM academic_reports ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Academic Reports';
$activeNav = 'Academic Reports';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Academic Reports</h2>
      <p>Generate academic performance and transcript reports.</p>
    </div>
  </div>

  <form class="form-grid" method="post">
    <label>
      Report Title
      <input type="text" name="title" required />
    </label>
    <label>
      Coverage
      <input type="text" name="coverage" placeholder="AY 2025-2026" required />
    </label>
    <label>
      Status
      <select name="status">
        <option>Draft</option>
        <option>In Review</option>
        <option>Released</option>
      </select>
    </label>
    <button class="primary" type="submit">Create Academic Report</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Academic Report Log</h2>
      <p>Monitor academic report submissions.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Coverage</th>
          <th>Status</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reports): ?>
          <tr><td colspan="4" class="empty">No academic reports found.</td></tr>
        <?php endif; ?>
        <?php foreach ($reports as $report): ?>
          <tr>
            <td><?php echo e($report['title']); ?></td>
            <td><?php echo e($report['coverage']); ?></td>
            <td><span class="status <?php echo status_class($report['status']); ?>"><?php echo e($report['status']); ?></span></td>
            <td><?php echo e($report['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

