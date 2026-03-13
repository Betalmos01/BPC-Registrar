<?php
$flash = get_flash();
$greetingName = $user ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : '';
?>
<main class="content">
  <header class="topbar">
    <button class="icon-btn sidebar-toggle" type="button" aria-label="Toggle sidebar" onclick="(function(){if(window.innerWidth<=900){document.body.classList.toggle('sidebar-open');document.body.classList.remove('sidebar-collapsed');}else{document.body.classList.toggle('sidebar-collapsed');document.body.classList.remove('sidebar-open');}})();">
      <span class="burger" aria-hidden="true"></span>
    </button>
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

  <section class="page-header">
    <div>
      <div class="page-title"><?php echo e($pageTitle); ?></div>
      <div class="page-sub"><?php echo $greetingName ? 'Welcome back, ' . e($greetingName) . '.' : 'Welcome back.'; ?></div>
    </div>
  </section>

  <?php if ($flash): ?>
    <div class="alert <?php echo e($flash['type']); ?>">
      <?php echo e($flash['message']); ?>
    </div>
  <?php endif; ?>
