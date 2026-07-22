<?php
/**
 * Backward-compatibility shim.
 * The real auth/session/RBAC logic now lives in /core/auth.php.
 * Any page that includes this (directly or via header.php) is protected.
 */
require_once __DIR__ . '/../../core/auth.php';
require_login();
