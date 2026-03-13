<?php
require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    $user = current_user();
    $role = strtolower($user['role'] ?? '');
    if ($role === 'administrator') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }
    header('Location: ' . BASE_URL . '/staff/dashboard.php');
    exit;
}

$pageTitle = APP_NAME . ' Login';
$error = $_GET['error'] ?? '';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo e($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;600;700&family=Manrope:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css" />
</head>
<body class="auth">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="brand-logo">
        <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="Bestlink College" />
      </div>
      <div>
        <div class="brand-title">Bestlink College</div>
        <div class="brand-sub">Registrar Management System</div>
      </div>
    </div>

    <h1>Admin &amp; Staff Login</h1>
    <p class="muted">Use your assigned account to continue.</p>
    <div class="auth-roles">
      <span class="role-pill">Administrator</span>
      <span class="role-pill">Registrar Staff</span>
    </div>

    <?php if ($error === '1'): ?>
      <div class="alert error">Invalid username or password.</div>
    <?php elseif ($error === 'login'): ?>
      <div class="alert error">Please sign in to continue.</div>
    <?php endif; ?>

    <form class="auth-form" method="post" action="<?php echo BASE_URL; ?>/auth/login.php">
      <label>
        Email or Username
        <div class="input-group">
          <span class="input-icon user" aria-hidden="true"></span>
          <input type="text" name="username" autocomplete="username" required />
        </div>
      </label>
      <label>
        Password
        <div class="input-group">
          <span class="input-icon lock" aria-hidden="true"></span>
          <input type="password" name="password" autocomplete="current-password" required />
          <button class="toggle-password" type="button" aria-label="Show password"></button>
        </div>
      </label>
      <button class="primary" type="submit">Sign in</button>
    </form>

    <div class="auth-foot">
      <div>Need access? Contact the registrar administrator.</div>
    </div>
  </div>

  <script>
    const toggleBtn = document.querySelector('.toggle-password');
    const passInput = document.querySelector('input[name="password"]');
    if (toggleBtn && passInput) {
      toggleBtn.addEventListener('click', () => {
        const isHidden = passInput.getAttribute('type') === 'password';
        passInput.setAttribute('type', isHidden ? 'text' : 'password');
        toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        toggleBtn.classList.toggle('active', isHidden);
      });
    }
  </script>
</body>
</html>
