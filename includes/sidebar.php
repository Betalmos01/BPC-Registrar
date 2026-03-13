<?php
$nav = [
    'Dashboard' => [
        ['label' => 'Dashboard', 'href' => $roleLabel === 'Administrator' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/staff/dashboard.php'],
    ],
    'Student Services' => [
        ['label' => 'Student Management', 'href' => BASE_URL . '/staff/students.php'],
        ['label' => 'Enrollment', 'href' => BASE_URL . '/staff/enrollments.php'],
        ['label' => 'Class Schedules', 'href' => BASE_URL . '/staff/schedules.php'],
        ['label' => 'Document Requests', 'href' => BASE_URL . '/staff/documents.php'],
    ],
    'Faculty / Instructors' => [
        ['label' => 'Class Lists', 'href' => BASE_URL . '/staff/class_lists.php'],
        ['label' => 'Grade Management', 'href' => BASE_URL . '/staff/grades.php'],
    ],
    'Registrar Staff' => [
        ['label' => 'Manage Classes & Schedules', 'href' => BASE_URL . '/staff/classes.php'],
        ['label' => 'Staff Directory', 'href' => BASE_URL . '/staff/staff_directory.php'],
    ],
    'Administration' => [
        ['label' => 'Reports', 'href' => BASE_URL . '/admin/reports.php'],
        ['label' => 'System Activity', 'href' => BASE_URL . '/admin/activity.php'],
        ['label' => 'Academic Reports', 'href' => BASE_URL . '/admin/academic_reports.php'],
        ['label' => 'System Settings', 'href' => BASE_URL . '/admin/settings.php'],
    ],
    'System' => [
        ['label' => 'Profile', 'href' => BASE_URL . '/profile.php'],
        ['label' => 'Logout', 'href' => BASE_URL . '/auth/logout.php'],
    ],
];
?>
<aside class="sidebar">
  <div class="brand">
    <div class="brand-logo">
      <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="Bestlink College" />
    </div>
    <div>
      <div class="brand-title">Bestlink College</div>
      <div class="brand-sub">Registrar System</div>
    </div>
  </div>

  <?php foreach ($nav as $section => $items): ?>
    <?php
      if ($section === 'Administration' && strtolower($roleLabel) !== 'administrator') {
          continue;
      }
      if ($section === 'Registrar Staff' && strtolower($roleLabel) !== 'registrar staff') {
          if (strtolower($roleLabel) !== 'administrator') {
              continue;
          }
      }
    ?>
    <div class="nav-section"><?php echo e($section); ?></div>
    <nav class="nav">
      <?php foreach ($items as $item): ?>
        <?php $active = $activeNav === $item['label'] ? 'active' : ''; ?>
        <a class="nav-item <?php echo $active; ?>" href="<?php echo $item['href']; ?>">
          <span class="dot"></span>
          <?php echo e($item['label']); ?>
        </a>
      <?php endforeach; ?>
    </nav>
  <?php endforeach; ?>

  <div class="sidebar-footer">v1.0.0</div>
</aside>

