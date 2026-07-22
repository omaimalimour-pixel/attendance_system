<?php
/**
 * Backward-compatibility shim — included by header.php.
 * Bootstrap.php already called require_login() so we only need
 * to ensure the core helpers are loaded here.
 */
require_once __DIR__ . '/../../core/auth.php';
// Only enforce login if not already authenticated (prevents double-call with bootstrap.php)
if (!isset($_SESSION['user_id'])) {
    require_login();
}
