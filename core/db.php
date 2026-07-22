<?php
/**
 * ChronoX — Database layer (mysqli + prepared-statement helpers)
 * All queries MUST go through these helpers with bound parameters.
 * Never interpolate user input into SQL again.
 */

/** Return the loaded configuration array (cached). */
function cx_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg;
}

/** Lazily open (once) and return the shared mysqli connection. */
function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $cfg = cx_config();
    mysqli_report(MYSQLI_REPORT_OFF); // we handle errors ourselves

    $conn = @mysqli_connect(
        $cfg['db_host'],
        $cfg['db_user'],
        $cfg['db_pass'],
        $cfg['db_name'],
        $cfg['db_port']
    );

    if (!$conn) {
        cx_db_fatal(mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

/** Prepare + bind + execute. Returns mysqli_result|bool. */
function db_run(string $sql, array $params = [])
{
    $stmt = mysqli_prepare(db(), $sql);
    if (!$stmt) {
        return false;
    }
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }
    $res = mysqli_stmt_get_result($stmt); // false for INSERT/UPDATE/DELETE
    return $res === false ? true : $res;
}

/** Fetch all rows as associative arrays. */
function db_all(string $sql, array $params = []): array
{
    $res = db_run($sql, $params);
    if ($res instanceof mysqli_result) {
        return mysqli_fetch_all($res, MYSQLI_ASSOC);
    }
    return [];
}

/** Fetch a single row (or null). */
function db_one(string $sql, array $params = []): ?array
{
    $res = db_run($sql, $params);
    if ($res instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($res);
        return $row ?: null;
    }
    return null;
}

/** Fetch a single scalar value (or null). */
function db_val(string $sql, array $params = [])
{
    $row = db_one($sql, $params);
    return $row ? reset($row) : null;
}

/** Execute a write. Returns affected rows (int) or false on failure. */
function db_exec(string $sql, array $params = [])
{
    $res = db_run($sql, $params);
    return $res === false ? false : mysqli_affected_rows(db());
}

/** Last inserted id. */
function db_insert_id(): int
{
    return (int) mysqli_insert_id(db());
}

/** True if a table exists. */
function db_table_exists(string $table): bool
{
    $res = db_run("SHOW TABLES LIKE ?", [$table]);
    return $res instanceof mysqli_result && mysqli_num_rows($res) > 0;
}

/** Friendly fatal error page for DB connection failures. */
function cx_db_fatal(string $err): void
{
    http_response_code(500);
    $dev = cx_config()['app_env'] === 'development';
    $detail = $dev ? '<p style="margin:14px 0 0;font-size:13px;color:#B91C1C;">' . htmlspecialchars($err) . '</p>' : '';
    die('<div style="font-family:Inter,system-ui,sans-serif;max-width:560px;margin:80px auto;padding:32px;border-radius:16px;background:#fff;border:1px solid #FECACA;box-shadow:0 10px 40px rgba(0,0,0,.08);color:#991B1B;">
        <h2 style="margin:0 0 12px;font-size:20px;">Database Connection Failed</h2>
        <p style="margin:0;color:#7F1D1D;line-height:1.7;">Make sure MySQL is running, the database exists, and credentials are correct. If this is a fresh install, run <code>install.php</code>.</p>' . $detail . '</div>');
}
