<?php
/**
 * ChronoX — Central Configuration
 * ---------------------------------
 * Reads settings from environment variables (production) and falls back to
 * sensible XAMPP defaults (local dev). To override locally without touching
 * this file, create core/config.local.php returning an array of overrides.
 */

$defaults = [
    // Database
    'db_host'    => getenv('CHRONOX_DB_HOST') ?: '127.0.0.1',
    'db_name'    => getenv('CHRONOX_DB_NAME') ?: 'clocking',
    'db_user'    => getenv('CHRONOX_DB_USER') ?: 'root',
    'db_pass'    => getenv('CHRONOX_DB_PASS') ?: '',
    'db_port'    => (int)(getenv('CHRONOX_DB_PORT') ?: 3306),

    // Application
    'app_name'   => 'ChronoX',
    'app_env'    => getenv('CHRONOX_ENV') ?: 'production', // 'development' shows errors
    'timezone'   => getenv('CHRONOX_TZ') ?: 'Africa/Casablanca',

    // Security
    'session_name'      => 'chronox',
    'session_idle_secs' => 60 * 60,   // auto-logout after 1h idle
    'login_max_attempts'=> 5,          // lockout threshold
    'login_lockout_secs'=> 15 * 60,    // 15 min lockout window

    // Business rules (also editable via settings table at runtime)
    'work_start' => '09:00:00',        // late threshold
];

// Optional local overrides (git-ignored)
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $overrides = require $localFile;
    if (is_array($overrides)) {
        $defaults = array_merge($defaults, $overrides);
    }
}

return $defaults;
