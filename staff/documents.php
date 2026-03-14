<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

$students = $pdo->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$documents = $pdo->query('SELECT documents.id, documents.doc_type, documents.status, documents.requested_at, students.student_no, students.first_name, students.last_name FROM documents JOIN students ON documents.student_id = students.id ORDER BY documents.requested_at DESC')->fetchAll();

$pageTitle = 'Document Requests';
$activeNav = 'Document Requests';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Request Documents</h2>
      <p>Manage student requests and processing status.</p>
    </div>
    <div class="panel-actions">
      <button class="primary btn-sm js-document-create" type="button">New Request</button>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>Request Queue</h2>
      <p>Status updates for requested documents.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Document</th>
          <th>Status</th>
          <th>Requested</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$documents): ?>
          <tr><td colspan="5" class="empty">No document requests found.</td></tr>
        <?php endif; ?>
        <?php foreach ($documents as $doc): ?>
          <tr>
            <td><?php echo e($doc['student_no'] . ' - ' . $doc['last_name'] . ', ' . $doc['first_name']); ?></td>
            <td><?php echo e($doc['doc_type']); ?></td>
            <td><span class="status <?php echo status_class($doc['status']); ?>"><?php echo e($doc['status']); ?></span></td>
            <td><?php echo e($doc['requested_at']); ?></td>
            <td>
              <div class="btn-row">
              <form method="post" class="inline-form" action="<?php echo BASE_URL; ?>/api/documents.php">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="redirect" value="<?php echo BASE_URL; ?>/staff/documents.php" />
                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>" />
                <select name="status">
                  <option <?php echo $doc['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                  <option <?php echo $doc['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                  <option <?php echo $doc['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <button class="secondary btn-sm" type="submit">Update</button>
              </form>
              <button
                class="secondary btn-sm danger js-document-delete"
                type="button"
                data-id="<?php echo (int)$doc['id']; ?>"
                data-label="<?php echo e($doc['student_no'] . ' | ' . $doc['doc_type']); ?>"
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
    const escapeHtml = (value) =>
      String(value || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const openModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    const optionList = (items, labelFn) =>
      (items || []).map((item) => `<option value="${item.id}">${escapeHtml(labelFn(item))}</option>`).join('');

    const createButton = document.querySelector('.js-document-create');
    if (createButton) {
      createButton.addEventListener('click', () => {
        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="document-create-form">
            <label>Student
              <select name="student_id" required>
                <option value="">Select student</option>
                ${optionList(students, (s) => `${s.student_no} - ${s.last_name}, ${s.first_name}`)}
              </select>
            </label>
            <label>Document Type<input name="doc_type" type="text" placeholder="Transcript / Certification" required /></label>
          </form>
        `;

        openModal(
          'New Document Request',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#document-create-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'create');
              await window.RegistrarApi.post(`${BASE_URL}/api/documents.php`, fd);
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
          'Submit Request',
          'primary'
        );
      });
    }

    document.querySelectorAll('.js-document-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete document request <strong>${escapeHtml(label)}</strong>?</p>
        `;

        openModal(
          'Delete Document Request',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/documents.php`, { action: 'delete', doc_id: id });
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

