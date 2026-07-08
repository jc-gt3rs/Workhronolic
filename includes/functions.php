<?php
/**
 * Shared helpers: output escaping, input sanitation, validation, CSRF.
 */

/**
 * Escape a value for safe HTML output (XSS defense).
 * Use on EVERY piece of user-supplied data that is echoed.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Trim + strip control characters from a text input. */
function clean_text(?string $value): string
{
    $value = trim($value ?? '');
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
}

/** Validate an email address. Returns the normalized email or null. */
function valid_email(?string $value): ?string
{
    $value = filter_var(clean_text($value), FILTER_VALIDATE_EMAIL);
    return $value === false ? null : strtolower($value);
}

/** Validate a HH:MM (24h) time string. */
function valid_time(?string $value): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', clean_text($value));
}

/** Validate a YYYY-MM-DD date string. */
function valid_date(?string $value): bool
{
    $value = clean_text($value);
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

/** End time must be strictly after start time (same day). */
function time_range_valid(string $start, string $end): bool
{
    return valid_time($start) && valid_time($end) && strtotime($end) > strtotime($start);
}

/** Minimum-length check for justification / accomplishment notes. */
function valid_justification(?string $value, int $min = 30): bool
{
    return mb_strlen(clean_text($value)) >= $min;
}

/** Password policy: 8+ chars, at least one letter and one number. */
function valid_password(?string $value): bool
{
    $value = $value ?? '';
    return strlen($value) >= 8
        && preg_match('/[A-Za-z]/', $value)
        && preg_match('/\d/', $value);
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------

/** Get (or create) the CSRF token for this session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to drop inside every <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify the token submitted with a POST request. */
function csrf_verify(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ---------------------------------------------------------------------------
// Misc
// ---------------------------------------------------------------------------

/** Redirect and stop execution. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Format decimal hours as "7h 30m". */
function format_hours(float $hours): string
{
    $h = floor($hours);
    $m = round(($hours - $h) * 60);
    return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
}
