<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_manager();

$user = current_user();
$company_id = (int) $user['company_id'];
$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $id     = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? '';
        if ($id && in_array($action, ['approve', 'reject'], true)) {
            $review_comment = clean_text($_POST['review_comment'] ?? '');
            if (mb_strlen($review_comment) > 1000) {
                $errors[] = 'Review comments cannot exceed 1,000 characters.';
            } else {
                $status = $action === 'approve' ? 'approved' : 'rejected';
                db_execute(
                    'UPDATE time_entries
                     SET status = ?, review_comment = ?, reviewed_by = ?, reviewed_at = ?
                     WHERE id = ? AND status = ? AND company_id = ?',
                    'ssisisi',
                    [$status, $review_comment !== '' ? $review_comment : null, (int) $user['id'], local_now()->format('Y-m-d H:i:s'), $id, 'pending', $company_id]
                );
                $notice = $action === 'approve'
                    ? 'Entry approved. Your review is now visible to the worker.'
                    : 'Entry rejected. Your review is now visible so the worker can revise and resubmit.';
            }
        } elseif ($id && in_array($action, ['join_accept', 'join_decline'], true)) {
            if ($action === 'join_accept') {
                db_execute(
                    "UPDATE users SET status = 'active'
                     WHERE id = ? AND company_id = ? AND status = 'pending'",
                    'ii',
                    [$id, $company_id]
                );
                $notice = 'Join request accepted - they can now sign in.';
            } else {
                db_execute(
                    "DELETE FROM users
                     WHERE id = ? AND company_id = ? AND status = 'pending'",
                    'ii',
                    [$id, $company_id]
                );
                $notice = 'Join request declined.';
            }
        } else {
            $errors[] = 'Invalid request.';
        }
    }
}

$join_requests = db_all(
    "SELECT id, name, email, role, created_at AS requested
     FROM users
     WHERE company_id = ? AND status = 'pending'
     ORDER BY created_at",
    'i',
    [$company_id]
);
$pending_entries = db_all(
    "SELECT te.id, u.name AS worker, te.work_date AS date, te.start_time AS start,
            te.end_time AS end, te.hours, te.note
     FROM time_entries te
     JOIN users u ON u.id = te.user_id
     WHERE te.company_id = ? AND te.status = 'pending'
     ORDER BY te.work_date, te.start_time",
    'i',
    [$company_id]
);

$base = '../';
$page_title = 'Approvals';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-2xl font-normal">Approvals</h1>
<p class="mt-1 text-sm text-ggray">Review time entries against their accomplishment notes, and decide on company join requests.</p>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Join requests -->
<section class="mt-8" aria-labelledby="join-heading">
  <h2 id="join-heading" class="mb-3 text-base font-medium">Join requests</h2>
  <?php if (!$join_requests): ?>
    <div class="rounded-2xl border border-gline bg-white p-8 text-center">
      <p class="text-sm text-ggray">No one is waiting to join. Share your company code to invite people.</p>
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($join_requests as $req): ?>
      <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gline bg-white px-6 py-4">
        <div>
          <p class="font-medium"><?= e($req['name']) ?>
            <span class="ml-1 rounded-full px-2.5 py-0.5 text-xs font-medium <?= $req['role'] === 'manager'
                ? 'bg-gblue-tint text-gblue' : 'bg-gbg text-ggray' ?>">Joining as <?= e($req['role']) ?></span>
          </p>
          <p class="mt-0.5 text-sm text-ggray"><?= e($req['email']) ?> · requested <?= e(date('M j', strtotime($req['requested']))) ?></p>
        </div>
        <div class="flex gap-2">
          <form method="post" action="timesheets.php" data-confirm="Decline this join request?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="join_decline">
            <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
            <button type="submit" class="rounded-full border border-gline px-5 py-2 text-sm font-medium text-gred hover:bg-gred-tint">Decline</button>
          </form>
          <form method="post" action="timesheets.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="join_accept">
            <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
            <button type="submit" class="rounded-full bg-gblue px-5 py-2 text-sm font-medium text-white hover:bg-gblue-dark">Accept</button>
          </form>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<h2 class="mt-8 mb-3 text-base font-medium">Time entries</h2>
<?php if (!$pending_entries): ?>
  <div class="mt-6 rounded-2xl border border-gline bg-white p-10 text-center">
    <p class="text-base font-medium">All caught up</p>
    <p class="mt-1 text-sm text-ggray">New clock-outs and manual entries will appear here for review.</p>
  </div>
<?php else: ?>
  <div class="mt-6 space-y-4">
    <?php foreach ($pending_entries as $entry): ?>
    <article class="rounded-2xl border border-gline bg-white p-6">
      <div>
        <p class="font-medium"><?= e($entry['worker']) ?></p>
        <p class="mt-0.5 text-sm text-ggray">
          <?= e(date('l, M j', strtotime($entry['date']))) ?> ·
          <span class="font-mono text-xs"><?= e($entry['start']) ?> – <?= e($entry['end']) ?></span> ·
          <?= e(format_hours($entry['hours'])) ?>
        </p>
      </div>
      <p class="mt-4 rounded-lg bg-gbg px-4 py-3 text-sm text-gink"><?= e($entry['note']) ?></p>

      <form method="post" action="timesheets.php" class="mt-5">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">

        <label class="mb-1.5 block text-sm font-medium" for="review-comment-<?= (int) $entry['id'] ?>">Review comment <span class="font-normal text-ggray">(optional)</span></label>
        <textarea id="review-comment-<?= (int) $entry['id'] ?>" name="review_comment" rows="2" maxlength="1000"
                  placeholder="Add feedback, clarification, or a note for the worker…"
                  class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30"></textarea>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
          <p class="text-xs text-ggray">The worker will see this comment with the review decision.</p>
          <div class="flex gap-2">
            <button type="submit" name="action" value="reject"
                    data-confirm="Reject this entry? The worker will be asked to revise it."
                    class="rounded-full border border-gline px-5 py-2 text-sm font-medium text-gred hover:bg-gred-tint">Reject</button>
            <button type="submit" name="action" value="approve"
                    class="rounded-full bg-gblue px-5 py-2 text-sm font-medium text-white hover:bg-gblue-dark">Approve</button>
          </div>
        </div>
      </form>
    </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script src="../assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
