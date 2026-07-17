<?php
/**
 * Shared helpers: output escaping, input sanitation, validation, CSRF.
 */

// ---------------------------------------------------------------------------
// Database helpers
// ---------------------------------------------------------------------------

function db(): mysqli
{
    global $db;
    return $db;
}

function db_bind(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') {
        return;
    }

    $refs = [$types];
    foreach ($params as $key => $value) {
        $refs[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function db_execute(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    db_bind($stmt, $types, $params);
    $stmt->execute();
    return $stmt;
}

function db_one(string $sql, string $types = '', array $params = []): ?array
{
    $result = db_execute($sql, $types, $params)->get_result();
    $row = $result->fetch_assoc();
    return $row ?: null;
}

function db_all(string $sql, string $types = '', array $params = []): array
{
    $result = db_execute($sql, $types, $params)->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function session_user_from_row(array $row): array
{
    return [
        'id'         => (int) $row['id'],
        'name'       => $row['name'],
        'email'      => $row['email'],
        'role'       => $row['role'],
        'company_id' => (int) $row['company_id'],
        'company'    => $row['company_name'] ?? '',
        'status'     => $row['status'],
    ];
}

function current_company(): ?array
{
    $company_id = current_user()['company_id'] ?? null;
    if (!$company_id) {
        return null;
    }

    return db_one('SELECT id, name, code FROM companies WHERE id = ?', 'i', [$company_id]);
}

function local_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE));
}

function find_open_entry(int $user_id): ?array
{
    return db_one(
        "SELECT * FROM time_entries
         WHERE user_id = ? AND status = 'active' AND end_time IS NULL
         ORDER BY id DESC LIMIT 1",
        'i',
        [$user_id]
    );
}

function fetch_entry_breaks(int $entry_id): array
{
    return db_all(
        'SELECT id, break_start, break_end FROM entry_breaks WHERE entry_id = ? ORDER BY break_start',
        'i',
        [$entry_id]
    );
}

function break_seconds(array $breaks, ?int $until = null): int
{
    $total = 0;
    $until = $until ?? local_now()->getTimestamp();

    foreach ($breaks as $break) {
        $start = strtotime($break['break_start']);
        $end = $break['break_end'] ? strtotime($break['break_end']) : $until;
        if ($start !== false && $end !== false && $end > $start) {
            $total += $end - $start;
        }
    }

    return $total;
}

function calculate_hours(string $date, string $start, string $end, int $break_seconds = 0): float
{
    $start_ts = strtotime($date . ' ' . $start);
    $end_ts = strtotime($date . ' ' . $end);
    if ($start_ts === false || $end_ts === false || $end_ts <= $start_ts) {
        return 0.0;
    }

    return round(max(0, $end_ts - $start_ts - $break_seconds) / 3600, 2);
}

function fetch_user_entries(int $user_id, int $limit = 0): array
{
    $sql = 'SELECT id, work_date AS date, start_time AS start, end_time AS end,
                   hours, status, note
            FROM time_entries
            WHERE user_id = ?
            ORDER BY work_date DESC, start_time DESC, id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    return db_all($sql, 'i', [$user_id]);
}

function user_hours_between(int $user_id, string $start_date, string $end_date): float
{
    $row = db_one(
        "SELECT COALESCE(SUM(hours), 0) AS total
         FROM time_entries
         WHERE user_id = ? AND status = 'approved'
           AND work_date BETWEEN ? AND ?",
        'iss',
        [$user_id, $start_date, $end_date]
    );

    return (float) ($row['total'] ?? 0);
}

function pending_entry_count(int $user_id): int
{
    $row = db_one(
        "SELECT COUNT(*) AS total FROM time_entries WHERE user_id = ? AND status = 'pending'",
        'i',
        [$user_id]
    );

    return (int) ($row['total'] ?? 0);
}

function monthly_report(int $company_id, string $month): array
{
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));

    return db_all(
        "SELECT
             u.id,
             u.name AS worker,
             u.expected_hours AS expected,
             COALESCE(SUM(CASE WHEN te.status = 'approved' THEN te.hours ELSE 0 END), 0) AS verified,
             COALESCE(SUM(CASE WHEN te.status = 'pending' THEN te.hours ELSE 0 END), 0) AS pending,
             COUNT(te.id) AS entries
         FROM users u
         LEFT JOIN time_entries te
           ON te.user_id = u.id
          AND te.work_date BETWEEN ? AND ?
          AND te.status IN ('approved', 'pending')
         WHERE u.company_id = ?
           AND u.status = 'active'
           AND u.role IN ('manager', 'employee')
         GROUP BY u.id, u.name, u.expected_hours
         ORDER BY u.name",
        'ssi',
        [$start, $end, $company_id]
    );
}

/**
 * Return one worker's time-entry history for a company and date range, with
 * each entry's break intervals grouped alongside it for display in management
 * views.
 */
function fetch_worker_time_logs(int $company_id, int $user_id, string $start_date, string $end_date): array
{
    $entries = db_all(
        "SELECT id, work_date AS date, start_time AS start, end_time AS end,
                break_seconds, hours, status, reviewed_at
         FROM time_entries
         WHERE company_id = ? AND user_id = ?
           AND work_date BETWEEN ? AND ?
         ORDER BY work_date DESC, start_time DESC, id DESC",
        'iiss',
        [$company_id, $user_id, $start_date, $end_date]
    );

    if (!$entries) {
        return [];
    }

    $breaks = db_all(
        "SELECT eb.entry_id, eb.break_start, eb.break_end
         FROM entry_breaks eb
         JOIN time_entries te ON te.id = eb.entry_id
         WHERE te.company_id = ? AND te.user_id = ?
           AND te.work_date BETWEEN ? AND ?
         ORDER BY eb.entry_id, eb.break_start",
        'iiss',
        [$company_id, $user_id, $start_date, $end_date]
    );

    $breaks_by_entry = [];
    foreach ($breaks as $break) {
        $breaks_by_entry[(int) $break['entry_id']][] = $break;
    }

    foreach ($entries as &$entry) {
        $entry['breaks'] = $breaks_by_entry[(int) $entry['id']] ?? [];
    }
    unset($entry);

    return $entries;
}

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

/** Validate a company name. */
function valid_company_name(?string $value): bool
{
    $len = mb_strlen(clean_text($value));
    return $len >= 2 && $len <= 60;
}

/** Validate a company join code (format: XX-XXXXXX). */
function valid_company_code(?string $value): bool
{
    return (bool) preg_match('/^[A-Z]{2}-[A-Z0-9]{6}$/', strtoupper(clean_text($value)));
}

/**
 * Generate a unique, shareable company code from the company name.
 * Callers retry on collision against the UNIQUE index on companies.code.
 */
function generate_company_code(string $company_name): string
{
    $prefix = strtoupper(preg_replace('/[^a-z]/i', '', $company_name) . 'XX');
    $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ'; // no 0/O/1/I/L lookalikes
    $suffix = '';
    for ($i = 0; $i < 6; $i++) {
        $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return substr($prefix, 0, 2) . '-' . $suffix;
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
