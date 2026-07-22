# ChronoX — Biometric Attendance & Time-Tracking

A market-ready, multi-device attendance platform for companies that run several
ZKTeco clocking machines (e.g. one biometric terminal per department). Built with
PHP + MySQL, hardened for production, and designed to scale.

## Key features

- **Multi-device support** — register any number of clocking machines, each mapped
  to a department, with individual IP/port/status. Sync one device or all active
  devices at once.
- **Departments** — normalized department entities with employee & device counts.
- **Employees** — CRUD with department assignment, status, contact info, search &
  pagination (scales to thousands of records).
- **Attendance** — daily view with present / late / absent status, working hours,
  per-employee history, and CSV export.
- **Analytics** — monthly presence trend + department distribution + top attendees.
- **Roles & permissions (RBAC)** — Administrator / Manager / Viewer.
- **Sync history & audit log** — every device sync and admin action is recorded.

## Security hardening

- **Prepared statements everywhere** via `core/db.php` helpers — no string-built SQL.
- **CSRF protection** on every state-changing form (`csrf_field()` / `csrf_verify()`).
- **Hardened sessions** — HttpOnly + SameSite cookies, idle timeout, fingerprint
  binding, and `session_regenerate_id()` on login to stop fixation.
- **Login throttling** — temporary lockout after repeated failures.
- **Password hashing** with `password_hash()` (bcrypt).
- **Role enforcement** — `require_perm()` gates admin-only actions server-side.
- **Env-based config** — credentials come from environment / `config.local.php`,
  never hardcoded in tracked files. Errors are hidden in production.

## Getting started (XAMPP)

1. Create a MySQL database named `clocking` (or set your own name — see below).
2. (Optional) Copy `core/config.local.php.sample` → `core/config.local.php` and set
   your DB credentials / environment.
3. Visit `http://localhost/attendance_system/install.php` to build the schema and
   seed 5 departments, 5 devices, and a default admin.
4. Log in at `login.php` with **admin / admin123** and change the password
   immediately (Users & Roles).

## Configuration

Settings are read from environment variables first, then `core/config.local.php`,
then sensible defaults in `core/config.php`:

| Variable            | Purpose                    | Default        |
|---------------------|----------------------------|----------------|
| `CHRONOX_DB_HOST`   | MySQL host                 | `127.0.0.1`    |
| `CHRONOX_DB_NAME`   | Database name              | `clocking`     |
| `CHRONOX_DB_USER`   | DB user                    | `root`         |
| `CHRONOX_DB_PASS`   | DB password                | *(empty)*      |
| `CHRONOX_ENV`       | `development` / `production` | `production` |
| `CHRONOX_TZ`        | Timezone                   | `Africa/Casablanca` |

## Architecture

```
core/            Reusable backend (config, db, security, auth/RBAC, audit, settings)
install*.php     Schema installer/migrator + seed + result view
dashboard/       App pages (guarded by dashboard/bootstrap.php)
  includes/      Shared header / sidebar / footer + auth shim
index.php        Public marketing landing page
login.php        Hardened authentication
```

## Device sync

The sync engine (`dashboard/sync_attendance.php`) connects to each device by its
stored IP/port, pulls punches, maps them to employees by `user_id`, de-duplicates
against a unique punch key, records the run in `sync_logs`, and updates the device's
`last_sync_at`. Devices that are offline never block the others.

> Requires the `rats/zkteco` composer package on a server with network access to
> the devices. Run `composer install` before syncing real hardware.
