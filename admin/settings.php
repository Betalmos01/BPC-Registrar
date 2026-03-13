<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pageTitle = 'System Settings';
$activeNav = 'System Settings';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>System Configuration</h2>
      <p>Access control and registrar configuration overview.</p>
    </div>
  </div>
  <div class="settings-grid">
    <div class="settings-card">
      <div class="label">Authentication</div>
      <div class="value">RBAC Enabled</div>
      <p class="muted">Administrator and Registrar Staff roles enforced.</p>
    </div>
    <div class="settings-card">
      <div class="label">Audit Logging</div>
      <div class="value">Active</div>
      <p class="muted">Tracks key actions across modules.</p>
    </div>
    <div class="settings-card">
      <div class="label">Notifications</div>
      <div class="value">Document Requests</div>
      <p class="muted">Pending requests are highlighted for staff.</p>
    </div>
    <div class="settings-card">
      <div class="label">Demo Data</div>
      <div class="value">Seed Registrar Records</div>
      <p class="muted">Adds realistic sample students, classes, enrollments, grades, documents, and reports when tables are empty.</p>
      <form method="post" action="<?php echo BASE_URL; ?>/admin/seed_demo.php" style="margin-top: 12px;">
        <button class="primary" type="submit">Seed Demo Data</button>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
