<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Registrar Staff');

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $docType = trim($_POST['doc_type'] ?? '');
        if ($studentId && $docType) {
            $stmt = $pdo->prepare('INSERT INTO documents (student_id, doc_type, status, requested_at) VALUES (:student_id, :doc_type, :status, NOW())');
            $stmt->execute([
                'student_id' => $studentId,
                'doc_type' => $docType,
                'status' => 'Pending',
            ]);
            log_action((int)$user['id'], 'Create', 'Document Requests', 'Requested ' . $docType);
            set_flash('Document request submitted.');
        } else {
            set_flash('Please select student and document type.', 'error');
        }
    }

    if ($action === 'update') {
        $docId = (int)($_POST['doc_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if ($docId && $status) {
            $stmt = $pdo->prepare('UPDATE documents SET status = :status, completed_at = IF(:status = "Completed", NOW(), completed_at) WHERE id = :id');
            $stmt->execute([
                'status' => $status,
                'id' => $docId,
            ]);
            log_action((int)$user['id'], 'Update', 'Document Requests', 'Updated document request ' . $docId);
            set_flash('Document request updated.');
        }
    }

    header('Location: ' . BASE_URL . '/staff/documents.php');
    exit;
}

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
  </div>

  <form class="form-grid" method="post">
    <input type="hidden" name="action" value="create" />
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
      Document Type
      <input type="text" name="doc_type" placeholder="Transcript / Certification" required />
    </label>
    <button class="primary" type="submit">Submit Request</button>
  </form>
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
          <th>Update</th>
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
              <form method="post" class="inline-form">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>" />
                <select name="status">
                  <option <?php echo $doc['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                  <option <?php echo $doc['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                  <option <?php echo $doc['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <button class="secondary" type="submit">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

