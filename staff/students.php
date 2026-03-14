<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

$search = trim($_GET['q'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$params = [];
$where = '';
if ($search) {
    $where = "WHERE student_no LIKE :q OR first_name LIKE :q OR last_name LIKE :q";
    $params['q'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, PER_PAGE);
$params['limit'] = $pagination['perPage'];
$params['offset'] = $pagination['offset'];

$listStmt = $pdo->prepare("SELECT * FROM students $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $type = in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $listStmt->bindValue($key === 'q' ? ':q' : ':' . $key, $value, $type);
}
$listStmt->execute();
$students = $listStmt->fetchAll();
$activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
$onHoldStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'On Hold'")->fetchColumn();

$pageTitle = 'Student Management';
$activeNav = 'Student Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Intake Queue</div>
    <div class="module-value"><?php echo (int)$total; ?></div>
    <div class="module-note">Total student records in the registrar database.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Enrollment Ready</div>
    <div class="module-value"><?php echo (int)$activeStudents; ?></div>
    <div class="module-note">Students marked active and ready for downstream processing.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Needs Review</div>
    <div class="module-value"><?php echo (int)$onHoldStudents; ?></div>
    <div class="module-note">Records paused for validation, missing requirements, or manual checks.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Student Records</h2>
      <p>Register official student profiles first so class planning, enrollment confirmation, and grade release all inherit accurate identity data.</p>
    </div>
    <div class="panel-actions">
      <button class="primary btn-sm js-student-create" type="button">Add Student</button>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Student Master List</h2>
      <p>This list acts as the intake endpoint for the rest of the registrar workflow. Only validated records should move forward to enrollment.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student No</th>
          <th>Name</th>
          <th>Program</th>
          <th>Year</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$students): ?>
          <tr><td colspan="6" class="empty">No student records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $student): ?>
          <tr>
            <td><?php echo e($student['student_no']); ?></td>
            <td><?php echo e($student['first_name'] . ' ' . $student['last_name']); ?></td>
            <td><?php echo e($student['program']); ?></td>
            <td><?php echo e($student['year_level']); ?></td>
            <td><span class="status <?php echo status_class($student['status']); ?>"><?php echo e($student['status']); ?></span></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-student-edit"
                  type="button"
                  data-id="<?php echo (int)$student['id']; ?>"
                  data-student-no="<?php echo e($student['student_no']); ?>"
                  data-first-name="<?php echo e($student['first_name']); ?>"
                  data-last-name="<?php echo e($student['last_name']); ?>"
                  data-program="<?php echo e($student['program']); ?>"
                  data-year-level="<?php echo e($student['year_level']); ?>"
                  data-status="<?php echo e($student['status']); ?>"
                >Edit</button>
                <button
                  class="secondary btn-sm danger js-student-delete"
                  type="button"
                  data-id="<?php echo (int)$student['id']; ?>"
                  data-student-no="<?php echo e($student['student_no']); ?>"
                >Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php echo build_pagination(BASE_URL . '/staff/students.php', $pagination, ['q' => $search]); ?>
</section>

<script>
  (() => {
    const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    const escapeHtml = (value) =>
      String(value || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const openStudentModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    const createButton = document.querySelector('.js-student-create');
    if (createButton) {
      createButton.addEventListener('click', () => {
        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="student-create-form">
            <label>Student No<input name="student_no" type="text" required /></label>
            <label>First Name<input name="first_name" type="text" required /></label>
            <label>Last Name<input name="last_name" type="text" required /></label>
            <label>Program<input name="program" type="text" /></label>
            <label>Year Level<input name="year_level" type="text" /></label>
            <label>Status
              <select name="status">
                <option>Active</option>
                <option>Inactive</option>
                <option>On Hold</option>
              </select>
            </label>
          </form>
        `;

        openStudentModal(
          'Add Student',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#student-create-form');
            if (!form) return;
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'create');
              await window.RegistrarApi.post(`${BASE_URL}/api/students.php`, fd);
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
          'Create Student',
          'primary'
        );
      });
    }

    const editButtons = document.querySelectorAll('.js-student-edit');
    editButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const studentNo = btn.dataset.studentNo || '';
        const firstName = btn.dataset.firstName || '';
        const lastName = btn.dataset.lastName || '';
        const program = btn.dataset.program || '';
        const yearLevel = btn.dataset.yearLevel || '';
        const status = btn.dataset.status || 'Active';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="student-edit-form">
            <label>Student No<input name="student_no" type="text" required value="${escapeHtml(studentNo)}" /></label>
            <label>First Name<input name="first_name" type="text" required value="${escapeHtml(firstName)}" /></label>
            <label>Last Name<input name="last_name" type="text" required value="${escapeHtml(lastName)}" /></label>
            <label>Program<input name="program" type="text" value="${escapeHtml(program)}" /></label>
            <label>Year Level<input name="year_level" type="text" value="${escapeHtml(yearLevel)}" /></label>
            <label>Status
              <select name="status">
                <option ${status === 'Active' ? 'selected' : ''}>Active</option>
                <option ${status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                <option ${status === 'On Hold' ? 'selected' : ''}>On Hold</option>
              </select>
            </label>
          </form>
        `;

        openStudentModal(
          'Edit Student',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#student-edit-form');
            if (!form) return;
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'update');
              fd.set('id', id);
              await window.RegistrarApi.post(`${BASE_URL}/api/students.php`, fd);
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
          'Save Changes',
          'primary'
        );
      });
    });

    const deleteButtons = document.querySelectorAll('.js-student-delete');
    deleteButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const studentNo = btn.dataset.studentNo || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete student <strong>${escapeHtml(studentNo)}</strong>? This also removes related enrollments, grades, and document requests.</p>
        `;

        openStudentModal(
          'Delete Student',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/students.php`, { action: 'delete', id });
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


