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
    $where = 'WHERE employee_no LIKE :q OR first_name LIKE :q OR last_name LIKE :q OR department LIKE :q';
    $params['q'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM instructors $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, PER_PAGE);
$params['limit'] = $pagination['perPage'];
$params['offset'] = $pagination['offset'];

$listStmt = $pdo->prepare("SELECT * FROM instructors $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $type = in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $listStmt->bindValue($key === 'q' ? ':q' : ':' . $key, $value, $type);
}
$listStmt->execute();
$instructors = $listStmt->fetchAll();

$deptCount = (int)$pdo->query("SELECT COUNT(DISTINCT department) FROM instructors WHERE department <> ''")->fetchColumn();
$recentCount = (int)$pdo->query("SELECT COUNT(*) FROM instructors WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

$pageTitle = 'Instructor Management';
$activeNav = 'Instructor Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">Faculty Records</div>
    <div class="module-value"><?php echo (int)$total; ?></div>
    <div class="module-note">Instructor profiles registered in the registrar system.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Departments</div>
    <div class="module-value"><?php echo (int)$deptCount; ?></div>
    <div class="module-note">Distinct departments currently represented.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Recently Added</div>
    <div class="module-value"><?php echo (int)$recentCount; ?></div>
    <div class="module-note">New instructor records added in the last 30 days.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Faculty / Instructor Management</h2>
      <p>Maintain instructor identity records so class lists, schedules, and grade posting stay attributable and auditable.</p>
    </div>
    <div class="panel-actions">
      <button class="primary btn-sm js-inst-create" type="button">Add Instructor</button>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Faculty Directory</h2>
      <p>Search and update instructor records used for class planning and grade release.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Employee No</th>
          <th>Name</th>
          <th>Department</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$instructors): ?>
          <tr><td colspan="4" class="empty">No instructor records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($instructors as $inst): ?>
          <tr>
            <td><?php echo e($inst['employee_no']); ?></td>
            <td><?php echo e($inst['first_name'] . ' ' . $inst['last_name']); ?></td>
            <td><?php echo e($inst['department']); ?></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-inst-edit"
                  type="button"
                  data-id="<?php echo (int)$inst['id']; ?>"
                  data-employee-no="<?php echo e($inst['employee_no']); ?>"
                  data-first-name="<?php echo e($inst['first_name']); ?>"
                  data-last-name="<?php echo e($inst['last_name']); ?>"
                  data-department="<?php echo e($inst['department']); ?>"
                >Edit</button>
                <button
                  class="secondary btn-sm danger js-inst-delete"
                  type="button"
                  data-id="<?php echo (int)$inst['id']; ?>"
                  data-label="<?php echo e($inst['employee_no']); ?>"
                >Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php echo build_pagination(BASE_URL . '/staff/instructors.php', $pagination, ['q' => $search]); ?>
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

    const createButton = document.querySelector('.js-inst-create');
    if (createButton) {
      createButton.addEventListener('click', () => {
        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="inst-create-form">
            <label>Employee No<input name="employee_no" type="text" required /></label>
            <label>First Name<input name="first_name" type="text" required /></label>
            <label>Last Name<input name="last_name" type="text" required /></label>
            <label>Department<input name="department" type="text" placeholder="Computer Studies" /></label>
          </form>
        `;

        openModal(
          'Add Instructor',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#inst-create-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'create');
              await window.RegistrarApi.post(`${BASE_URL}/api/instructors.php`, fd);
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
          'Create Instructor',
          'primary'
        );
      });
    }

    document.querySelectorAll('.js-inst-edit').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const employeeNo = btn.dataset.employeeNo || '';
        const firstName = btn.dataset.firstName || '';
        const lastName = btn.dataset.lastName || '';
        const department = btn.dataset.department || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="inst-edit-form">
            <label>Employee No<input name="employee_no" type="text" required value="${escapeHtml(employeeNo)}" /></label>
            <label>First Name<input name="first_name" type="text" required value="${escapeHtml(firstName)}" /></label>
            <label>Last Name<input name="last_name" type="text" required value="${escapeHtml(lastName)}" /></label>
            <label>Department<input name="department" type="text" value="${escapeHtml(department)}" /></label>
          </form>
        `;

        openModal(
          'Edit Instructor',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#inst-edit-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'update');
              fd.set('id', id);
              await window.RegistrarApi.post(`${BASE_URL}/api/instructors.php`, fd);
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

    document.querySelectorAll('.js-inst-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete instructor <strong>${escapeHtml(label)}</strong>?</p>
        `;

        openModal(
          'Delete Instructor',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/instructors.php`, { action: 'delete', id });
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
