<?php
/**
 * Authentication guards. Include after config.php on every protected page.
 *
 * BACKEND TODO: login.php should verify credentials against the `users`
 * table (password_verify against a password_hash() value) and populate
 * $_SESSION['user'] with id, name, email, role.
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/** Block unauthenticated visitors (prevents URL-manipulation access). */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect((str_contains($_SERVER['PHP_SELF'], '/admin/') ? '../' : '') . 'login.php');
    }
}

/** Block non-admin users from admin pages. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        redirect('../dashboard.php');
    }
}
