<?php
require_once __DIR__ . '/../config/auth.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/settings.php');
    exit;
}

$pdo = db();
require_once __DIR__ . '/../database/seed_demo.php';

try {
    $counts = seed_demo_data($pdo);
    $parts = [];
    foreach ($counts as $key => $value) {
        if ((int)$value > 0) {
            $parts[] = $key . ': ' . (int)$value;
        }
    }
    $message = $parts ? 'Demo data seeded (' . implode(', ', $parts) . ').' : 'Demo data already present. No changes made.';
    set_flash($message, 'success');
} catch (Throwable $e) {
    set_flash('Unable to seed demo data.', 'error');
}

header('Location: ' . BASE_URL . '/admin/settings.php');
exit;

