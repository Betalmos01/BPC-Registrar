<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();
$user = current_user();

$reports = $pdo->query('SELECT * FROM reports ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Reports';
$activeNav = 'Reports';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Generate Reports</h2>
      <p>System and academic report compilation.</p>
    </div>
    <div class="panel-actions">
      <button class="primary btn-sm js-report-create" type="button">Create Report</button>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Report Queue</h2>
      <p>Track report progress and approvals.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Department</th>
          <th>Status</th>
          <th>Due Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reports): ?>
          <tr><td colspan="5" class="empty">No reports created.</td></tr>
        <?php endif; ?>
        <?php foreach ($reports as $report): ?>
          <tr>
            <td><?php echo e($report['title']); ?></td>
            <td><?php echo e($report['department']); ?></td>
            <td><span class="status <?php echo status_class($report['status']); ?>"><?php echo e($report['status']); ?></span></td>
            <td><?php echo e($report['due_date']); ?></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-report-edit"
                  type="button"
                  data-id="<?php echo (int)$report['id']; ?>"
                  data-title="<?php echo e($report['title']); ?>"
                  data-department="<?php echo e($report['department']); ?>"
                  data-status="<?php echo e($report['status']); ?>"
                  data-due-date="<?php echo e((string)$report['due_date']); ?>"
                >Edit</button>
                <button
                  class="secondary btn-sm danger js-report-delete"
                  type="button"
                  data-id="<?php echo (int)$report['id']; ?>"
                  data-label="<?php echo e($report['title']); ?>"
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

    const statuses = ['Pending', 'In Review', 'Completed'];
    const statusOptions = (selected) =>
      statuses.map((s) => `<option ${String(s) === String(selected) ? 'selected' : ''}>${escapeHtml(s)}</option>`).join('');

    const openModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    const createButton = document.querySelector('.js-report-create');
    if (createButton) {
      createButton.addEventListener('click', () => {
        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="report-create-form">
            <label>Report Title<input name="title" type="text" required /></label>
            <label>Department<input name="department" type="text" required /></label>
            <label>Status<select name="status">${statusOptions('Pending')}</select></label>
            <label>Due Date<input name="due_date" type="date" /></label>
          </form>
        `;

        openModal(
          'Create Report',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#report-create-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'create');
              await window.RegistrarApi.post(`${BASE_URL}/api/reports.php`, fd);
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
          'Create Report',
          'primary'
        );
      });
    }

    document.querySelectorAll('.js-report-edit').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const title = btn.dataset.title || '';
        const department = btn.dataset.department || '';
        const status = btn.dataset.status || 'Pending';
        const dueDate = btn.dataset.dueDate || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="report-edit-form">
            <label>Report Title<input name="title" type="text" required value="${escapeHtml(title)}" /></label>
            <label>Department<input name="department" type="text" required value="${escapeHtml(department)}" /></label>
            <label>Status<select name="status">${statusOptions(status)}</select></label>
            <label>Due Date<input name="due_date" type="date" value="${escapeHtml(dueDate)}" /></label>
          </form>
        `;

        openModal(
          'Edit Report',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#report-edit-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'update');
              fd.set('id', id);
              await window.RegistrarApi.post(`${BASE_URL}/api/reports.php`, fd);
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

    document.querySelectorAll('.js-report-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete report <strong>${escapeHtml(label)}</strong>?</p>
        `;

        openModal(
          'Delete Report',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/reports.php`, { action: 'delete', id });
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

