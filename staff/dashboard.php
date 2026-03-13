<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pageTitle = 'Staff Dashboard';
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

$studentCount = safe_count($pdo, 'SELECT COUNT(*) FROM students');
$classCount = safe_count($pdo, 'SELECT COUNT(*) FROM classes');
$enrollCount = safe_count($pdo, 'SELECT COUNT(*) FROM enrollments');
$docPending = safe_count($pdo, "SELECT COUNT(*) FROM documents WHERE status = 'Pending'");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="hero">
  <div>
    <div class="hero-badge">REGISTRAR STAFF</div>
    <h1>Registrar Operations</h1>
    <p>Manage students, enrollments, schedules, and grade records.</p>
  </div>
  <div class="hero-card">
    <div class="hero-card-title">Workload Snapshot</div>
    <div class="hero-card-line">Students: <strong><?php echo $studentCount; ?></strong></div>
    <div class="hero-card-line">Enrollments: <strong><?php echo $enrollCount; ?></strong></div>
    <div class="hero-card-line">Doc Requests: <strong><?php echo $docPending; ?></strong></div>
  </div>
</section>

<section class="metrics">
  <div class="metric green">
    <div class="metric-title">Students</div>
    <div class="metric-value"><?php echo $studentCount; ?></div>
    <div class="metric-sub">Active student records</div>
  </div>
  <div class="metric blue">
    <div class="metric-title">Classes</div>
    <div class="metric-value"><?php echo $classCount; ?></div>
    <div class="metric-sub">Current schedule offerings</div>
  </div>
  <div class="metric orange">
    <div class="metric-title">Enrollments</div>
    <div class="metric-value"><?php echo $enrollCount; ?></div>
    <div class="metric-sub">Validated enrollments</div>
  </div>
  <div class="metric violet">
    <div class="metric-title">Pending Documents</div>
    <div class="metric-value"><?php echo $docPending; ?></div>
    <div class="metric-sub">Awaiting processing</div>
  </div>
</section>

<section class="panel-grid">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2>Today’s Priorities</h2>
        <p>Flow: Manage students ? class lists ? authorization check ? registrar system.</p>
      </div>
      <div class="panel-actions">
        <a class="primary" href="<?php echo BASE_URL; ?>/staff/enrollments.php">Enroll Student</a>
      </div>
    </div>

    <div class="task-list">
      <div class="task">
        <div>
          <div class="label">Student Records</div>
          <div class="value">Update new admissions and missing documents.</div>
        </div>
        <span class="chip">In Progress</span>
      </div>
      <div class="task">
        <div>
          <div class="label">Class Lists</div>
          <div class="value">Verify class lists before instructor release.</div>
        </div>
        <span class="chip">Pending</span>
      </div>
      <div class="task">
        <div>
          <div class="label">Authorization Check</div>
          <div class="value">Ensure enrollee status is verified.</div>
        </div>
        <span class="chip">Queued</span>
      </div>
    </div>
  </div>

  <aside class="panel focus">
    <div class="panel-header">
      <div>
        <h2>Service Status</h2>
        <p>Document requests and schedule updates.</p>
      </div>
    </div>
    <div class="focus-card">
      <div class="focus-title">Document Requests</div>
      <div class="focus-name">Pending: <?php echo $docPending; ?></div>
      <div class="focus-detail">Student transcripts and certifications</div>
      <div class="focus-box">
        <div class="focus-label">Next Action</div>
        <div class="focus-text">Process request queue by end of day.</div>
      </div>
      <a class="secondary" href="<?php echo BASE_URL; ?>/staff/documents.php">Open Requests</a>
    </div>
  </aside>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
