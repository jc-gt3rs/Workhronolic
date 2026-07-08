<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/demo_data.php';

require_admin();

$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $id     = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? '';
        if ($id && in_array($action, ['approve', 'reject'], true)) {
            // BACKEND TODO: UPDATE time_entries SET status = ?, reviewed_by = ?, reviewed_at = NOW()
            // WHERE id = ? AND status = 'pending' (prepared statement).
            $notice = $action === 'approve' ? 'Entry approved.' : 'Entry rejected — the worker can revise and resubmit.';
        } else {
            $errors[] = 'Invalid request.';
        }
    }
}

$base = '../';
$page_title = 'Approvals';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-2xl font-normal">Timesheet approvals</h1>
<p class="mt-1 text-sm text-ggray">Review each entry against its accomplishment notes before approving.</p>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$DEMO_PENDING): ?>
  <div class="mt-6 rounded-2xl border border-gline bg-white p-10 text-center">
    <p class="text-base font-medium">All caught up</p>
    <p class="mt-1 text-sm text-ggray">New clock-outs and manual entries will appear here for review.</p>
  </div>
<?php else: ?>
  <div class="mt-6 space-y-4">
    <?php foreach ($DEMO_PENDING as $entry): ?>
    <article class="rounded-2xl border border-gline bg-white p-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="font-medium"><?= e($entry['worker']) ?></p>
          <p class="mt-0.5 text-sm text-ggray">
            <?= e(date('l, M j', strtotime($entry['date']))) ?> ·
            <span class="font-mono text-xs"><?= e($entry['start']) ?> – <?= e($entry['end']) ?></span> ·
            <?= e(format_hours($entry['hours'])) ?>
          </p>
        </div>
        <div class="flex gap-2">
          <form method="post" action="timesheets.php" data-confirm="Reject this entry? The worker will be asked to revise it.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
            <button type="submit"
                    class="rounded-full border border-gline px-5 py-2 text-sm font-medium text-gred hover:bg-gred-tint">Reject</button>
          </form>
          <form method="post" action="timesheets.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
            <button type="submit"
                    class="rounded-full bg-gblue px-5 py-2 text-sm font-medium text-white hover:bg-gblue-dark">Approve</button>
          </form>
        </div>
      </div>
      <p class="mt-4 rounded-lg bg-gbg px-4 py-3 text-sm text-gink"><?= e($entry['note']) ?></p>
    </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script src="../assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
