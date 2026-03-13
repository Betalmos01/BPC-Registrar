<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();

$search = trim($_GET['q'] ?? '');
$day = trim($_GET['day'] ?? '');
$room = trim($_GET['room'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$params = [];
$whereParts = [];
if ($search) {
    $whereParts[] = '(classes.class_code LIKE :q OR classes.title LIKE :q OR schedules.room LIKE :q)';
    $params['q'] = '%' . $search . '%';
}
if ($day) {
    $whereParts[] = 'schedules.day = :day';
    $params['day'] = $day;
}
if ($room) {
    $whereParts[] = 'schedules.room = :room';
    $params['room'] = $room;
}
$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM schedules JOIN classes ON schedules.class_id = classes.id $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, PER_PAGE);
$params['limit'] = $pagination['perPage'];
$params['offset'] = $pagination['offset'];

$sql = "SELECT schedules.id, classes.id AS class_id, classes.class_code, classes.title, classes.course, schedules.day, schedules.time, schedules.room
        FROM schedules
        JOIN classes ON schedules.class_id = classes.id
        $where
        ORDER BY schedules.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $type = in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key === 'q' ? ':q' : ':' . $key, $value, $type);
}
$stmt->execute();
$schedules = $stmt->fetchAll();

$days = $pdo->query("SELECT DISTINCT day FROM schedules WHERE day <> '' ORDER BY day")->fetchAll(PDO::FETCH_COLUMN);
$rooms = $pdo->query("SELECT DISTINCT room FROM schedules WHERE room <> '' ORDER BY room")->fetchAll(PDO::FETCH_COLUMN);

$activeRooms = count($rooms);
$activeDays = count($days);

$pageTitle = 'Class Schedules';
$activeNav = 'Class Schedules';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Schedules</div>
    <div class="module-value"><?php echo (int)$total; ?></div>
    <div class="module-note">Published schedule entries based on planned classes.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Days Used</div>
    <div class="module-value"><?php echo (int)$activeDays; ?></div>
    <div class="module-note">Distinct meeting days in the schedule list.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Rooms Used</div>
    <div class="module-value"><?php echo (int)$activeRooms; ?></div>
    <div class="module-note">Rooms or labs currently assigned across sections.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Class Schedules</h2>
      <p>Review schedules for students and instructors.</p>
    </div>
    <div class="panel-actions">
      <form method="get" class="inline-form">
        <input type="hidden" name="q" value="<?php echo e($search); ?>" />
        <select name="day" onchange="this.form.submit()">
          <option value="">All Days</option>
          <?php foreach ($days as $d): ?>
            <option value="<?php echo e($d); ?>" <?php echo $day === $d ? 'selected' : ''; ?>><?php echo e($d); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="room" onchange="this.form.submit()">
          <option value="">All Rooms</option>
          <?php foreach ($rooms as $r): ?>
            <option value="<?php echo e($r); ?>" <?php echo $room === $r ? 'selected' : ''; ?>><?php echo e($r); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($day || $room): ?>
          <a class="secondary btn-sm" href="<?php echo BASE_URL; ?>/staff/schedules.php<?php echo $search ? '?q=' . urlencode($search) : ''; ?>">Clear</a>
        <?php endif; ?>
        <a class="primary btn-sm" href="<?php echo BASE_URL; ?>/staff/classes.php">Edit Schedules</a>
      </form>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Class Code</th>
          <th>Title</th>
          <th>Course</th>
          <th>Day</th>
          <th>Time</th>
          <th>Room</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$schedules): ?>
          <tr><td colspan="7" class="empty">No schedules available.</td></tr>
        <?php endif; ?>
        <?php foreach ($schedules as $schedule): ?>
          <tr>
            <td><?php echo e($schedule['class_code']); ?></td>
            <td><?php echo e($schedule['title']); ?></td>
            <td><?php echo e($schedule['course']); ?></td>
            <td><?php echo e($schedule['day']); ?></td>
            <td><?php echo e($schedule['time']); ?></td>
            <td><?php echo e($schedule['room']); ?></td>
            <td><a class="secondary btn-sm" href="<?php echo BASE_URL; ?>/staff/classes.php">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php echo build_pagination(BASE_URL . '/staff/schedules.php', $pagination, ['q' => $search, 'day' => $day, 'room' => $room]); ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
