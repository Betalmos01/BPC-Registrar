<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

// Ensure course column exists (self-heal on older DB schema).
try {
    $pdo->query('SELECT course FROM classes LIMIT 1');
} catch (PDOException $e) {
    if ($e->getCode() === '42S22') {
        $pdo->exec("ALTER TABLE classes ADD COLUMN course VARCHAR(120) DEFAULT ''");
    } else {
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Legacy: CRUD is handled by /api/classes.php (redirects back with flash messages).
    header('Location: ' . BASE_URL . '/staff/classes.php');
    exit;
}

$filterCourse = trim($_GET['course'] ?? '');
$query = 'SELECT classes.id, classes.class_code, classes.title, classes.course, classes.units, schedules.day, schedules.time, schedules.room FROM classes LEFT JOIN schedules ON classes.id = schedules.class_id';
$params = [];
if ($filterCourse !== '') {
    $query .= ' WHERE classes.course = :course';
    $params['course'] = $filterCourse;
}
$query .= ' ORDER BY classes.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$classes = $stmt->fetchAll();
$scheduledCount = count($classes);
$roomCount = count(array_unique(array_filter(array_column($classes, 'room'))));
$courseCount = count(array_unique(array_filter(array_column($classes, 'course'))));

$days = $pdo->query("SELECT DISTINCT day FROM schedules WHERE day <> '' ORDER BY day")->fetchAll(PDO::FETCH_COLUMN);
$times = $pdo->query("SELECT DISTINCT time FROM schedules WHERE time <> '' ORDER BY time")->fetchAll(PDO::FETCH_COLUMN);
$rooms = $pdo->query("SELECT DISTINCT room FROM schedules WHERE room <> '' ORDER BY room")->fetchAll(PDO::FETCH_COLUMN);
$courses = $pdo->query("SELECT DISTINCT course FROM classes WHERE course <> '' ORDER BY course")->fetchAll(PDO::FETCH_COLUMN);

if (!$days) {
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
}
if (!$times) {
    $times = [
        '7:00 AM - 9:00 AM',
        '9:00 AM - 11:00 AM',
        '11:00 AM - 1:00 PM',
        '1:00 PM - 3:00 PM',
        '3:00 PM - 5:00 PM',
        '5:00 PM - 7:00 PM',
    ];
}
if (!$rooms) {
    $rooms = ['Room 101', 'Room 102', 'Room 201', 'Room 202', 'Room 301', 'Room 302', 'Lab 1', 'Lab 2'];
}
if (!$courses) {
    $courses = ['BSIT', 'BSCS', 'BSBA', 'BSED', 'BEED', 'BSECE'];
}
$catalogRows = $pdo->query("SELECT class_code, title FROM classes ORDER BY class_code")->fetchAll();
$classCatalog = [];
foreach ($catalogRows as $row) {
    $code = (string)($row['class_code'] ?? '');
    if ($code === '') {
        continue;
    }
    $classCatalog[$code] = (string)($row['title'] ?? '');
}

$pageTitle = 'Manage Classes & Schedules';
$activeNav = 'Manage Classes & Schedules';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Open Sections</div>
    <div class="module-value"><?php echo (int)$scheduledCount; ?></div>
    <div class="module-note">Available class offerings prepared for enrollment validation.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Courses Planned</div>
    <div class="module-value"><?php echo (int)$courseCount; ?></div>
    <div class="module-note">Programs with live academic load options in the current setup.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Rooms Utilized</div>
    <div class="module-value"><?php echo (int)$roomCount; ?></div>
    <div class="module-note">Physical rooms and labs assigned across scheduled sections.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Class Scheduling</h2>
      <p>Create section offerings with complete schedule details before students are tagged as officially enrolled.</p>
    </div>
  </div>

  <form class="form-grid" method="post" action="<?php echo BASE_URL; ?>/api/classes.php">
    <input type="hidden" name="action" value="create" />
    <input type="hidden" name="redirect" value="<?php echo BASE_URL; ?>/staff/classes.php" />
    <label>
      Class Code
      <input type="text" name="class_code" list="class-codes" required />
      <datalist id="class-codes">
        <?php foreach ($classCatalog as $code => $label): ?>
          <option value="<?php echo e($code); ?>"><?php echo e($label); ?></option>
        <?php endforeach; ?>
      </datalist>
    </label>
    <label>
      Class Title
      <input type="text" name="class_title" required />
    </label>
    <label>
      Course
      <input type="text" name="course" list="course-options" placeholder="BSIT / BSCS" />
      <datalist id="course-options">
        <?php foreach ($courses as $course): ?>
          <option value="<?php echo e($course); ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </label>
    <label>
      Units
      <input type="number" name="units" min="1" max="6" value="3" />
    </label>
    <label>
      Day
      <input type="text" name="day" list="day-options" placeholder="Monday" />
      <datalist id="day-options">
        <?php foreach ($days as $day): ?>
          <option value="<?php echo e($day); ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </label>
    <label>
      Time (2 hrs)
      <input type="text" name="time" list="time-options" placeholder="7:00 AM - 9:00 AM" />
      <datalist id="time-options">
        <?php foreach ($times as $slot): ?>
          <option value="<?php echo e($slot); ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </label>
    <label>
      Room
      <input type="text" name="room" list="room-options" placeholder="Room 101 / Lab 1" />
      <datalist id="room-options">
        <?php foreach ($rooms as $room): ?>
          <option value="<?php echo e($room); ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </label>
    <button class="primary" type="submit">Add Class</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header" style="align-items: center; gap: 12px;">
    <div>
      <h2>Class List</h2>
      <p>This schedule list is the handoff point into enrollment. Each section here should be ready for student assignment and academic tracking.</p>
    </div>
    <form method="get" style="display: flex; gap: 8px; align-items: center;">
      <label style="margin: 0;">
        <select name="course" onchange="this.form.submit()">
          <option value="">All Courses</option>
          <?php foreach ($courses as $course): ?>
            <option value="<?php echo e($course); ?>" <?php echo $filterCourse === $course ? 'selected' : ''; ?>><?php echo e($course); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Class Code</th>
          <th>Title</th>
          <th>Course</th>
          <th>Units</th>
          <th>Day</th>
          <th>Time</th>
          <th>Room</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$classes): ?>
          <tr><td colspan="8" class="empty">No classes scheduled yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($classes as $class): ?>
          <tr>
            <td><?php echo e($class['class_code']); ?></td>
            <td><?php echo e($class['title']); ?></td>
            <td><?php echo e($class['course']); ?></td>
            <td><?php echo e($class['units']); ?></td>
            <td><?php echo e($class['day']); ?></td>
            <td><?php echo e($class['time']); ?></td>
            <td><?php echo e($class['room']); ?></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-class-edit"
                  type="button"
                  data-id="<?php echo (int)$class['id']; ?>"
                  data-class-code="<?php echo e($class['class_code']); ?>"
                  data-class-title="<?php echo e($class['title']); ?>"
                  data-course="<?php echo e($class['course']); ?>"
                  data-units="<?php echo e((string)$class['units']); ?>"
                  data-day="<?php echo e($class['day']); ?>"
                  data-time="<?php echo e($class['time']); ?>"
                  data-room="<?php echo e($class['room']); ?>"
                >Edit</button>
                <button
                  class="secondary btn-sm danger js-class-delete"
                  type="button"
                  data-id="<?php echo (int)$class['id']; ?>"
                  data-label="<?php echo e($class['class_code']); ?>"
                >Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
  const classCatalog = <?php echo json_encode($classCatalog); ?>;
  const days = <?php echo json_encode($days); ?>;
  const times = <?php echo json_encode($times); ?>;
  const rooms = <?php echo json_encode($rooms); ?>;
  const courses = <?php echo json_encode($courses); ?>;
  const escapeHtml = (value) =>
    String(value || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const datalist = (id, items) =>
    `<datalist id="${id}">${(items || []).map((v) => `<option value="${escapeHtml(v)}"></option>`).join('')}</datalist>`;

  const codeInput = document.querySelector('input[name="class_code"]');
  const titleInput = document.querySelector('input[name="class_title"]');
  if (codeInput && titleInput) {
    codeInput.addEventListener('input', () => {
      const match = classCatalog[codeInput.value.trim()];
      if (match) {
        titleInput.value = match;
      }
    });
  }

  const openModal = (title, body, onSubmit, submitText, submitClass) => {
    if (!window.RegistrarModal) return;
    window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
  };

  document.querySelectorAll('.js-class-edit').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const classCode = btn.dataset.classCode || '';
      const classTitle = btn.dataset.classTitle || '';
      const course = btn.dataset.course || '';
      const units = btn.dataset.units || '3';
      const day = btn.dataset.day || '';
      const time = btn.dataset.time || '';
      const room = btn.dataset.room || '';

      const body = `
        <div class="modal-error" style="display:none"></div>
        <form class="form-grid" id="class-edit-form">
          <label>Class Code<input name="class_code" type="text" required value="${escapeHtml(classCode)}" /></label>
          <label>Class Title<input name="class_title" type="text" required value="${escapeHtml(classTitle)}" /></label>
          <label>Course<input name="course" type="text" list="modal-course-options" value="${escapeHtml(course)}" />${datalist('modal-course-options', courses)}</label>
          <label>Units<input name="units" type="number" min="1" max="6" value="${escapeHtml(units)}" /></label>
          <label>Day<input name="day" type="text" list="modal-day-options" value="${escapeHtml(day)}" />${datalist('modal-day-options', days)}</label>
          <label>Time (2 hrs)<input name="time" type="text" list="modal-time-options" value="${escapeHtml(time)}" />${datalist('modal-time-options', times)}</label>
          <label>Room<input name="room" type="text" list="modal-room-options" value="${escapeHtml(room)}" />${datalist('modal-room-options', rooms)}</label>
        </form>
      `;

      openModal(
        'Edit Class Schedule',
        body,
        async ({ modal, close, submit }) => {
          const errorBox = modal.querySelector('.modal-error');
          const form = modal.querySelector('#class-edit-form');
          try {
            submit.disabled = true;
            const fd = new FormData(form);
            fd.set('action', 'update');
            fd.set('class_id', id);
            await window.RegistrarApi.post(`${BASE_URL}/api/classes.php`, fd);
            close();
            window.location.reload();
          } catch (e) {
            submit.disabled = false;
            if (errorBox) {
              errorBox.style.display = '';
              errorBox.textContent = e.message || 'Request failed.';
            }
          }
        },
        'Save',
        'primary'
      );
    });
  });

  document.querySelectorAll('.js-class-delete').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const label = btn.dataset.label || '';

      const body = `
        <div class="modal-error" style="display:none"></div>
        <p style="margin:0">Delete class schedule <strong>${escapeHtml(label)}</strong>? This also removes linked schedules, enrollments, and grades.</p>
      `;

      openModal(
        'Delete Class Schedule',
        body,
        async ({ modal, close, submit }) => {
          const errorBox = modal.querySelector('.modal-error');
          try {
            submit.disabled = true;
            await window.RegistrarApi.post(`${BASE_URL}/api/classes.php`, { action: 'delete', class_id: id });
            close();
            window.location.reload();
          } catch (e) {
            submit.disabled = false;
            if (errorBox) {
              errorBox.style.display = '';
              errorBox.textContent = e.message || 'Request failed.';
            }
          }
        },
        'Delete',
        'danger primary'
      );
    });
  });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>


