<?php
/**
 * ChronoX — Security helpers
 * Hardened sessions, CSRF protection, input sanitization, login throttling.
 */

require_once __DIR__ . '/db.php';

/** Start a hardened session (idempotent). */
function cx_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $cfg = cx_config();
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);

    session_name($cfg['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Idle timeout
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $cfg['session_idle_secs']) {
        cx_logout();
    }
    $_SESSION['last_activity'] = $now;

    // Bind session to a lightweight fingerprint to blunt hijacking
    $fp = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|chronox');
    if (!isset($_SESSION['fp'])) {
        $_SESSION['fp'] = $fp;
    } elseif (!hash_equals($_SESSION['fp'], $fp)) {
        cx_logout();
    }
}

/** Destroy the session completely. */
function cx_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Hidden input to drop inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/** Verify a submitted token; kills the request on failure. */
function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(419);
        die('Security check failed (invalid CSRF token). Please go back and try again.');
    }
}

/* ---------------- Input ---------------- */

/** HTML-escape for safe output. */
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Trimmed string from an input array. */
function inp(array $src, string $key, string $default = ''): string
{
    return isset($src[$key]) ? trim((string) $src[$key]) : $default;
}

/* ---------------- Login throttle ---------------- */

function login_key(string $username): string
{
    return 'lock_' . md5(strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function login_is_locked(string $username): bool
{
    $cfg = cx_config();
    $k = login_key($username);
    $rec = $_SESSION[$k] ?? null;
    if (!$rec) return false;
    if ($rec['count'] >= $cfg['login_max_attempts'] && (time() - $rec['last']) < $cfg['login_lockout_secs']) {
        return true;
    }
    if ((time() - $rec['last']) >= $cfg['login_lockout_secs']) {
        unset($_SESSION[$k]); // window expired
    }
    return false;
}

function login_record_fail(string $username): void
{
    $k = login_key($username);
    $rec = $_SESSION[$k] ?? ['count' => 0, 'last' => 0];
    $rec['count']++;
    $rec['last'] = time();
    $_SESSION[$k] = $rec;
}

function login_reset(string $username): void
{
    unset($_SESSION[login_key($username)]);
}
