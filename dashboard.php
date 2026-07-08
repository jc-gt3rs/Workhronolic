<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/demo_data.php';

require_login();
if (is_admin()) {
    redirect('admin/dashboard.php');
}

$user    = current_user();
$errors  = [];
$notice  = '';

// DEMO: active shift lives in the session. BACKEND TODO: read/write the
// open time_entries row (end_time IS NULL) for this user instead.
$active_since = $_SESSION['demo_clock_in'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'clock_in') {
        if ($active_since) {
            $errors[] = 'You are already clocked in.';
        } else {
            // BACKEND TODO: INSERT INTO time_entries (user_id, work_date, start_time) VALUES (?, ?, ?)
            $_SESSION['demo_clock_in'] = time();
            $active_since = $_SESSION['demo_clock_in'];
            $notice = 'Clocked in. Your timer is running.';
        }
    } elseif (($_POST['action'] ?? '') === 'clock_out') {
        $note = clean_text($_POST['note'] ?? '');
        if (!$active_since) {
            $errors[] = 'You are not clocked in.';
        } elseif (!valid_justification($note)) {
            $errors[] = 'Describe what you accomplished (at least 30 characters) before clocking out.';
        } else {
            // BACKEND TODO: UPDATE time_entries SET end_time = ?, note = ?, status = 'pending'
            // WHERE id = ? AND user_id = ? (prepared statement).
            unset($_SESSION['demo_clock_in']);
            $active_since = null;
            $notice = 'Clocked out. Your entry was submitted for review.';
        }
    }
}

$week_hours    = 12.0; // BACKEND TODO: SUM(hours) for current week
$month_hours   = 16.0; // BACKEND TODO: SUM(hours) for current month
$pending_count = 1;    // BACKEND TODO: COUNT(*) WHERE status = 'pending'

$page_title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-normal">Good day, <?= e(explode(' ', $user['name'])[0]) ?></h1>
<p class="mt-1 text-sm text-ggray"><?= e(date('l, F j, Y')) ?></p>

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
        <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-ggreen" aria-hidden="true"></span>
        <h2 id="clock-heading" class="text-sm font-medium text-ggreen">Clocked in since <?= e(date('g:i A', $active_since)) ?></h2>
      </div>

      <p id="live-timer" data-start="<?= (int) $active_since ?>"
         class="mt-4 font-mono text-6xl font-medium tabular-nums tracking-tight sm:text-7xl">0:00:00</p>

      <form method="post" action="dashboard.php" class="mt-8">
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
        <?php foreach (array_slice($DEMO_ENTRIES, 0, 4) as $entry): ?>
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
