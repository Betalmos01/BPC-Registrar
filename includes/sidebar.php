<?php
$isAdmin = strtolower($roleLabel) === 'administrator';

$shorten = static function (string $label): string {
    $label = preg_replace('/[^A-Za-z0-9 ]+/', '', $label);
    $parts = preg_split('/\s+/', trim($label));
    $initials = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 3) {
            break;
        }
    }
    return $initials ?: strtoupper(substr($label, 0, 2));
};

if ($isAdmin) {
    $navItems = [
        ['label' => 'Dashboard', 'href' => BASE_URL . '/admin/dashboard.php'],
        ['label' => 'User Management', 'href' => BASE_URL . '/admin/users.php'],
        ['label' => 'Student Records', 'href' => BASE_URL . '/staff/students.php', 'active' => 'Student Management'],
        ['label' => 'Faculty / Instructor Management', 'href' => BASE_URL . '/staff/staff_directory.php', 'active' => 'Staff Directory'],
        ['label' => 'Classes and Schedules', 'href' => BASE_URL . '/staff/classes.php', 'active' => 'Manage Classes & Schedules'],
        ['label' => 'Enrollment Monitoring', 'href' => BASE_URL . '/staff/enrollments.php', 'active' => 'Enrollment'],
        ['label' => 'Grade Records', 'href' => BASE_URL . '/staff/grades.php', 'active' => 'Grade Management'],
        ['label' => 'Academic Reports', 'href' => BASE_URL . '/admin/academic_reports.php'],
        ['label' => 'System Logs', 'href' => BASE_URL . '/admin/activity.php', 'active' => 'System Activity'],
        ['label' => 'System Settings', 'href' => BASE_URL . '/admin/settings.php'],
    ];
} else {
    $navItems = [
        ['label' => 'Dashboard', 'href' => BASE_URL . '/staff/dashboard.php'],
        ['label' => 'Student Management', 'href' => BASE_URL . '/staff/students.php'],
        ['label' => 'Enroll Students', 'href' => BASE_URL . '/staff/enrollments.php', 'active' => 'Enrollment'],
        ['label' => 'Class Lists', 'href' => BASE_URL . '/staff/class_lists.php'],
        ['label' => 'Manage Classes and Schedules', 'href' => BASE_URL . '/staff/classes.php', 'active' => 'Manage Classes & Schedules'],
        ['label' => 'Record Grades', 'href' => BASE_URL . '/staff/grades.php', 'active' => 'Grade Management'],
        ['label' => 'Document Requests', 'href' => BASE_URL . '/staff/documents.php'],
        ['label' => 'Update Records', 'href' => BASE_URL . '/staff/students.php', 'active' => 'Student Management'],
    ];
}
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

  <nav class="nav">
    <?php foreach ($navItems as $item): ?>
      <?php
        $activeKey = $item['active'] ?? $item['label'];
        $active = $activeNav === $activeKey ? 'active' : '';
        $short = $shorten($item['label']);
      ?>
      <a class="nav-item <?php echo $active; ?>" href="<?php echo $item['href']; ?>" data-short="<?php echo e($short); ?>">
        <span class="dot"></span>
        <span class="nav-text"><?php echo e($item['label']); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">v1.0.0</div>
</aside>
