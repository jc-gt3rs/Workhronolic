<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_manager();

$user = current_user();
$company_id = (int) $user['company_id'];
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$pending_row = db_one(
    "SELECT COUNT(*) AS total FROM time_entries WHERE company_id = ? AND status = 'pending'",
    'i',
    [$company_id]
);
$month_row = db_one(
    "SELECT COALESCE(SUM(hours), 0) AS total
     FROM time_entries
     WHERE company_id = ? AND status = 'approved' AND work_date BETWEEN ? AND ?",
    'iss',
    [$company_id, $month_start, $month_end]
);
$workers_row = db_one(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE company_id = ? AND status = 'active' AND role IN ('manager', 'employee')",
    'i',
    [$company_id]
);
$clocked_row = db_one(
    "SELECT COUNT(*) AS total
     FROM time_entries
     WHERE company_id = ? AND status = 'active' AND end_time IS NULL",
    'i',
    [$company_id]
);

$stats = [
    'pending'    => (int) ($pending_row['total'] ?? 0),
    'month'      => (float) ($month_row['total'] ?? 0),
    'workers'    => (int) ($workers_row['total'] ?? 0),
    'clocked_in' => (int) ($clocked_row['total'] ?? 0),
];

$report_rows = monthly_report($company_id, date('Y-m'));
$selected_worker_id = filter_var($_GET['worker'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$selected_worker = null;
$selected_logs = [];

if ($selected_worker_id) {
    foreach ($report_rows as $report_row) {
        if ((int) $report_row['id'] === $selected_worker_id) {
            $selected_worker = $report_row;
            $selected_logs = fetch_worker_time_logs($company_id, $selected_worker_id, $month_start, $month_end);
            break;
        }
    }
}

$base = '../';
$page_title = 'Team overview';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-2xl font-normal">Team overview</h1>
<p class="mt-1 text-sm text-ggray"><?= e(date('l, F j, Y')) ?></p>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <div class="rounded-2xl border border-gline bg-white p-6">
    <p class="text-sm text-ggray">Awaiting approval</p>
    <p class="mt-1 text-3xl font-normal"><?= (int) $stats['pending'] ?></p>
    <a href="timesheets.php" class="mt-2 inline-block text-sm font-medium text-gblue hover:underline">Review now</a>
  </div>
  <div class="rounded-2xl border border-gline bg-white p-6">
    <p class="text-sm text-ggray">Verified hours this month</p>
    <p class="mt-1 text-3xl font-normal"><?= e(format_hours($stats['month'])) ?></p>
  </div>
  <div class="rounded-2xl border border-gline bg-white p-6">
    <p class="text-sm text-ggray">Active workers</p>
    <p class="mt-1 text-3xl font-normal"><?= (int) $stats['workers'] ?></p>
  </div>
  <div class="rounded-2xl border border-gline bg-white p-6">
    <p class="text-sm text-ggray">Clocked in right now</p>
    <p class="mt-1 flex items-baseline gap-2 text-3xl font-normal">
      <?= (int) $stats['clocked_in'] ?>
      <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-ggreen" aria-hidden="true"></span>
    </p>
  </div>
</div>

<section class="mt-8" aria-labelledby="month-heading">
  <h2 id="month-heading" class="mb-3 text-base font-medium">This month at a glance</h2>
  <div class="overflow-x-auto rounded-2xl border border-gline bg-white">
    <table class="w-full min-w-[640px] text-left text-sm">
      <thead>
        <tr class="border-b border-gline text-xs font-medium uppercase tracking-wide text-ggray">
          <th class="px-6 py-3">Worker</th>
          <th class="px-6 py-3">Expected</th>
          <th class="px-6 py-3">Verified</th>
          <th class="px-6 py-3">Progress</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($report_rows as $row):
          $pct = $row['expected'] > 0 ? min(100, round($row['verified'] / $row['expected'] * 100)) : 0; ?>
      <tr class="border-b border-gline last:border-0 hover:bg-gbg <?= $selected_worker && (int) $selected_worker['id'] === (int) $row['id'] ? 'bg-gblue-tint/40' : '' ?>">
          <td class="px-6 py-4 font-medium">
            <a href="dashboard.php?worker=<?= (int) $row['id'] ?>#worker-log"
               class="text-gink hover:text-gblue hover:underline"
               aria-current="<?= $selected_worker && (int) $selected_worker['id'] === (int) $row['id'] ? 'true' : 'false' ?>">
              <?= e($row['worker']) ?>
            </a>
          </td>
          <td class="px-6 py-4"><?= e(format_hours((float) $row['expected'])) ?></td>
          <td class="px-6 py-4"><?= e(format_hours($row['verified'])) ?></td>
          <td class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="h-1.5 w-40 overflow-hidden rounded-full bg-gbg" role="presentation">
                <div class="h-full rounded-full <?= $pct >= 90 ? 'bg-ggreen' : ($pct >= 50 ? 'bg-gblue' : 'bg-gyellow') ?>"
                     style="width: <?= $pct ?>%"></div>
              </div>
              <span class="text-xs text-ggray"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php if ($selected_worker): ?>
<section id="worker-log" class="mt-8" aria-labelledby="worker-log-heading">
  <div class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <h2 id="worker-log-heading" class="text-base font-medium"><?= e($selected_worker['worker']) ?>'s time log</h2>
      <p class="mt-1 text-sm text-ggray">Clock-ins, clock-outs, and recorded breaks for <?= e(date('F Y', strtotime($month_start))) ?>.</p>
    </div>
    <a href="dashboard.php#month-heading" class="text-sm font-medium text-gblue hover:underline">Close log</a>
  </div>

  <div class="mt-3 overflow-x-auto rounded-2xl border border-gline bg-white">
    <table class="w-full min-w-[860px] text-left text-sm">
      <thead>
        <tr class="border-b border-gline text-xs font-medium uppercase tracking-wide text-ggray">
          <th class="px-6 py-3">Date</th>
          <th class="px-6 py-3">Time in</th>
          <th class="px-6 py-3">Breaks</th>
          <th class="px-6 py-3">Time out</th>
          <th class="px-6 py-3">Worked</th>
          <th class="px-6 py-3">Status</th>
          <th class="px-6 py-3">Approved at</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($selected_logs): ?>
          <?php foreach ($selected_logs as $log): ?>
          <tr class="border-b border-gline align-top last:border-0 hover:bg-gbg">
            <td class="px-6 py-4 whitespace-nowrap"><?= e(date('D, M j', strtotime($log['date']))) ?></td>
            <td class="px-6 py-4 font-mono text-xs whitespace-nowrap"><?= e(date('g:i A', strtotime($log['start']))) ?></td>
            <td class="px-6 py-4">
              <?php if ($log['breaks']): ?>
                <ul class="space-y-1 font-mono text-xs text-ggray">
                  <?php foreach ($log['breaks'] as $break): ?>
                    <li><?= e(date('g:i A', strtotime($break['break_start']))) ?> – <?= $break['break_end'] ? e(date('g:i A', strtotime($break['break_end']))) : 'Ongoing' ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <?php if ((int) $log['break_seconds'] > 0): ?>
                  <span class="text-ggray"><?= e(format_hours((int) $log['break_seconds'] / 3600)) ?> recorded (times unavailable)</span>
                <?php else: ?>
                  <span class="text-ggray">No breaks recorded</span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td class="px-6 py-4 font-mono text-xs whitespace-nowrap"><?= $log['end'] ? e(date('g:i A', strtotime($log['end']))) : '<span class="text-gblue">In progress</span>' ?></td>
            <td class="px-6 py-4 whitespace-nowrap"><?= $log['hours'] !== null ? e(format_hours((float) $log['hours'])) : '<span class="text-ggray">—</span>' ?></td>
            <td class="px-6 py-4"><?php
              $badges = [
                'approved' => 'bg-ggreen-tint text-ggreen',
                'pending'  => 'bg-gyellow-tint text-gyellow',
                'rejected' => 'bg-gred-tint text-gred',
                'active'   => 'bg-gblue-tint text-gblue',
              ];
            ?><span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $badges[$log['status']] ?? '' ?>"><?= e(ucfirst($log['status'])) ?></span></td>
            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-ggray">
              <?php if ($log['reviewed_at']): ?>
                <time datetime="<?= e($log['reviewed_at']) ?>" title="<?= e(date('F j, Y \a\t g:i A', strtotime($log['reviewed_at']))) ?>">
                  <?= e(date('M j, g:i A', strtotime($log['reviewed_at']))) ?>
                </time>
              <?php elseif ($log['status'] === 'pending'): ?>
                <span class="text-gyellow">Awaiting approval</span>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="px-6 py-10 text-center text-sm text-ggray">No time logs for this month.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
