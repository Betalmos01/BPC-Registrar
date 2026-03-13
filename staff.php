<?php
require_once __DIR__ . '/config/auth.php';
require_role('Registrar Staff');
header('Location: ' . BASE_URL . '/staff/dashboard.php');
exit;
