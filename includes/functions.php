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

function registrar_workflow(bool $isAdmin = false): array
{
    $shared = [
        [
            'label' => 'Student Intake',
            'href' => BASE_URL . '/staff/students.php',
            'match' => ['Student Management'],
            'summary' => 'Capture applicant and student master records before any downstream registrar transaction.',
        ],
        [
            'label' => 'Class Planning',
            'href' => BASE_URL . '/staff/classes.php',
            'match' => ['Manage Classes & Schedules', 'Instructor Management', 'Class Schedules'],
            'summary' => 'Prepare course sections, schedules, rooms, and academic load availability.',
        ],
        [
            'label' => 'Enrollment Validation',
            'href' => BASE_URL . '/staff/enrollments.php',
            'match' => ['Enrollment', 'Class Lists', 'Class List View'],
            'summary' => 'Assign students to available sections and confirm the official registration status.',
        ],
        [
            'label' => 'Grade Posting',
            'href' => BASE_URL . '/staff/grades.php',
            'match' => ['Grade Management'],
            'summary' => 'Record final grades and preserve academic standing for release and reporting.',
        ],
    ];

    if ($isAdmin) {
        array_unshift($shared, [
            'label' => 'Access Setup',
            'href' => BASE_URL . '/admin/users.php',
            'match' => ['User Management', 'Staff Directory'],
            'summary' => 'Control user access, roles, and operating permissions across the registrar office.',
        ]);

        $shared[] = [
            'label' => 'Compliance & Reports',
            'href' => BASE_URL . '/admin/academic_reports.php',
            'match' => ['Academic Reports', 'System Activity', 'System Settings'],
            'summary' => 'Audit transactions, configure policies, and generate institutional reports.',
        ];
    } else {
        $shared[] = [
            'label' => 'Completion Services',
            'href' => BASE_URL . '/staff/documents.php',
            'match' => ['Document Requests', 'Class Lists'],
            'summary' => 'Support list release, transcript requests, and other registrar completion services.',
        ];
    }

    return $shared;
}

function registrar_page_context(string $activeNav, bool $isAdmin = false): array
{
    $workflow = registrar_workflow($isAdmin);
    $currentIndex = 0;
    $matched = false;

    foreach ($workflow as $index => $step) {
        if (in_array($activeNav, $step['match'], true)) {
            $currentIndex = $index;
            $matched = true;
            break;
        }
    }

    $pageMap = [
        'Dashboard' => [
            'eyebrow' => $isAdmin ? 'Registrar Command Center' : 'Registrar Operations Hub',
            'title' => $isAdmin ? 'Oversee access, compliance, and office-wide throughput.' : 'Move students from intake to enrollment with fewer handoff gaps.',
            'description' => $isAdmin
                ? 'Track role provisioning, academic activity, and reporting deadlines from one operating view.'
                : 'Monitor student records, class setup, enrollment activity, and grade release in one registrar flow.',
        ],
        'User Management' => [
            'eyebrow' => 'Access Setup',
            'title' => 'Provision secure accounts for administrators and registrar staff.',
            'description' => 'Start the flow by assigning the right people, roles, and permissions before records are processed.',
        ],
        'Student Management' => [
            'eyebrow' => 'Student Intake',
            'title' => 'Maintain the official source of truth for student identity and eligibility.',
            'description' => 'Student records should be complete here first so class assignment, enrollment, and grade release stay accurate.',
        ],
        'Manage Classes & Schedules' => [
            'eyebrow' => 'Class Planning',
            'title' => 'Build section offerings that students can actually enroll into.',
            'description' => 'Define subjects, schedules, rooms, and load ahead of enrollment confirmation to prevent registrar bottlenecks.',
        ],
        'Enrollment' => [
            'eyebrow' => 'Enrollment Validation',
            'title' => 'Confirm section assignments and finalize registration status.',
            'description' => 'This is the operational checkpoint between student readiness and officially enrolled class loads.',
        ],
        'Grade Management' => [
            'eyebrow' => 'Grade Posting',
            'title' => 'Capture completion results for transcript, retention, and reporting use.',
            'description' => 'Academic outcomes close the loop and feed the registrar\'s release, evaluation, and compliance work.',
        ],
        'Document Requests' => [
            'eyebrow' => 'Completion Services',
            'title' => 'Release registrar documents only after record status is complete and verified.',
            'description' => 'Use this stage to fulfill transcript, certification, and completion-related requests safely.',
        ],
        'Academic Reports' => [
            'eyebrow' => 'Compliance & Reports',
            'title' => 'Turn registrar transactions into audit-ready school reporting.',
            'description' => 'Summaries here should reflect the whole lifecycle from intake to completion and institutional review.',
        ],
        'System Activity' => [
            'eyebrow' => 'Compliance & Reports',
            'title' => 'Review the audit trail behind registrar actions and approvals.',
            'description' => 'Activity logs validate who changed what across every operational module.',
        ],
        'System Settings' => [
            'eyebrow' => 'Compliance & Reports',
            'title' => 'Standardize registrar rules, defaults, and operating policy.',
            'description' => 'Settings help keep the process predictable across admissions, enrollment, grading, and release services.',
        ],
        'Instructor Management' => [
            'eyebrow' => 'Class Planning',
            'title' => 'Maintain verified instructor identity records for scheduling and grade posting.',
            'description' => 'Faculty profiles should be accurate here so class lists and grade releases remain attributable and audit-ready.',
        ],
        'Class Lists' => [
            'eyebrow' => 'Enrollment Validation',
            'title' => 'Turn validated enrollments into class rosters ready for grading and release.',
            'description' => 'Class lists reflect official student load. Use them as the handoff into grade posting and completion workflows.',
        ],
        'Class List View' => [
            'eyebrow' => 'Enrollment Validation',
            'title' => 'Review the official class roster and prepare students for grade posting.',
            'description' => 'This roster is generated from validated enrollments. Record grades only for officially enrolled students.',
        ],
        'Class Schedules' => [
            'eyebrow' => 'Class Planning',
            'title' => 'Review published schedules that support enrollment and registrar confirmation.',
            'description' => 'Schedules should be consistent with class offerings, room assignments, and registrar planning rules.',
        ],
    ];

    $default = [
        'eyebrow' => 'Registrar Workspace',
        'title' => 'Manage registrar operations in a connected end-to-end flow.',
        'description' => 'Each module should hand off cleanly to the next stage so records remain accurate from intake to completion.',
    ];

    $page = $pageMap[$activeNav] ?? $default;

    if (!$matched) {
        $eyebrow = strtolower((string)($page['eyebrow'] ?? ''));
        foreach ($workflow as $index => $step) {
            if (strtolower((string)($step['label'] ?? '')) === $eyebrow) {
                $currentIndex = $index;
                break;
            }
        }
    }
    $nextStep = $workflow[min($currentIndex + 1, count($workflow) - 1)];
    $prevStep = $workflow[max($currentIndex - 1, 0)];

    return [
        'page' => $page,
        'workflow' => $workflow,
        'currentIndex' => $currentIndex,
        'nextStep' => $nextStep,
        'prevStep' => $prevStep,
    ];
}

