<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

$students = $pdo->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$classes = $pdo->query('SELECT id, class_code, title FROM classes ORDER BY class_code')->fetchAll();
$enrollments = $pdo->query('SELECT enrollments.id, students.student_no, students.first_name, students.last_name, classes.class_code, classes.title, enrollments.status FROM enrollments JOIN students ON enrollments.student_id = students.id JOIN classes ON enrollments.class_id = classes.id ORDER BY enrollments.created_at DESC')->fetchAll();
$pendingEnrollments = 0;
$confirmedEnrollments = 0;
foreach ($enrollments as $entry) {
    $normalized = strtolower(trim($entry['status']));
    if ($normalized === 'pending' || $normalized === 'waitlisted') {
        $pendingEnrollments++;
    }
    if ($normalized === 'enrolled') {
        $confirmedEnrollments++;
    }
}

$pageTitle = 'Enrollment';
$activeNav = 'Enrollment';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Student Pool</div>
    <div class="module-value"><?php echo count($students); ?></div>
    <div class="module-note">Registered students available for enrollment processing.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Confirmed Enrollments</div>
    <div class="module-value"><?php echo $confirmedEnrollments; ?></div>
    <div class="module-note">Students already matched to an official class load.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Pending Review</div>
    <div class="module-value"><?php echo $pendingEnrollments; ?></div>
    <div class="module-note">Transactions awaiting final confirmation, slot availability, or registrar review.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Enroll Students</h2>
      <p>Validate student-to-section assignment here after records and class schedules have already been completed upstream.</p>
    </div>
  </div>

  <form class="form-grid" method="post" action="<?php echo BASE_URL; ?>/api/enrollments.php">
    <input type="hidden" name="action" value="create" />
    <input type="hidden" name="redirect" value="<?php echo BASE_URL; ?>/staff/enrollments.php" />
    <label>
      Student
      <select name="student_id" required>
        <option value="">Select student</option>
        <?php foreach ($students as $student): ?>
          <option value="<?php echo $student['id']; ?>"><?php echo e($student['student_no'] . ' - ' . $student['last_name'] . ', ' . $student['first_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Class
      <select name="class_id" required>
        <option value="">Select class</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?php echo $class['id']; ?>"><?php echo e($class['class_code'] . ' - ' . $class['title']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Status
      <select name="status">
        <option>Enrolled</option>
        <option>Pending</option>
        <option>Waitlisted</option>
      </select>
    </label>
    <button class="primary" type="submit">Add Enrollment</button>
  </form>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Enrollment Register</h2>
      <p>This register becomes the source for class rosters, academic load tracking, and grade posting in the next workflow stage.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Class</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$enrollments): ?>
          <tr><td colspan="4" class="empty">No enrollment records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($enrollments as $enroll): ?>
          <tr>
            <td><?php echo e($enroll['student_no'] . ' - ' . $enroll['last_name'] . ', ' . $enroll['first_name']); ?></td>
            <td><?php echo e($enroll['class_code'] . ' - ' . $enroll['title']); ?></td>
            <td><span class="status <?php echo status_class($enroll['status']); ?>"><?php echo e($enroll['status']); ?></span></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-enrollment-edit"
                  type="button"
                  data-id="<?php echo (int)$enroll['id']; ?>"
                  data-status="<?php echo e($enroll['status']); ?>"
                  data-label="<?php echo e($enroll['student_no'] . ' - ' . $enroll['last_name'] . ', ' . $enroll['first_name'] . ' | ' . $enroll['class_code']); ?>"
                >Update</button>
                <button
                  class="secondary btn-sm danger js-enrollment-delete"
                  type="button"
                  data-id="<?php echo (int)$enroll['id']; ?>"
                  data-label="<?php echo e($enroll['student_no'] . ' | ' . $enroll['class_code']); ?>"
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
  (() => {
    const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    const escapeHtml = (value) =>
      String(value || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const openModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    document.querySelectorAll('.js-enrollment-edit').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const status = btn.dataset.status || 'Enrolled';
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0 0 10px;color:var(--muted);font-size:13px">${escapeHtml(label)}</p>
          <form class="form-grid" id="enrollment-edit-form">
            <label>Status
              <select name="status">
                <option ${status === 'Enrolled' ? 'selected' : ''}>Enrolled</option>
                <option ${status === 'Pending' ? 'selected' : ''}>Pending</option>
                <option ${status === 'Waitlisted' ? 'selected' : ''}>Waitlisted</option>
              </select>
            </label>
          </form>
        `;

        openModal(
          'Update Enrollment',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#enrollment-edit-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'update');
              fd.set('id', id);
              await window.RegistrarApi.post(`${BASE_URL}/api/enrollments.php`, fd);
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

    document.querySelectorAll('.js-enrollment-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete enrollment <strong>${escapeHtml(label)}</strong>?</p>
        `;

        openModal(
          'Delete Enrollment',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/enrollments.php`, { action: 'delete', id });
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
  })();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

