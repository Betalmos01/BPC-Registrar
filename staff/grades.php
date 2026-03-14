<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

$students = $pdo->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$classes = $pdo->query('SELECT id, class_code, title FROM classes ORDER BY class_code')->fetchAll();
$grades = $pdo->query('SELECT grades.id, students.student_no, students.first_name, students.last_name, classes.class_code, classes.title, grades.grade, grades.remarks FROM grades JOIN students ON grades.student_id = students.id JOIN classes ON grades.class_id = classes.id ORDER BY grades.created_at DESC')->fetchAll();
$passedCount = 0;
foreach ($grades as $entry) {
    if (stripos((string)$entry['remarks'], 'pass') !== false) {
        $passedCount++;
    }
}

$pageTitle = 'Grade Management';
$activeNav = 'Grade Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Grade Records</div>
    <div class="module-value"><?php echo count($grades); ?></div>
    <div class="module-note">Academic results already posted into the registrar system.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Passed Remarks</div>
    <div class="module-value"><?php echo $passedCount; ?></div>
    <div class="module-note">Completions already tagged as passed or successfully completed.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Ready for Release</div>
    <div class="module-value"><?php echo count($grades); ?></div>
    <div class="module-note">Posted grades that can feed transcript, report, and completion services.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Record Grades</h2>
      <p>Finalize academic outcomes only for students with completed enrollment and class history in the registrar workflow.</p>
    </div>
    <div class="panel-actions">
      <button class="primary btn-sm js-grade-create" type="button">Add Grade</button>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Grade Records</h2>
      <p>This is the closing academic endpoint before transcript generation, retention review, and institutional reporting.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Class</th>
          <th>Grade</th>
          <th>Remarks</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$grades): ?>
          <tr><td colspan="5" class="empty">No grade records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($grades as $grade): ?>
          <tr>
            <td><?php echo e($grade['student_no'] . ' - ' . $grade['last_name'] . ', ' . $grade['first_name']); ?></td>
            <td><?php echo e($grade['class_code'] . ' - ' . $grade['title']); ?></td>
            <td><?php echo e($grade['grade']); ?></td>
            <td><?php echo e($grade['remarks']); ?></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-grade-edit"
                  type="button"
                  data-id="<?php echo (int)$grade['id']; ?>"
                  data-grade="<?php echo e($grade['grade']); ?>"
                  data-remarks="<?php echo e($grade['remarks']); ?>"
                  data-label="<?php echo e($grade['student_no'] . ' | ' . $grade['class_code']); ?>"
                >Edit</button>
                <button
                  class="secondary btn-sm danger js-grade-delete"
                  type="button"
                  data-id="<?php echo (int)$grade['id']; ?>"
                  data-label="<?php echo e($grade['student_no'] . ' | ' . $grade['class_code']); ?>"
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
    const students = <?php echo json_encode($students); ?>;
    const classes = <?php echo json_encode($classes); ?>;
    const escapeHtml = (value) =>
      String(value || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const openModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    const optionList = (items, labelFn) =>
      (items || []).map((item) => `<option value="${item.id}">${escapeHtml(labelFn(item))}</option>`).join('');

    const createButton = document.querySelector('.js-grade-create');
    if (createButton) {
      createButton.addEventListener('click', () => {
        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="grade-create-form">
            <label>Student
              <select name="student_id" required>
                <option value="">Select student</option>
                ${optionList(students, (s) => `${s.student_no} - ${s.last_name}, ${s.first_name}`)}
              </select>
            </label>
            <label>Class
              <select name="class_id" required>
                <option value="">Select class</option>
                ${optionList(classes, (c) => `${c.class_code} - ${c.title}`)}
              </select>
            </label>
            <label>Grade<input name="grade" type="text" placeholder="1.00 / A / 95" required /></label>
            <label>Remarks<input name="remarks" type="text" placeholder="Passed" /></label>
          </form>
        `;

        openModal(
          'Add Grade',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#grade-create-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'create');
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
          'Create Grade',
          'primary'
        );
      });
    }

    document.querySelectorAll('.js-grade-edit').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const grade = btn.dataset.grade || '';
        const remarks = btn.dataset.remarks || '';
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0 0 10px;color:var(--muted);font-size:13px">${escapeHtml(label)}</p>
          <form class="form-grid" id="grade-edit-form">
            <label>Grade<input name="grade" type="text" required value="${escapeHtml(grade)}" /></label>
            <label>Remarks<input name="remarks" type="text" value="${escapeHtml(remarks)}" /></label>
          </form>
        `;

        openModal(
          'Edit Grade',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#grade-edit-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'update');
              fd.set('id', id);
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
          'Save',
          'primary'
        );
      });
    });

    document.querySelectorAll('.js-grade-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete grade record <strong>${escapeHtml(label)}</strong>?</p>
        `;

        openModal(
          'Delete Grade Record',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/grades.php`, { action: 'delete', id });
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
