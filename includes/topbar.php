<?php
$flash = get_flash();
?>
<main class="content">
  <header class="topbar">
    <form class="search" method="get">
      <span class="search-icon"></span>
      <input type="text" name="q" placeholder="Search records, IDs, names..." value="<?php echo e(request_value('q')); ?>" />
    </form>
    <div class="topbar-right">
      <div class="pill"><?php echo e($roleLabel); ?></div>
      <div class="pill"><?php echo date('D, M d, Y'); ?></div>
      <button class="icon-btn" aria-label="Notifications">
        <span class="bell"></span>
      </button>
      <div class="profile-menu">
        <button class="avatar" type="button" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
          <?php echo e($initials); ?>
        </button>
        <div class="profile-dropdown" role="menu" aria-hidden="true">
          <div class="profile-header">
            <div class="profile-name"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></div>
            <div class="profile-role"><?php echo e($roleLabel); ?></div>
          </div>
          <a class="profile-item" role="menuitem" href="<?php echo BASE_URL; ?>/profile.php">Profile Settings</a>
          <a class="profile-item" role="menuitem" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a>
        </div>
      </div>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="alert <?php echo e($flash['type']); ?>">
      <?php echo e($flash['message']); ?>
    </div>
  <?php endif; ?>
