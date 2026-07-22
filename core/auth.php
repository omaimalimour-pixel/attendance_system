<?php
/**
 * ChronoX — Authentication, RBAC, audit, settings.
 */

require_once __DIR__ . '/security.php';

/* ---------------- Roles & permissions ---------------- */

const CX_ROLES = ['Administrator', 'Manager', 'Viewer'];

/**
 * Permission matrix. Managers can operate day-to-day; Viewers are read-only;
 * Administrators can do everything including managing users & devices.
 */
function cx_permissions(): array
{
    return [
        'Administrator' => ['*'],
        'Manager'       => ['view', 'employees.manage', 'attendance.manage', 'devices.sync', 'export'],
        'Viewer'        => ['view', 'export'],
    ];
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'name'     => $_SESSION['name'] ?? ($_SESSION['username'] ?? ''),
        'role'     => $_SESSION['role'] ?? 'Viewer',
    ];
}

function can(string $perm): bool
{
    $u = current_user();
    if (!$u) return false;
    $perms = cx_permissions()[$u['role']] ?? [];
    return in_array('*', $perms, true) || in_array($perm, $perms, true);
}

/** Block the request (403) unless the current user has the permission. */
function require_perm(string $perm): void
{
    if (!can($perm)) {
        http_response_code(403);
        die('<div style="font-family:Inter,system-ui,sans-serif;max-width:520px;margin:80px auto;padding:32px;border-radius:16px;background:#fff;border:1px solid #FDE68A;color:#92400E;text-align:center;">
            <h2 style="margin:0 0 10px;">Access denied</h2>
            <p style="margin:0;color:#78350F;">Your role does not have permission to perform this action.</p>
            <p style="margin:16px 0 0;"><a href="dashboard.php" style="color:#5B54E8;">← Back to dashboard</a></p></div>');
    }
}

/**
 * Page guard. Include at the top of protected pages.
 * Redirects to login if unauthenticated; to install if the app isn't set up.
 */
function require_login(): void
{
    cx_session_start();

    if (isset($_SESSION['user_id'])) {
        return;
    }

    // Not installed yet? Send to installer instead of looping to login.
    if (!db_table_exists('admin_users')) {
        header('Location: ' . cx_base() . '/install.php');
        exit;
    }

    header('Location: ' . cx_base() . '/login.php');
    exit;
}

/** Best-effort base URL path (handles subfolder installs like /attendance_system). */
function cx_base(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If we're inside /dashboard, the app root is one level up.
    if (preg_match('#/dashboard$#', $dir)) {
        $dir = preg_replace('#/dashboard$#', '', $dir);
    }
    return rtrim($dir, '/');
}

/* ---------------- Audit log ---------------- */

function audit(string $action, string $entity = '', $entityId = null): void
{
    if (!db_table_exists('audit_logs')) return;
    $u = current_user();
    db_exec(
        "INSERT INTO audit_logs (user_id, username, action, entity, entity_id, ip, created_at)
         VALUES (?,?,?,?,?,?,NOW())",
        [
            $u['id'] ?? null,
            $u['username'] ?? 'system',
            $action,
            $entity,
            $entityId !== null ? (string) $entityId : null,
            $_SERVER['REMOTE_ADDR'] ?? '',
        ]
    );
}

/* ---------------- Settings (key/value) ---------------- */

function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        if (db_table_exists('settings')) {
            foreach (db_all("SELECT `key`,`value` FROM settings") as $r) {
                $cache[$r['key']] = $r['value'];
            }
        }
    }
    return $cache[$key] ?? $default;
}

/** Work-start threshold used to flag "late" arrivals. */
function work_start(): string
{
    return setting('work_start', cx_config()['work_start']);
}
