<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();
$user = current_user();

$reports = $pdo->query('SELECT * FROM academic_reports ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Academic Reports';
$activeNav = 'Academic Reports';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Academic Reports</h2>
      <p>Generate academic performance and transcript reports.</p>
    </div>
    <div class="panel-actions">
      <button class="primary btn-sm js-acr-create" type="button">Create Report</button>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Academic Report Log</h2>
      <p>Monitor academic report submissions.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Coverage</th>
          <th>Status</th>
          <th>Created</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reports): ?>
          <tr><td colspan="5" class="empty">No academic reports found.</td></tr>
        <?php endif; ?>
        <?php foreach ($reports as $report): ?>
          <tr>
            <td><?php echo e($report['title']); ?></td>
            <td><?php echo e($report['coverage']); ?></td>
            <td><span class="status <?php echo status_class($report['status']); ?>"><?php echo e($report['status']); ?></span></td>
            <td><?php echo e($report['created_at']); ?></td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-acr-edit"
                  type="button"
                  data-id="<?php echo (int)$report['id']; ?>"
                  data-title="<?php echo e($report['title']); ?>"
                  data-coverage="<?php echo e($report['coverage']); ?>"
                  data-status="<?php echo e($report['status']); ?>"
                >Edit</button>
                <button
                  class="secondary btn-sm danger js-acr-delete"
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

    const statuses = ['Draft', 'In Review', 'Released'];
    const statusOptions = (selected) =>
      statuses.map((s) => `<option ${String(s) === String(selected) ? 'selected' : ''}>${escapeHtml(s)}</option>`).join('');

    const openModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    const createButton = document.querySelector('.js-acr-create');
    if (createButton) {
      createButton.addEventListener('click', () => {
        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="acr-create-form">
            <label>Report Title<input name="title" type="text" required /></label>
            <label>Coverage<input name="coverage" type="text" placeholder="AY 2025-2026" required /></label>
            <label>Status<select name="status">${statusOptions('Draft')}</select></label>
          </form>
        `;

        openModal(
          'Create Academic Report',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#acr-create-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'create');
              await window.RegistrarApi.post(`${BASE_URL}/api/academic_reports.php`, fd);
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

    document.querySelectorAll('.js-acr-edit').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const title = btn.dataset.title || '';
        const coverage = btn.dataset.coverage || '';
        const status = btn.dataset.status || 'Draft';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="acr-edit-form">
            <label>Report Title<input name="title" type="text" required value="${escapeHtml(title)}" /></label>
            <label>Coverage<input name="coverage" type="text" required value="${escapeHtml(coverage)}" /></label>
            <label>Status<select name="status">${statusOptions(status)}</select></label>
          </form>
        `;

        openModal(
          'Edit Academic Report',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#acr-edit-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'update');
              fd.set('id', id);
              await window.RegistrarApi.post(`${BASE_URL}/api/academic_reports.php`, fd);
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

    document.querySelectorAll('.js-acr-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete academic report <strong>${escapeHtml(label)}</strong>?</p>
        `;

        openModal(
          'Delete Academic Report',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/academic_reports.php`, { action: 'delete', id });
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

