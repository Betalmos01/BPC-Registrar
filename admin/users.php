<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

$pdo = db();
$user = current_user();

$roles = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
$users = $pdo->query('SELECT users.id, users.role_id, users.username, users.first_name, users.last_name, users.is_active, roles.name AS role FROM users JOIN roles ON users.role_id = roles.id ORDER BY users.created_at DESC')->fetchAll();
$activeUsers = 0;
foreach ($users as $entry) {
    if ((int)$entry['is_active'] === 1) {
        $activeUsers++;
    }
}

$pageTitle = 'User Management';
$activeNav = 'User Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<section class="module-strip">
  <article class="module-card">
    <div class="module-label">System Accounts</div>
    <div class="module-value"><?php echo count($users); ?></div>
    <div class="module-note">Named users with access to the registrar platform.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Active Users</div>
    <div class="module-value"><?php echo $activeUsers; ?></div>
    <div class="module-note">Accounts currently enabled for registrar office operations.</div>
  </article>
  <article class="module-card">
    <div class="module-label">Role Templates</div>
    <div class="module-value"><?php echo count($roles); ?></div>
    <div class="module-note">Access groups available to support secure workflow segmentation.</div>
  </article>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>User Management</h2>
      <p>Create the right access structure first so every registrar action downstream stays attributable and secure.</p>
    </div>
    <div class="panel-actions">
      <button class="primary btn-sm js-user-create" type="button">Add User</button>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-header">
    <div>
      <h2>All Users</h2>
      <p>User access is the starting checkpoint of the registrar flow and determines who can touch each operational stage.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$users): ?>
          <tr>
            <td colspan="5" class="empty">No users found.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($users as $entry): ?>
          <tr>
            <td><?php echo e($entry['first_name'] . ' ' . $entry['last_name']); ?></td>
            <td><?php echo e($entry['username']); ?></td>
            <td><?php echo e($entry['role']); ?></td>
            <td>
              <span class="status <?php echo $entry['is_active'] ? 'active' : 'inactive'; ?>">
                <?php echo $entry['is_active'] ? 'Active' : 'Inactive'; ?>
              </span>
            </td>
            <td>
              <div class="btn-row">
                <button
                  class="secondary btn-sm js-user-edit"
                  type="button"
                  data-id="<?php echo (int)$entry['id']; ?>"
                  data-role-id="<?php echo (int)$entry['role_id']; ?>"
                  data-username="<?php echo e($entry['username']); ?>"
                  data-first-name="<?php echo e($entry['first_name']); ?>"
                  data-last-name="<?php echo e($entry['last_name']); ?>"
                >Edit</button>
                <?php if ((int)$entry['id'] !== (int)$user['id']): ?>
                  <button
                    class="secondary btn-sm js-user-status"
                    type="button"
                    data-id="<?php echo (int)$entry['id']; ?>"
                    data-is-active="<?php echo (int)$entry['is_active']; ?>"
                    data-label="<?php echo e($entry['username']); ?>"
                  ><?php echo $entry['is_active'] ? 'Disable' : 'Enable'; ?></button>
                  <button
                    class="secondary btn-sm js-user-reset"
                    type="button"
                    data-id="<?php echo (int)$entry['id']; ?>"
                    data-label="<?php echo e($entry['username']); ?>"
                  >Reset PW</button>
                  <button
                    class="secondary btn-sm danger js-user-delete"
                    type="button"
                    data-id="<?php echo (int)$entry['id']; ?>"
                    data-label="<?php echo e($entry['username']); ?>"
                  >Delete</button>
                <?php else: ?>
                  <span class="tag">You</span>
                <?php endif; ?>
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
    const roles = <?php echo json_encode($roles); ?>;
    const escapeHtml = (value) =>
      String(value || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const roleOptions = (selectedId) =>
      roles
        .map((r) => `<option value="${r.id}" ${String(r.id) === String(selectedId) ? 'selected' : ''}>${escapeHtml(r.name)}</option>`)
        .join('');

    const openModal = (title, body, onSubmit, submitText, submitClass) => {
      if (!window.RegistrarModal) return;
      window.RegistrarModal.open({ title, body, onSubmit, submitText, submitClass });
    };

    const createButton = document.querySelector('.js-user-create');
    if (createButton) {
      createButton.addEventListener('click', () => {
        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="user-create-form">
            <label>First Name<input name="first_name" type="text" required /></label>
            <label>Last Name<input name="last_name" type="text" required /></label>
            <label>Email / Username<input name="username" type="text" required /></label>
            <label>Role
              <select name="role_id" required>
                <option value="">Select role</option>
                ${roleOptions('')}
              </select>
            </label>
            <label>Password<input name="password" type="password" required /></label>
          </form>
        `;

        openModal(
          'Add User',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#user-create-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'create');
              await window.RegistrarApi.post(`${BASE_URL}/api/users.php`, fd);
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
          'Create User',
          'primary'
        );
      });
    }

    document.querySelectorAll('.js-user-edit').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const roleId = btn.dataset.roleId;
        const username = btn.dataset.username || '';
        const firstName = btn.dataset.firstName || '';
        const lastName = btn.dataset.lastName || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <form class="form-grid" id="user-edit-form">
            <label>First Name<input name="first_name" type="text" required value="${escapeHtml(firstName)}" /></label>
            <label>Last Name<input name="last_name" type="text" required value="${escapeHtml(lastName)}" /></label>
            <label>Email / Username<input name="username" type="text" required value="${escapeHtml(username)}" /></label>
            <label>Role
              <select name="role_id" required>
                <option value="">Select role</option>
                ${roleOptions(roleId)}
              </select>
            </label>
          </form>
        `;

        openModal(
          'Edit User',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#user-edit-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'update');
              fd.set('id', id);
              await window.RegistrarApi.post(`${BASE_URL}/api/users.php`, fd);
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

    document.querySelectorAll('.js-user-status').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const isActive = Number(btn.dataset.isActive || 0);
        const next = isActive ? 0 : 1;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Set <strong>${escapeHtml(label)}</strong> to <strong>${next ? 'Active' : 'Inactive'}</strong>?</p>
        `;

        openModal(
          next ? 'Enable User' : 'Disable User',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/users.php`, { action: 'toggle_active', id, is_active: next });
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
          next ? 'Enable' : 'Disable',
          next ? 'primary' : 'danger primary'
        );
      });
    });

    document.querySelectorAll('.js-user-reset').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0 0 10px;color:var(--muted);font-size:13px">Reset password for ${escapeHtml(label)}.</p>
          <form class="form-grid" id="user-reset-form">
            <label>New Password<input name="password" type="password" required /></label>
          </form>
        `;

        openModal(
          'Reset Password',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            const form = modal.querySelector('#user-reset-form');
            try {
              submit.disabled = true;
              const fd = new FormData(form);
              fd.set('action', 'reset_password');
              fd.set('id', id);
              await window.RegistrarApi.post(`${BASE_URL}/api/users.php`, fd);
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
          'Reset Password',
          'danger primary'
        );
      });
    });

    document.querySelectorAll('.js-user-delete').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const label = btn.dataset.label || '';

        const body = `
          <div class="modal-error" style="display:none"></div>
          <p style="margin:0">Delete user <strong>${escapeHtml(label)}</strong>? This cannot be undone.</p>
        `;

        openModal(
          'Delete User',
          body,
          async ({ modal, close, submit }) => {
            const errorBox = modal.querySelector('.modal-error');
            try {
              submit.disabled = true;
              await window.RegistrarApi.post(`${BASE_URL}/api/users.php`, { action: 'delete', id });
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
