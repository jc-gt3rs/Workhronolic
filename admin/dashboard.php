<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/demo_data.php';

require_admin();

// BACKEND TODO: replace with aggregate queries (COUNT of pending entries,
// SUM of month hours, active workers, who is clocked in right now).
$stats = [
    'pending'    => count($DEMO_PENDING),
    'month'      => 142.5,
    'workers'    => 3,
    'clocked_in' => 1,
];

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
        <?php foreach ($DEMO_REPORT as $row):
          $pct = $row['expected'] > 0 ? min(100, round($row['verified'] / $row['expected'] * 100)) : 0; ?>
        <tr class="border-b border-gline last:border-0 hover:bg-gbg">
          <td class="px-6 py-4 font-medium"><?= e($row['worker']) ?></td>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>
