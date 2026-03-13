<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pageTitle = 'Admin Dashboard';
$activeNav = 'Dashboard';

$pdo = db();

function safe_count(PDO $pdo, string $sql): int
{
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S02') {
            throw $e;
        }
        return 0;
    }
}

$reportCount = safe_count($pdo, 'SELECT COUNT(*) FROM reports');
$pendingDocs = safe_count($pdo, "SELECT COUNT(*) FROM documents WHERE status = 'Pending'");
$gradeCount = safe_count($pdo, 'SELECT COUNT(*) FROM grades');
$activityCount = safe_count($pdo, 'SELECT COUNT(*) FROM audit_logs');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="hero">
  <div>
    <div class="hero-badge">ADMIN CONSOLE</div>
    <h1>Registrar Administration</h1>
    <p>Generate reports, monitor system activity, and enforce access control.</p>
  </div>
  <div class="hero-card">
    <div class="hero-card-title">Daily Summary</div>
    <div class="hero-card-line">Reports: <strong><?php echo $reportCount; ?></strong></div>
    <div class="hero-card-line">Grade Entries: <strong><?php echo $gradeCount; ?></strong></div>
    <div class="hero-card-line">Pending Docs: <strong><?php echo $pendingDocs; ?></strong></div>
  </div>
</section>

<section class="metrics">
  <div class="metric green">
    <div class="metric-title">Generated Reports</div>
    <div class="metric-value"><?php echo $reportCount; ?></div>
    <div class="metric-sub">Academic and system reports</div>
  </div>
  <div class="metric blue">
    <div class="metric-title">Audit Logs</div>
    <div class="metric-value"><?php echo $activityCount; ?></div>
    <div class="metric-sub">Recorded system actions</div>
  </div>
  <div class="metric orange">
    <div class="metric-title">Document Requests</div>
    <div class="metric-value"><?php echo $pendingDocs; ?></div>
    <div class="metric-sub">Awaiting registrar action</div>
  </div>
  <div class="metric violet">
    <div class="metric-title">Grade Records</div>
    <div class="metric-value"><?php echo $gradeCount; ?></div>
    <div class="metric-sub">Filed by instructors</div>
  </div>
</section>

<section class="panel-grid">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2>Administration Queue</h2>
        <p>Latest reports and access requests.</p>
      </div>
      <div class="panel-actions">
        <a class="primary" href="<?php echo BASE_URL; ?>/admin/reports.php">Create Report</a>
      </div>
    </div>

    <?php
    try {
        $queue = $pdo->query('SELECT id, title, department, status, due_date FROM reports ORDER BY created_at DESC LIMIT 3')->fetchAll();
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S02') {
            throw $e;
        }
        $queue = [];
    }
    ?>
    <div class="staff-list">
      <?php if (!$queue): ?>
        <div class="empty">No reports yet. Create one to begin tracking.</div>
      <?php endif; ?>
      <?php foreach ($queue as $item): ?>
        <article class="staff-card">
          <div class="staff-meta">
            <div class="staff-avatar">RP</div>
            <div>
              <div class="staff-name"><?php echo e($item['title']); ?></div>
              <div class="staff-role"><?php echo e($item['department']); ?></div>
            </div>
            <span class="tag active"><?php echo e($item['status']); ?></span>
          </div>
          <div class="staff-body">
            <div>
              <div class="label">Report ID</div>
              <div class="value"><?php echo e($item['id']); ?></div>
            </div>
            <div>
              <div class="label">Due Date</div>
              <div class="value"><?php echo e($item['due_date']); ?></div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>

  <aside class="panel focus">
    <div class="panel-header">
      <div>
        <h2>Admin Focus</h2>
        <p>Access control snapshot.</p>
      </div>
    </div>
    <div class="focus-card">
      <div class="focus-title">Access Control Check</div>
      <div class="focus-name">Roles & Permissions</div>
      <div class="focus-detail">Ensure staff modules are protected.</div>
      <div class="focus-box">
        <div class="focus-label">Status</div>
        <div class="focus-text">RBAC enforced on all modules.</div>
      </div>
      <div class="focus-list">
        <div>
          <div class="label">Admin Modules</div>
          <div class="value">Reports, Activity, Settings</div>
        </div>
        <div>
          <div class="label">Staff Modules</div>
          <div class="value">Students, Enrollment, Classes</div>
        </div>
      </div>
      <a class="secondary" href="<?php echo BASE_URL; ?>/admin/activity.php">View Activity Logs</a>
    </div>
  </aside>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
