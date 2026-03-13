<?php
require_once __DIR__ . '/../config/auth.php';

$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
$user = current_user();
$roleLabel = $user['role'] ?? 'Guest';
$initials = $user ? strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) : 'GU';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo e($pageTitle . ' | ' . APP_NAME); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/img/logo.png" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css" />
  <script>window.APP_BASE_URL = <?php echo json_encode(BASE_URL); ?>;</script>
</head>
<body>
  <div class="app">
    <div class="sidebar-overlay" data-sidebar-close></div>
