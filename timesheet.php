<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login(); // owners and managers keep personal timesheets too

$user = current_user();
$errors = [];
$notice = '';

// Editable target (only pending/rejected entries may be changed).
$edit_id = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$editing = $edit_id ? db_one(
    "SELECT id, work_date AS date, start_time AS start, end_time AS end, note, status
     FROM time_entries
     WHERE id = ? AND user_id = ? AND status IN ('pending', 'rejected')
     LIMIT 1",
    'ii',
    [$edit_id, (int) $user['id']]
) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $date  = clean_text($_POST['date'] ?? '');
            $start = clean_text($_POST['start'] ?? '');
            $end   = clean_text($_POST['end'] ?? '');
            $note  = clean_text($_POST['note'] ?? '');
            $id    = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!valid_date($date)) {
                $errors[] = 'Enter a valid date.';
            } elseif (strtotime($date) > time()) {
                $errors[] = 'The date cannot be in the future.';
            }
            if (!valid_time($start) || !valid_time($end)) {
                $errors[] = 'Enter start and end times in HH:MM format.';
            } elseif (!time_range_valid($start, $end)) {
                $errors[] = 'End time must be after the start time.';
            }
            if (!valid_justification($note)) {
                $errors[] = 'Accomplishment notes need at least 30 characters.';
            }

            if (!$errors) {
                $hours = calculate_hours($date, $start, $end);
                if ($id) {
                    db_execute(
                        "UPDATE time_entries
                         SET work_date = ?, start_time = ?, end_time = ?, break_seconds = 0,
                             hours = ?, note = ?, status = 'pending', reviewed_by = NULL, reviewed_at = NULL
                         WHERE id = ? AND user_id = ? AND status IN ('pending', 'rejected')",
                        'sssdsii',
                        [$date, $start, $end, $hours, $note, $id, (int) $user['id']]
                    );
                } else {
                    db_execute(
                        "INSERT INTO time_entries
                            (company_id, user_id, work_date, start_time, end_time, break_seconds, hours, note, status)
                         VALUES (?, ?, ?, ?, ?, 0, ?, ?, 'pending')",
                        'iisssds',
                        [(int) $user['company_id'], (int) $user['id'], $date, $start, $end, $hours, $note]
                    );
                }
                $notice = $id ? 'Entry updated and resubmitted for review.' : 'Entry added and submitted for review.';
                $editing = null;
            }
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id) {
                db_execute(
                    "DELETE FROM time_entries
                     WHERE id = ? AND user_id = ? AND status IN ('pending', 'rejected')",
                    'ii',
                    [$id, (int) $user['id']]
                );
                $notice = 'Entry deleted.';
            }
        }
    }
}

$entries = fetch_user_entries((int) $user['id']);

$page_title = 'Timesheet';
require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-2xl font-normal">Timesheet</h1>
    <p class="mt-1 text-sm text-ggray">Pending and rejected entries can be edited or deleted until they are reviewed.</p>
  </div>
  <a href="#entry-form" class="rounded-full bg-gblue px-6 py-2.5 text-sm font-medium text-white hover:bg-gblue-dark">
    Add manual entry
  </a>
</div>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Entries -->
<div class="mt-6 overflow-x-auto rounded-2xl border border-gline bg-white">
  <table class="w-full min-w-[760px] text-left text-sm">
    <thead>
      <tr class="border-b border-gline text-xs font-medium uppercase tracking-wide text-ggray">
        <th class="px-6 py-3">Date</th>
        <th class="px-6 py-3">Time</th>
        <th class="px-6 py-3">Hours</th>
        <th class="px-6 py-3">Accomplishments</th>
        <th class="px-6 py-3">Status</th>
        <th class="px-6 py-3"><span class="sr-only">Actions</span></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entries as $entry): ?>
      <tr class="border-b border-gline align-top last:border-0 hover:bg-gbg">
        <td class="px-6 py-4 whitespace-nowrap"><?= e(date('D, M j', strtotime($entry['date']))) ?></td>
        <td class="px-6 py-4 font-mono text-xs whitespace-nowrap"><?= e($entry['start']) ?> – <?= e($entry['end'] ?? 'now') ?></td>
        <td class="px-6 py-4 whitespace-nowrap"><?= $entry['hours'] !== null ? e(format_hours($entry['hours'])) : '—' ?></td>
        <td class="max-w-xs px-6 py-4 text-ggray"><?= e($entry['note']) ?></td>
        <td class="px-6 py-4"><?php
          $badges = [
            'approved' => 'bg-ggreen-tint text-ggreen',
            'pending'  => 'bg-gyellow-tint text-gyellow',
            'rejected' => 'bg-gred-tint text-gred',
            'active'   => 'bg-gblue-tint text-gblue',
          ];
        ?><span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $badges[$entry['status']] ?? '' ?>"><?= e(ucfirst($entry['status'])) ?></span></td>
        <td class="px-6 py-4 whitespace-nowrap">
          <?php if (in_array($entry['status'], ['pending', 'rejected'], true)): ?>
            <a href="timesheet.php?edit=<?= (int) $entry['id'] ?>#entry-form"
               class="text-sm font-medium text-gblue hover:underline">Edit</a>
            <form method="post" action="timesheet.php" class="inline"
                  data-confirm="Delete this entry? This cannot be undone.">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
              <button type="submit" class="ml-3 text-sm font-medium text-gred hover:underline">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add / edit form -->
<section id="entry-form-section" class="mt-8 max-w-2xl rounded-2xl border border-gline bg-white p-8" aria-labelledby="form-heading">
  <h2 id="form-heading" class="text-base font-medium"><?= $editing ? 'Edit entry' : 'Add manual entry' ?></h2>
  <p class="mt-1 text-sm text-ggray">Manual entries go to your manager as pending, the same as clock-outs.</p>

  <form id="entry-form" method="post" action="timesheet.php" class="mt-6" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>

    <p id="entry-form-error" class="mb-4 hidden rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert"></p>

    <div class="grid gap-5 sm:grid-cols-3">
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="date">Date</label>
        <input id="date" name="date" type="date" required max="<?= e(date('Y-m-d')) ?>"
               value="<?= e($editing['date'] ?? '') ?>"
               class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="start">Start</label>
        <input id="start" name="start" type="time" required
               value="<?= e($editing['start'] ?? '') ?>"
               class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="end">End</label>
        <input id="end" name="end" type="time" required
               value="<?= e($editing['end'] ?? '') ?>"
               class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
      </div>
    </div>

    <label class="mb-1.5 mt-5 block text-sm font-medium" for="note">What did you accomplish?</label>
    <textarea id="note" name="note" rows="3" required minlength="30"
              placeholder="Detail the tasks completed and the milestones they map to (minimum 30 characters)…"
              class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30"><?= e($editing['note'] ?? '') ?></textarea>
    <p class="mt-1 text-xs text-ggray"><span id="note-count">0</span>/30 characters minimum</p>

    <div class="mt-6 flex items-center gap-3">
      <button type="submit"
              class="rounded-full bg-gblue px-6 py-2.5 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
        <?= $editing ? 'Save changes' : 'Add entry' ?>
      </button>
      <?php if ($editing): ?>
        <a href="timesheet.php" class="rounded-full border border-gline px-6 py-2.5 text-sm font-medium text-ggray hover:bg-gbg">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</section>

<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
