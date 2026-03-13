<?php
require_once __DIR__ . '/config/auth.php';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Access Denied - <?php echo e(APP_NAME); ?></title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css" />
</head>
<body class="auth">
  <div class="auth-card">
    <h1>Access Denied</h1>
    <p class="muted">You do not have permission to view this module.</p>
    <a class="primary" href="<?php echo BASE_URL; ?>/index.php">Return to Login</a>
  </div>
</body>
</html>
