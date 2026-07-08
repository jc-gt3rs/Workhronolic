<?php
/**
 * Workhronolic — app configuration & session bootstrap.
 *
 * BACKEND TODO: fill in the MySQL credentials below and uncomment the
 * mysqli connection. All queries must use prepared statements.
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

// ---------------------------------------------------------------------------
// Database (BACKEND TODO — uncomment and configure)
// ---------------------------------------------------------------------------
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'workhronolic');
//
// $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// if ($db->connect_error) {
//     http_response_code(500);
//     exit('Database connection failed.');
// }
// $db->set_charset('utf8mb4');

require_once __DIR__ . '/functions.php';
