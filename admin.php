<?php
require_once __DIR__ . '/config/auth.php';
require_role('Administrator');
header('Location: ' . BASE_URL . '/admin/dashboard.php');
exit;
