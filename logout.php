<?php
require_once __DIR__ . '/core/auth.php';
cx_session_start();
audit('auth.logout', 'admin_users', current_user()['id'] ?? null);
cx_logout();
header('Location: login.php');
exit;
