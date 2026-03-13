<?php
$flash = get_flash();
$greetingName = $user ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : '';
$workflowContext = registrar_page_context($activeNav, strtolower($roleLabel) === 'administrator');
$workflowPage = $workflowContext['page'];
$workflow = $workflowContext['workflow'];
$currentStep = $workflowContext['currentIndex'];
$nextStep = $workflowContext['nextStep'];
$prevStep = $workflowContext['prevStep'];

$notifications = [];
$unreadCount = 0;
try {
  $pdo = db();
  $notifications = $pdo->query('SELECT id, title, message, status, created_at FROM notifications ORDER BY created_at DESC LIMIT 6')->fetchAll();
  foreach ($notifications as $n) {
    if (strtolower((string)($n['status'] ?? '')) === 'unread') {
      $unreadCount++;
    }
  }
} catch (Throwable $e) {
  $notifications = [];
  $unreadCount = 0;
}
?>
<main class="content">
  <header class="topbar">
    <button class="icon-btn sidebar-toggle" type="button" aria-label="Toggle sidebar">
      <span class="burger" aria-hidden="true"></span>
    </button>
    <form class="search" method="get">
      <span class="search-icon"></span>
      <input type="text" name="q" placeholder="Search records, IDs, names..." value="<?php echo e(request_value('q')); ?>" />
      <button class="search-filter" type="button" aria-label="Filter">
        <span class="filter-lines" aria-hidden="true"></span>
      </button>
    </form>
    <div class="topbar-right">
      <div class="pill subtle"><?php echo date('D g:i A'); ?></div>
      <div class="pill subtle"><?php echo date('M d, Y'); ?></div>
      <div class="notif-menu">
        <button class="icon-btn notif-trigger" type="button" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
          <span class="bell"></span>
          <?php if ($unreadCount > 0): ?>
            <span class="notif-badge"><?php echo $unreadCount > 9 ? '9+' : (int)$unreadCount; ?></span>
          <?php endif; ?>
        </button>
        <div class="notif-dropdown" role="menu" aria-hidden="true">
          <div class="notif-head">
            <div class="notif-title">Notifications</div>
            <div class="notif-sub"><?php echo $unreadCount ? ((int)$unreadCount . ' unread') : 'All caught up'; ?></div>
          </div>
          <div class="notif-actions">
            <button class="secondary btn-sm js-notif-markall" type="button" <?php echo $unreadCount ? '' : 'disabled'; ?>>Mark all read</button>
          </div>
          <div class="notif-list">
            <?php if (!$notifications): ?>
              <div class="notif-empty">No notifications available.</div>
            <?php endif; ?>
            <?php foreach ($notifications as $note): ?>
              <?php $isUnread = strtolower((string)($note['status'] ?? '')) === 'unread'; ?>
              <div class="notif-item <?php echo $isUnread ? 'unread' : ''; ?>" role="menuitem" data-id="<?php echo (int)$note['id']; ?>" data-unread="<?php echo $isUnread ? '1' : '0'; ?>">
                <div class="notif-item-title"><?php echo e($note['title']); ?></div>
                <div class="notif-item-msg"><?php echo e($note['message']); ?></div>
                <div class="notif-item-meta"><?php echo e(date('M d, Y g:i A', strtotime((string)$note['created_at']))); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <a class="icon-btn" href="<?php echo strtolower($roleLabel) === 'administrator' ? BASE_URL . '/admin/settings.php' : BASE_URL . '/profile.php'; ?>" aria-label="Settings">
        <span class="gear" aria-hidden="true"></span>
      </a>
      <div class="profile-menu">
        <button class="profile-trigger" type="button" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
          <span class="avatar"><?php echo e($initials); ?></span>
          <span class="profile-trigger-text">
            <span class="profile-trigger-name"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></span>
            <span class="profile-trigger-role"><?php echo e($roleLabel); ?></span>
          </span>
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

  <section class="workflow-shell">
    <div class="workflow-steps">
      <?php foreach ($workflow as $index => $step): ?>
        <?php
          $state = 'upcoming';
          if ($index < $currentStep) {
              $state = 'done';
          } elseif ($index === $currentStep) {
              $state = 'current';
          }
        ?>
        <a class="workflow-step <?php echo e($state); ?>" href="<?php echo $step['href']; ?>">
          <span class="workflow-step-index"><?php echo $index + 1; ?></span>
          <span class="workflow-step-copy">
            <span class="workflow-step-title"><?php echo e($step['label']); ?></span>
            <span class="workflow-step-text"><?php echo e($step['summary']); ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="page-header">
    <div>
      <div class="page-title"><?php echo e($pageTitle); ?></div>
      <div class="page-sub"><?php echo $greetingName ? 'Signed in as ' . e($greetingName) . '.' : 'Welcome back.'; ?></div>
    </div>
  </section>

  <?php if ($flash): ?>
    <div class="alert <?php echo e($flash['type']); ?>">
      <?php echo e($flash['message']); ?>
    </div>
  <?php endif; ?>
