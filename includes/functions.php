<?php
require_once __DIR__ . '/../config/config.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function request_value(string $key, $default = '')
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function paginate(int $total, int $page, int $perPage): array
{
    $totalPages = (int)ceil($total / $perPage);
    $page = max(1, min($page, max(1, $totalPages)));
    $offset = ($page - 1) * $perPage;

    return [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'offset' => $offset,
    ];
}

function build_pagination(string $baseUrl, array $pagination, array $params = []): string
{
    if ($pagination['totalPages'] <= 1) {
        return '';
    }

    $html = '<div class="pagination">';
    for ($i = 1; $i <= $pagination['totalPages']; $i++) {
        $params['page'] = $i;
        $query = http_build_query($params);
        $active = $i === $pagination['page'] ? 'active' : '';
        $html .= '<a class="page ' . $active . '" href="' . $baseUrl . '?' . $query . '">' . $i . '</a>';
    }
    $html .= '</div>';
    return $html;
}

function log_action(int $userId, string $action, string $module, string $details = ''): void
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, module, details, created_at) VALUES (:user_id, :action, :module, :details, NOW())');
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'details' => $details,
        ]);
    } catch (Throwable $e) {
        // Avoid breaking UX if audit logging fails.
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function status_class(string $status): string
{
    $normalized = strtolower(trim($status));
    $normalized = preg_replace('/\s+/', '-', $normalized);
    return $normalized ?: 'default';
}
