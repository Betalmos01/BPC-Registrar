<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$user = api_require_role('Registrar Staff');
$pdo = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = api_input();
$action = strtolower(trim($input['action'] ?? ($method === 'GET' ? 'list' : '')));

if ($method === 'GET' && api_wants_json()) {
    $documents = $pdo->query('SELECT documents.id, documents.student_id, documents.doc_type, documents.status, documents.requested_at, documents.completed_at FROM documents ORDER BY documents.requested_at DESC')->fetchAll();
    api_json(['ok' => true, 'data' => ['documents' => $documents]], 200);
}

if ($method !== 'POST') {
    api_error('Unsupported method.', 405, BASE_URL . '/staff/documents.php');
}

if ($action === 'create') {
    $studentId = (int)($input['student_id'] ?? 0);
    $docType = trim($input['doc_type'] ?? '');
    if ($studentId <= 0 || !$docType) {
        api_error('Please select student and document type.', 422, BASE_URL . '/staff/documents.php');
    }

    $stmt = $pdo->prepare('INSERT INTO documents (student_id, doc_type, status, requested_at) VALUES (:student_id, :doc_type, :status, NOW())');
    $stmt->execute([
        'student_id' => $studentId,
        'doc_type' => $docType,
        'status' => 'Pending',
    ]);

    try {
        $note = $pdo->prepare('INSERT INTO notifications (title, message, status, created_at) VALUES (:title, :message, :status, NOW())');
        $note->execute([
            'title' => 'New Document Request',
            'message' => 'A new document request was filed: ' . $docType . '.',
            'status' => 'Unread',
        ]);
    } catch (Throwable $e) {
        // Non-blocking.
    }

    log_action((int)$user['id'], 'Create', 'Document Requests', 'Requested ' . $docType);
    api_success('Document request submitted.', ['id' => (int)$pdo->lastInsertId()], BASE_URL . '/staff/documents.php');
}

if ($action === 'update') {
    $docId = (int)($input['doc_id'] ?? 0);
    $status = trim($input['status'] ?? '');
    if ($docId <= 0 || !$status) {
        api_error('Missing document id or status.', 422, BASE_URL . '/staff/documents.php');
    }

    $stmt = $pdo->prepare('UPDATE documents SET status = :status, completed_at = IF(:status = "Completed", NOW(), completed_at) WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'id' => $docId,
    ]);

    if (strcasecmp($status, 'Completed') === 0) {
        try {
            $note = $pdo->prepare('INSERT INTO notifications (title, message, status, created_at) VALUES (:title, :message, :status, NOW())');
            $note->execute([
                'title' => 'Document Completed',
                'message' => 'A document request was marked as Completed (ID ' . $docId . ').',
                'status' => 'Unread',
            ]);
        } catch (Throwable $e) {
            // Non-blocking.
        }
    }

    log_action((int)$user['id'], 'Update', 'Document Requests', 'Updated document request ' . $docId);
    api_success('Document request updated.', ['id' => $docId], BASE_URL . '/staff/documents.php');
}

if ($action === 'delete') {
    $docId = (int)($input['doc_id'] ?? 0);
    if ($docId <= 0) {
        api_error('Missing document id.', 422, BASE_URL . '/staff/documents.php');
    }

    $stmt = $pdo->prepare('DELETE FROM documents WHERE id = :id');
    $stmt->execute(['id' => $docId]);

    log_action((int)$user['id'], 'Delete', 'Document Requests', 'Deleted document request ' . $docId);
    api_success('Document request deleted.', ['id' => $docId], BASE_URL . '/staff/documents.php');
}

api_error('Unknown action.', 400, BASE_URL . '/staff/documents.php');
