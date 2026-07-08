<?php
/**
 * Workhronolic — app configuration & session bootstrap.
 */

// Secure session cookie settings must be set before session_start().
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,        // JS cannot read the session cookie
        'samesite' => 'Lax',       // CSRF hardening
        // 'secure' => true,       // enable when serving over HTTPS
    ]);
    session_start();
}

define('APP_NAME', 'Workhronolic');
define('APP_TIMEZONE', getenv('WORKHRONOLIC_TIMEZONE') ?: 'Asia/Manila');

date_default_timezone_set(APP_TIMEZONE);

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------
// XAMPP defaults: root user, empty password, local MySQL server.
define('DB_HOST', getenv('WORKHRONOLIC_DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('WORKHRONOLIC_DB_USER') ?: 'root');
define('DB_PASS', getenv('WORKHRONOLIC_DB_PASS') ?: '');
define('DB_NAME', getenv('WORKHRONOLIC_DB_NAME') ?: 'workhronolic');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
    $offset = (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))->format('P');
    $db->query("SET time_zone = '" . $db->real_escape_string($offset) . "'");
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Import database/schema.sql in phpMyAdmin and confirm your XAMPP MySQL service is running.');
}

require_once __DIR__ . '/functions.php';
