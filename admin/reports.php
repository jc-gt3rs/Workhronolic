<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/demo_data.php';

require_manager();

// Month selector (validated: YYYY-MM only, never trusted raw).
$month = clean_text($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $month = date('Y-m');
}
$month_label = date('F Y', strtotime($month . '-01'));

// CSV export. BACKEND TODO: build rows from the real monthly aggregate query.
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="workhronolic-' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Worker', 'Expected hours', 'Verified hours', 'Pending hours', 'Entries', 'Variance']);
    foreach ($DEMO_REPORT as $row) {
        fputcsv($out, [
            $row['worker'], $row['expected'], $row['verified'], $row['pending'],
            $row['entries'], round($row['verified'] - $row['expected'], 2),
        ]);
    }
    fclose($out);
    exit;
}

$total_expected = array_sum(array_column($DEMO_REPORT, 'expected'));
$total_verified = array_sum(array_column($DEMO_REPORT, 'verified'));

$base = '../';
$page_title = 'Monthly report';
require __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-2xl font-normal">Monthly audit</h1>
    <p class="mt-1 text-sm text-ggray">Agreed hours vs. verified hours for payroll alignment.</p>
  </div>
  <div class="flex items-center gap-3">
    <form method="get" action="reports.php" class="flex items-center gap-2">
      <label class="sr-only" for="month">Report month</label>
      <input id="month" name="month" type="month" value="<?= e($month) ?>" max="<?= e(date('Y-m')) ?>"
             class="rounded-lg border border-gline px-3 py-2 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
      <button type="submit" class="rounded-full border border-gline px-5 py-2 text-sm font-medium text-gblue hover:bg-gblue-tint">View</button>
    </form>
    <a href="reports.php?month=<?= e($month) ?>&amp;export=csv"
       class="rounded-full bg-gblue px-5 py-2 text-sm font-medium text-white hover:bg-gblue-dark">Export CSV</a>
  </div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
  <div class="rounded-2xl border border-gline bg-white p-6">
    <p class="text-sm text-ggray">Expected · <?= e($month_label) ?></p>
    <p class="mt-1 text-3xl font-normal"><?= e(format_hours((float) $total_expected)) ?></p>
  </div>
  <div class="rounded-2xl border border-gline bg-white p-6">
    <p class="text-sm text-ggray">Verified</p>
    <p class="mt-1 text-3xl font-normal"><?= e(format_hours($total_verified)) ?></p>
  </div>
  <div class="rounded-2xl border border-gline bg-white p-6">
    <p class="text-sm text-ggray">Variance</p>
    <?php $variance = $total_verified - $total_expected; ?>
    <p class="mt-1 text-3xl font-normal <?= $variance < 0 ? 'text-gred' : 'text-ggreen' ?>">
      <?= $variance >= 0 ? '+' : '−' ?><?= e(format_hours(abs($variance))) ?>
    </p>
  </div>
</div>

<div class="mt-6 overflow-x-auto rounded-2xl border border-gline bg-white">
  <table class="w-full min-w-[720px] text-left text-sm">
    <thead>
      <tr class="border-b border-gline text-xs font-medium uppercase tracking-wide text-ggray">
        <th class="px-6 py-3">Worker</th>
        <th class="px-6 py-3">Expected</th>
        <th class="px-6 py-3">Verified</th>
        <th class="px-6 py-3">Pending</th>
        <th class="px-6 py-3">Entries</th>
        <th class="px-6 py-3">Variance</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($DEMO_REPORT as $row): $v = $row['verified'] - $row['expected']; ?>
      <tr class="border-b border-gline last:border-0 hover:bg-gbg">
        <td class="px-6 py-4 font-medium whitespace-nowrap"><?= e($row['worker']) ?></td>
        <td class="px-6 py-4"><?= e(format_hours((float) $row['expected'])) ?></td>
        <td class="px-6 py-4"><?= e(format_hours($row['verified'])) ?></td>
        <td class="px-6 py-4 text-ggray"><?= e(format_hours($row['pending'])) ?></td>
        <td class="px-6 py-4 text-ggray"><?= (int) $row['entries'] ?></td>
        <td class="px-6 py-4 font-medium <?= $v < 0 ? 'text-gred' : 'text-ggreen' ?>">
          <?= $v >= 0 ? '+' : '−' ?><?= e(format_hours(abs($v))) ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<p class="mt-4 text-xs text-ggray">
  Verified = approved entries only. Pending hours move into Verified once approved on the
  <a href="timesheets.php" class="font-medium text-gblue hover:underline">Approvals</a> page.
</p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
