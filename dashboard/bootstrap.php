<?php
/**
 * Dashboard bootstrap — load the core, enforce auth, expose current page.
 * Include this at the very top of every dashboard page:
 *   require __DIR__ . '/bootstrap.php';
 */
require_once __DIR__ . '/../core/auth.php';

$cfg = cx_config();
date_default_timezone_set($cfg['timezone']);

if ($cfg['app_env'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

require_login();
