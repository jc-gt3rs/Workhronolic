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
        $action = $_POST['action'] ?? '';
        $id     = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if ($action === 'toggle' && $id) {
            // BACKEND TODO: UPDATE users SET active = NOT active WHERE id = ? AND id != {current admin}
            $notice = 'Account status updated.';
        } elseif ($action === 'expected' && $id) {
            $hours = filter_var($_POST['expected_hours'] ?? null, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 300]]);
            if ($hours === false || $hours === null) {
                $errors[] = 'Expected hours must be a whole number between 0 and 300.';
            } else {
                // BACKEND TODO: UPDATE users SET expected_hours = ? WHERE id = ?
                $notice = 'Expected monthly hours updated.';
            }
        } else {
            $errors[] = 'Invalid request.';
        }
    }
}

$base = '../';
$page_title = 'People';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-2xl font-normal">People</h1>
<p class="mt-1 text-sm text-ggray">Manage team accounts and each worker's agreed monthly hours.</p>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="mt-6 overflow-x-auto rounded-2xl border border-gline bg-white">
  <table class="w-full min-w-[760px] text-left text-sm">
    <thead>
      <tr class="border-b border-gline text-xs font-medium uppercase tracking-wide text-ggray">
        <th class="px-6 py-3">Name</th>
        <th class="px-6 py-3">Email</th>
        <th class="px-6 py-3">Role</th>
        <th class="px-6 py-3">Expected hours / month</th>
        <th class="px-6 py-3">Status</th>
        <th class="px-6 py-3"><span class="sr-only">Actions</span></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($DEMO_USERS as $u): ?>
      <tr class="border-b border-gline last:border-0 hover:bg-gbg">
        <td class="px-6 py-4 font-medium whitespace-nowrap"><?= e($u['name']) ?></td>
        <td class="px-6 py-4 text-ggray"><?= e($u['email']) ?></td>
        <td class="px-6 py-4">
          <span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $u['role'] === 'admin'
              ? 'bg-gblue-tint text-gblue' : 'bg-gbg text-ggray' ?>"><?= e(ucfirst($u['role'])) ?></span>
        </td>
        <td class="px-6 py-4">
          <?php if ($u['role'] === 'worker'): ?>
          <form method="post" action="users.php" class="flex items-center gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="expected">
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <label class="sr-only" for="hours-<?= (int) $u['id'] ?>">Expected hours for <?= e($u['name']) ?></label>
            <input id="hours-<?= (int) $u['id'] ?>" name="expected_hours" type="number" min="0" max="300"
                   value="<?= (int) $u['expected_hours'] ?>"
                   class="w-20 rounded-lg border border-gline px-3 py-1.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
            <button type="submit" class="text-sm font-medium text-gblue hover:underline">Save</button>
          </form>
          <?php else: ?>
            <span class="text-ggray">—</span>
          <?php endif; ?>
        </td>
        <td class="px-6 py-4">
          <span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $u['active']
              ? 'bg-ggreen-tint text-ggreen' : 'bg-gred-tint text-gred' ?>"><?= $u['active'] ? 'Active' : 'Deactivated' ?></span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <?php if ($u['role'] === 'worker'): ?>
          <form method="post" action="users.php" class="inline"
                data-confirm="<?= $u['active'] ? 'Deactivate' : 'Reactivate' ?> <?= e($u['name']) ?>?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <button type="submit" class="text-sm font-medium <?= $u['active'] ? 'text-gred' : 'text-ggreen' ?> hover:underline">
              <?= $u['active'] ? 'Deactivate' : 'Reactivate' ?>
            </button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script src="../assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
