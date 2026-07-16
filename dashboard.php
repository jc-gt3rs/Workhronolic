<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login(); // owners and managers log time too — no role redirect here

$user    = current_user();
$errors  = [];
$notice  = '';

$active_entry = find_open_entry((int) $user['id']);
$break_rows = $active_entry ? fetch_entry_breaks((int) $active_entry['id']) : [];
$active_since = $active_entry ? strtotime($active_entry['work_date'] . ' ' . $active_entry['start_time']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'clock_in') {
        if ($active_entry) {
            $errors[] = 'You are already clocked in.';
        } else {
            $now = local_now();
            db_execute(
                "INSERT INTO time_entries (company_id, user_id, work_date, start_time, status)
                 VALUES (?, ?, ?, ?, 'active')",
                'iiss',
                [(int) $user['company_id'], (int) $user['id'], $now->format('Y-m-d'), $now->format('H:i:s')]
            );
            $notice = 'Clocked in. Your timer is running.';
        }
    } elseif (($_POST['action'] ?? '') === 'break_start') {
        $active_entry = find_open_entry((int) $user['id']);
        $break_rows = $active_entry ? fetch_entry_breaks((int) $active_entry['id']) : [];
        $open = $break_rows && end($break_rows)['break_end'] === null;
        if (!$active_entry) {
            $errors[] = 'You are not clocked in.';
        } elseif ($open) {
            $errors[] = 'You are already on a break.';
        } else {
            db_execute(
                'INSERT INTO entry_breaks (entry_id, break_start) VALUES (?, ?)',
                'is',
                [(int) $active_entry['id'], local_now()->format('Y-m-d H:i:s')]
            );
            $notice = 'Break started. Your timer is paused.';
        }
    } elseif (($_POST['action'] ?? '') === 'break_end') {
        $active_entry = find_open_entry((int) $user['id']);
        $break_rows = $active_entry ? fetch_entry_breaks((int) $active_entry['id']) : [];
        $open = $break_rows && end($break_rows)['break_end'] === null;
        if (!$open) {
            $errors[] = 'You are not on a break.';
        } else {
            db_execute(
                'UPDATE entry_breaks SET break_end = ? WHERE entry_id = ? AND break_end IS NULL',
                'si',
                [local_now()->format('Y-m-d H:i:s'), (int) $active_entry['id']]
            );
            $notice = 'Break ended. Your timer is running again.';
        }
    } elseif (($_POST['action'] ?? '') === 'clock_out') {
        $note = clean_text($_POST['note'] ?? '');
        $active_entry = find_open_entry((int) $user['id']);
        $break_rows = $active_entry ? fetch_entry_breaks((int) $active_entry['id']) : [];
        $open = $break_rows && end($break_rows)['break_end'] === null;
        if (!$active_entry) {
            $errors[] = 'You are not clocked in.';
        } elseif ($open) {
            $errors[] = 'End your break before clocking out.';
        } elseif (!valid_justification($note)) {
            $errors[] = 'Describe what you accomplished (at least 30 characters) before clocking out.';
        } else {
            $break_total = break_seconds($break_rows);
            $end_time = local_now()->format('H:i:s');
            $hours = calculate_hours($active_entry['work_date'], $active_entry['start_time'], $end_time, $break_total);
            if (is_owner()) {
                db_execute(
                    "UPDATE time_entries
                     SET end_time = ?, note = ?, break_seconds = ?, hours = ?, status = 'approved',
                         reviewed_by = ?, reviewed_at = ?
                     WHERE id = ? AND user_id = ? AND status = 'active'",
                    'ssidisii',
                    [$end_time, $note, $break_total, $hours, (int) $user['id'], local_now()->format('Y-m-d H:i:s'), (int) $active_entry['id'], (int) $user['id']]
                );
                $notice = 'Clocked out. Your entry was approved automatically.';
            } else {
                db_execute(
                    "UPDATE time_entries
                     SET end_time = ?, note = ?, break_seconds = ?, hours = ?, status = 'pending'
                     WHERE id = ? AND user_id = ? AND status = 'active'",
                    'ssidii',
                    [$end_time, $note, $break_total, $hours, (int) $active_entry['id'], (int) $user['id']]
                );
                $notice = 'Clocked out. Your entry was submitted for review.';
            }
        }
    }

    $active_entry = find_open_entry((int) $user['id']);
    $break_rows = $active_entry ? fetch_entry_breaks((int) $active_entry['id']) : [];
}

// Derived break state for rendering.
$active_since = $active_entry ? strtotime($active_entry['work_date'] . ' ' . $active_entry['start_time']) : null;
$break_since = ($break_rows && end($break_rows)['break_end'] === null) ? strtotime(end($break_rows)['break_start']) : null;
$break_total = break_seconds(array_filter($break_rows, fn ($b) => $b['break_end'] !== null));

$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$week_hours = user_hours_between((int) $user['id'], $week_start, $week_end);
$month_hours = user_hours_between((int) $user['id'], $month_start, $month_end);
$pending_count = pending_entry_count((int) $user['id']);
$recent_entries = fetch_user_entries((int) $user['id'], 4);

$page_title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-normal">Good day, <?= e(explode(' ', $user['name'])[0]) ?></h1>
<p class="mt-1 text-sm text-ggray"><?= e(date('l, F j, Y')) ?><?= !empty($user['company']) ? ' · ' . e($user['company']) : '' ?></p>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-3">

  <!-- Clock card -->
  <section class="lg:col-span-2 rounded-2xl border border-gline bg-white p-8" aria-labelledby="clock-heading">
    <?php if ($active_since): ?>
      <div class="flex items-center gap-2">
        <?php if ($break_since): ?>
          <span class="h-2.5 w-2.5 rounded-full bg-gyellow" aria-hidden="true"></span>
          <h2 id="clock-heading" class="text-sm font-medium text-gyellow">On break since <?= e(date('g:i A', $break_since)) ?> — timer paused</h2>
        <?php else: ?>
          <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-ggreen" aria-hidden="true"></span>
          <h2 id="clock-heading" class="text-sm font-medium text-ggreen">Clocked in since <?= e(date('g:i A', $active_since)) ?></h2>
        <?php endif; ?>
      </div>

      <div class="mt-4 flex flex-wrap items-end gap-x-8 gap-y-3">
        <p id="live-timer" data-start="<?= (int) $active_since ?>"
           data-break-total="<?= (int) $break_total ?>"
           <?= $break_since ? 'data-break-start="' . (int) $break_since . '"' : '' ?>
           class="font-mono text-6xl font-medium tabular-nums tracking-tight sm:text-7xl <?= $break_since ? 'text-ggray' : '' ?>">0:00:00</p>

        <?php if ($break_rows): ?>
        <ul class="pb-1.5 font-mono text-xs tabular-nums text-ggray" aria-label="Breaks this shift">
          <?php foreach ($break_rows as $i => $b): ?>
            <?php if ($b['break_end'] !== null): $secs = strtotime($b['break_end']) - strtotime($b['break_start']); ?>
              <li>Break <?= $i + 1 ?>: <?= e(sprintf('%d:%02d:%02d', intdiv($secs, 3600), intdiv($secs % 3600, 60), $secs % 60)) ?></li>
            <?php else: ?>
              <li class="text-gyellow">Break <?= $i + 1 ?>: <span id="current-break">0:00:00</span> …</li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <p class="mt-2 text-sm text-ggray">
        Breaks total: <span id="break-timer" class="font-mono text-xs"><?= e(format_hours($break_total / 3600)) ?></span>
        — not counted toward your hours.
      </p>

      <?php if ($break_since): ?>
        <form method="post" action="dashboard.php" class="mt-8">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="break_end">
          <button type="submit"
                  class="rounded-full bg-gblue px-8 py-3 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
            End break
          </button>
        </form>
      <?php else: ?>
        <form method="post" action="dashboard.php" class="mt-8">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="break_start">
          <button type="submit"
                  class="rounded-full border border-gline px-6 py-2.5 text-sm font-medium text-gyellow hover:bg-gyellow-tint focus:outline-none focus:ring-2 focus:ring-gyellow/40 focus:ring-offset-2">
            Start break
          </button>
        </form>

        <form method="post" action="dashboard.php" class="mt-6 border-t border-gline pt-6">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="clock_out">
          <label class="mb-1.5 block text-sm font-medium" for="note">What did you accomplish?</label>
          <textarea id="note" name="note" rows="3" required minlength="30"
                    placeholder="Detail the tasks you completed and the milestones they map to (minimum 30 characters)…"
                    class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30"></textarea>
          <div class="mt-1 flex items-center justify-between">
            <p class="text-xs text-ggray"><span id="note-count">0</span>/30 characters minimum</p>
            <button type="submit"
                    class="rounded-full bg-gred px-6 py-2.5 text-sm font-medium text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-gred/40 focus:ring-offset-2">
              Clock out
            </button>
          </div>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <h2 id="clock-heading" class="text-sm font-medium text-ggray">You are off the clock</h2>
      <p id="live-timer" class="mt-4 font-mono text-6xl font-medium tabular-nums tracking-tight text-gline sm:text-7xl">0:00:00</p>
      <form method="post" action="dashboard.php" class="mt-8">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="clock_in">
        <button type="submit"
                class="rounded-full bg-gblue px-8 py-3 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
          Clock in
        </button>
      </form>
    <?php endif; ?>
  </section>

  <!-- Summary -->
  <section class="flex flex-col gap-4" aria-label="Your hours summary">
    <div class="rounded-2xl border border-gline bg-white p-6">
      <p class="text-sm text-ggray">This week</p>
      <p class="mt-1 text-3xl font-normal"><?= e(format_hours($week_hours)) ?></p>
    </div>
    <div class="rounded-2xl border border-gline bg-white p-6">
      <p class="text-sm text-ggray">This month</p>
      <p class="mt-1 text-3xl font-normal"><?= e(format_hours($month_hours)) ?></p>
    </div>
    <div class="rounded-2xl border border-gline bg-white p-6">
      <p class="text-sm text-ggray">Awaiting review</p>
      <p class="mt-1 text-3xl font-normal"><?= (int) $pending_count ?> <span class="text-base text-ggray">entr<?= $pending_count === 1 ? 'y' : 'ies' ?></span></p>
      <a href="timesheet.php" class="mt-2 inline-block text-sm font-medium text-gblue hover:underline">View timesheet</a>
    </div>
  </section>
</div>

<!-- Recent entries -->
<section class="mt-8" aria-labelledby="recent-heading">
  <h2 id="recent-heading" class="mb-3 text-base font-medium">Recent entries</h2>
  <div class="overflow-x-auto rounded-2xl border border-gline bg-white">
    <table class="w-full min-w-[540px] text-left text-sm">
      <thead>
        <tr class="border-b border-gline text-xs font-medium uppercase tracking-wide text-ggray">
          <th class="px-6 py-3">Date</th>
          <th class="px-6 py-3">Time</th>
          <th class="px-6 py-3">Hours</th>
          <th class="px-6 py-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_entries as $entry): ?>
        <tr class="border-b border-gline last:border-0 hover:bg-gbg">
          <td class="px-6 py-3.5 whitespace-nowrap"><?= e(date('D, M j', strtotime($entry['date']))) ?></td>
          <td class="px-6 py-3.5 font-mono text-xs"><?= e($entry['start']) ?> – <?= e($entry['end'] ?? 'now') ?></td>
          <td class="px-6 py-3.5"><?= $entry['hours'] !== null ? e(format_hours($entry['hours'])) : '—' ?></td>
          <td class="px-6 py-3.5"><?php
            $badges = [
              'approved' => 'bg-ggreen-tint text-ggreen',
              'pending'  => 'bg-gyellow-tint text-gyellow',
              'rejected' => 'bg-gred-tint text-gred',
              'active'   => 'bg-gblue-tint text-gblue',
            ];
          ?><span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $badges[$entry['status']] ?? '' ?>"><?= e(ucfirst($entry['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
