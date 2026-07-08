<?php
/**
 * Authentication guards. Include after config.php on every protected page.
 *
 * Roles:
 *   owner    — created the company; full control, including managing people.
 *   manager  — everything the owner can do EXCEPT managing people.
 *   employee — tracks time, submits justifications, edits own profile.
 * Owners and managers also log their own time, so every role gets the
 * personal Dashboard / Timesheet / Profile pages.
 *
 * BACKEND TODO: login.php should verify credentials against the `users`
 * table (password_verify against a password_hash() value) and populate
 * $_SESSION['user'] with id, name, email, role, company_id, status.
 * Every data query must be scoped to the session's company_id.
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): string
{
    return current_user()['role'] ?? '';
}

function is_owner(): bool
{
    return user_role() === 'owner';
}

/** Owner or manager — anyone who can run company operations. */
function is_manager(): bool
{
    return in_array(user_role(), ['owner', 'manager'], true);
}

/** Block unauthenticated visitors (prevents URL-manipulation access). */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect((str_contains($_SERVER['PHP_SELF'], '/admin/') ? '../' : '') . 'login.php');
    }
}

/** Block employees from management pages. */
function require_manager(): void
{
    require_login();
    if (!is_manager()) {
        redirect('../dashboard.php');
    }
}

/** Owner-only pages (managing people, company settings). */
function require_owner(): void
{
    require_login();
    if (!is_owner()) {
        redirect(is_manager() ? 'dashboard.php' : '../dashboard.php');
    }
}
