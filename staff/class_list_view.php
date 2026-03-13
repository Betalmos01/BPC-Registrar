<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();

$classId = (int)($_GET['class_id'] ?? 0);
if ($classId <= 0) {
    set_flash('Missing class id.', 'error');
    header('Location: ' . BASE_URL . '/staff/class_lists.php');
    exit;
}

$classStmt = $pdo->prepare('SELECT classes.id, classes.class_code, classes.title, classes.course, classes.units, schedules.day, schedules.time, schedules.room FROM classes LEFT JOIN schedules ON classes.id = schedules.class_id WHERE classes.id = :id LIMIT 1');
$classStmt->execute(['id' => $classId]);
$class = $classStmt->fetch();
if (!$class) {
    set_flash('Class not found.', 'error');
    header('Location: ' . BASE_URL . '/staff/class_lists.php');
    exit;
}

$rosterStmt = $pdo->prepare(
    "SELECT enrollments.id AS enrollment_id, enrollments.status AS enrollment_status,
            students.id AS student_id, students.student_no, students.first_name, students.last_name, students.program, students.year_level,
            grades.id AS grade_id, grades.grade, grades.remarks
     FROM enrollments
     JOIN students ON enrollments.student_id = students.id
     LEFT JOIN grades ON grades.student_id = enrollments.student_id AND grades.class_id = enrollments.class_id
     WHERE enrollments.class_id = :class_id
     ORDER BY students.last_name, students.first_name"
);
$rosterStmt->execute(['class_id' => $classId]);
$roster = $rosterStmt->fetchAll();

$enrolledCount = 0;
$pendingCount = 0;
foreach ($roster as $row) {
    $normalized = strtolower(trim((string)$row['enrollment_status']));
    if ($normalized === 'enrolled') {
        $enrolledCount++;
    } else {
        $pendingCount++;
    }
}

$pageTitle = 'Class List';
$activeNav = 'Class List View';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>

<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Class</div>
    <div class="module-value"><?php echo e($class['class_code']); ?></div>
    <div class="module-note"><?php echo e($class['title']); ?></div>
  </article>
  <article class="module-card">
    <div class="module-label">Enrolled</div>
    <div class="module-value"><?php echo (int)$enrolledCount; ?></div>
    <div class="module-note">Officially enrolled students in this roster.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Pending</div>
    <div class="module-value"><?php echo (int)$pendingCount; ?></div>
    <div class="module-note">Students awaiting final validation or slot confirmation.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header" style="align-items:center;">
    <div>
      <h2>Class Roster</h2>
      <p><?php echo e($class['course']); ?> · <?php echo e($class['day']); ?> · <?php echo e($class['time']); ?> · <?php echo e($class['room']); ?></p>
    </div>
    <div class="panel-actions">
      <a class="secondary btn-sm" href="<?php echo BASE_URL; ?>/staff/class_lists.php">Back to Class Lists</a>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Program</th>
          <th>Year</th>
          <th>Status</th>
          <th>Grade</th>
          <th>Remarks</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$roster): ?>
          <tr><td colspan="7" class="empty">No enrollments found for this class.</td></tr>
        <?php endif; ?>
        <?php foreach ($roster as $row): ?>
          <?php $canGrade = strtolower(trim((string)$row['enrollment_status'])) === 'enrolled'; ?>
          <tr>
            <td><?php echo e($row['student_no'] . ' - ' . $row['last_name'] . ', ' . $row['first_name']); ?></td>
            <td><?php echo e($row['program']); ?></td>
            <td><?php echo e($row['year_level']); ?></td>
            <td><span class="status <?php echo status_class((string)$row['enrollment_status']); ?>"><?php echo e($row['enrollment_status']); ?></span></td>
            <td><?php echo e((string)($row['grade'] ?? '')); ?></td>
            <td><?php echo e((string)($row['remarks'] ?? '')); ?></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-grade-record"
                  type="button"
                  data-grade-id="<?php echo (int)($row['grade_id'] ?? 0); ?>"
                  data-student-id="<?php echo (int)$row['student_id']; ?>"
                  data-class-id="<?php echo (int)$classId; ?>"
                  data-student-label="<?php echo e($row['student_no'] . ' - ' . $row['last_name'] . ', ' . $row['first_name']); ?>"
                  data-grade="<?php echo e((string)($row['grade'] ?? '')); ?>"
                  data-remarks="<?php echo e((string)($row['remarks'] ?? '')); ?>"
                  <?php echo $canGrade ? '' : 'disabled'; ?>
                  title="<?php echo $canGrade ? 'Record or update grade' : 'Only enrolled students can be graded'; ?>"
                ><?php echo ($row['grade_id'] ?? null) ? 'Update Grade' : 'Record Grade'; ?></button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  (() => {
    const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    const escapeHtml = (value) =>
      String(value || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const openModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    document.querySelectorAll('.js-grade-record').forEach((btn) => {
      btn.addEventListener('click', () => {
        const gradeId = Number(btn.dataset.gradeId || 0);
        const studentId = btn.dataset.studentId;
        const classId = btn.dataset.classId;
        const label = btn.dataset.studentLabel || '';
        const gradeVal = btn.dataset.grade || '';
        const remarksVal = btn.dataset.remarks || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0 0 10px;color:var(--muted);font-size:13px">${escapeHtml(label)}</p>
          <form class="form-grid" id="roster-grade-form">
            <label>Grade<input name="grade" type="text" required value="${escapeHtml(gradeVal)}" placeholder="1.00 / A / 95" /></label>
            <label>Remarks<input name="remarks" type="text" value="${escapeHtml(remarksVal)}" placeholder="Passed" /></label>
          </form>
        `;

        openModal(
          gradeId ? 'Update Grade' : 'Record Grade',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#roster-grade-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              if (gradeId) {
                fd.set('action', 'update');
                fd.set('id', String(gradeId));
              } else {
                fd.set('action', 'create');
                fd.set('student_id', studentId);
                fd.set('class_id', classId);
              }
              await window.RegistrarApi.post(`${BASE_URL}/api/grades.php`, fd);
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
          gradeId ? 'Save' : 'Record',
          'primary'
        );
      });
    });
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
