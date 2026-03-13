<?php
$isAdmin = strtolower($roleLabel) === 'administrator';

$iconMap = [
    'Dashboard' => 'dashboard',
    'User Management' => 'users',
    'Student Records' => 'students',
    'Faculty / Instructor Management' => 'faculty',
    'Classes and Schedules' => 'classes',
    'Enrollment Monitoring' => 'enrollment',
    'Grade Records' => 'grades',
    'Academic Reports' => 'reports',
    'System Logs' => 'logs',
    'System Settings' => 'settings',
    'Student Management' => 'students',
    'Enroll Students' => 'enrollment',
    'Class Lists' => 'classes',
    'Manage Classes and Schedules' => 'classes',
    'Record Grades' => 'grades',
    'Document Requests' => 'documents',
    'Update Records' => 'students',
];

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
        'Campus Control' => [
            ['label' => 'Dashboard', 'href' => BASE_URL . '/admin/dashboard.php'],
            ['label' => 'User Management', 'href' => BASE_URL . '/admin/users.php'],
        ],
        'Registrar Workflow' => [
            ['label' => 'Student Records', 'href' => BASE_URL . '/staff/students.php', 'active' => 'Student Management'],
            ['label' => 'Faculty / Instructor Management', 'href' => BASE_URL . '/staff/instructors.php', 'active' => 'Instructor Management'],
            ['label' => 'Classes and Schedules', 'href' => BASE_URL . '/staff/classes.php', 'active' => 'Manage Classes & Schedules'],
            ['label' => 'Enrollment Monitoring', 'href' => BASE_URL . '/staff/enrollments.php', 'active' => 'Enrollment'],
            ['label' => 'Grade Records', 'href' => BASE_URL . '/staff/grades.php', 'active' => 'Grade Management'],
            ['label' => 'Academic Reports', 'href' => BASE_URL . '/admin/academic_reports.php'],
            ['label' => 'System Logs', 'href' => BASE_URL . '/admin/activity.php', 'active' => 'System Activity'],
        ],
        'Account' => [
            ['label' => 'System Settings', 'href' => BASE_URL . '/admin/settings.php'],
        ],
    ];
} else {
    $navItems = [
        'Registrar System' => [
            ['label' => 'Dashboard', 'href' => BASE_URL . '/staff/dashboard.php'],
        ],
        'Student Workflow' => [
            ['label' => 'Student Management', 'href' => BASE_URL . '/staff/students.php'],
            ['label' => 'Faculty / Instructor Management', 'href' => BASE_URL . '/staff/instructors.php', 'active' => 'Instructor Management'],
            ['label' => 'Enroll Students', 'href' => BASE_URL . '/staff/enrollments.php', 'active' => 'Enrollment'],
            ['label' => 'Class Lists', 'href' => BASE_URL . '/staff/class_lists.php'],
            ['label' => 'Manage Classes and Schedules', 'href' => BASE_URL . '/staff/classes.php', 'active' => 'Manage Classes & Schedules'],
            ['label' => 'Record Grades', 'href' => BASE_URL . '/staff/grades.php', 'active' => 'Grade Management'],
            ['label' => 'Document Requests', 'href' => BASE_URL . '/staff/documents.php'],
            ['label' => 'Update Records', 'href' => BASE_URL . '/staff/students.php', 'active' => 'Student Management'],
        ],
        'Account' => [
            ['label' => 'System Settings', 'href' => BASE_URL . '/profile.php'],
        ],
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
      <div class="brand-sub">of the Philippines</div>
      <div class="brand-meta">Registrar and Academic Records</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($navItems as $section => $items): ?>
      <div class="nav-section"><?php echo e($section); ?></div>
      <div class="nav">
        <?php foreach ($items as $item): ?>
          <?php
            $activeKey = $item['active'] ?? $item['label'];
            $active = $activeNav === $activeKey ? 'active' : '';
            $short = $shorten($item['label']);
            $icon = $iconMap[$item['label']] ?? 'dashboard';
          ?>
          <a class="nav-item <?php echo $active; ?>" href="<?php echo $item['href']; ?>" data-short="<?php echo e($short); ?>">
            <span class="nav-icon <?php echo e($icon); ?>" aria-hidden="true"></span>
            <span class="nav-text"><?php echo e($item['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-profile">
    <a class="sidebar-profile-main" href="<?php echo BASE_URL; ?>/profile.php">
      <span class="sidebar-avatar"><?php echo e($initials); ?></span>
      <span class="sidebar-profile-text">
        <span class="sidebar-profile-name"><?php echo e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Guest User'); ?></span>
        <span class="sidebar-profile-role"><?php echo e($roleLabel); ?></span>
      </span>
    </a>
    <div class="sidebar-profile-actions">
      <a class="sidebar-mini-btn" href="<?php echo BASE_URL; ?>/profile.php" aria-label="Profile">
        <span class="nav-icon users" aria-hidden="true"></span>
      </a>
      <a class="sidebar-mini-btn" href="<?php echo $isAdmin ? BASE_URL . '/admin/settings.php' : BASE_URL . '/profile.php'; ?>" aria-label="Settings">
        <span class="nav-icon settings" aria-hidden="true"></span>
      </a>
      <a class="sidebar-mini-btn" href="<?php echo BASE_URL; ?>/auth/logout.php" aria-label="Logout">
        <span class="nav-icon logs" aria-hidden="true"></span>
      </a>
    </div>
  </div>
</aside>
