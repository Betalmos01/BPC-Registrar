<?php
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle = 'Profile';
$activeNav = 'Profile';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
include __DIR__ . '/includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>User Profile</h2>
      <p>Account details and access information.</p>
    </div>
  </div>
  <div class="profile-grid">
    <div>
      <div class="label">Name</div>
      <div class="value"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></div>
    </div>
    <div>
      <div class="label">Username</div>
      <div class="value"><?php echo e($user['username']); ?></div>
    </div>
    <div>
      <div class="label">Role</div>
      <div class="value"><?php echo e($user['role']); ?></div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
