<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_owner(); // managing people is owner-only

$current = current_user();
$company_id = (int) $current['company_id'];
$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if ($action === 'promote' && $id) {
            db_execute(
                "UPDATE users SET role = 'manager'
                 WHERE id = ? AND company_id = ? AND role = 'employee' AND status = 'active'",
                'ii',
                [$id, $company_id]
            );
            $notice = 'Promoted to manager.';
        } elseif ($action === 'demote' && $id) {
            db_execute(
                "UPDATE users SET role = 'employee'
                 WHERE id = ? AND company_id = ? AND role = 'manager'",
                'ii',
                [$id, $company_id]
            );
            $notice = 'Changed to employee.';
        } elseif ($action === 'remove' && $id) {
            db_execute(
                "UPDATE users SET status = 'inactive'
                 WHERE id = ? AND company_id = ? AND role <> 'owner'",
                'ii',
                [$id, $company_id]
            );
            $notice = 'Account removed from the company.';
        } elseif ($action === 'expected' && $id) {
            $hours = filter_var($_POST['expected_hours'] ?? null, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 300]]);
            if ($hours === false || $hours === null) {
                $errors[] = 'Expected hours must be a whole number between 0 and 300.';
            } else {
                db_execute(
                    "UPDATE users SET expected_hours = ?
                     WHERE id = ? AND company_id = ? AND role <> 'owner'",
                    'iii',
                    [$hours, $id, $company_id]
                );
                $notice = 'Expected monthly hours updated.';
            }
        } else {
            $errors[] = 'Invalid request.';
        }
    }
}

$company = current_company();
$users = db_all(
    "SELECT id, name, email, role, status, expected_hours, created_at
     FROM users
     WHERE company_id = ? AND status <> 'inactive'
     ORDER BY FIELD(role, 'owner', 'manager', 'employee'), status, name",
    'i',
    [$company_id]
);

$base = '../';
$page_title = 'People';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-2xl font-normal">People</h1>
<p class="mt-1 text-sm text-ggray">
  Manage roles and agreed monthly hours for everyone in <?= e($company['name'] ?? 'your company') ?>.
  New people join with your company code — accept them on the
  <a href="timesheets.php" class="font-medium text-gblue hover:underline">Approvals</a> page.
</p>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="mt-6 overflow-x-auto rounded-2xl border border-gline bg-white">
  <table class="w-full min-w-[820px] text-left text-sm">
    <thead>
      <tr class="border-b border-gline text-xs font-medium uppercase tracking-wide text-ggray">
        <th class="px-6 py-3">Name</th>
        <th class="px-6 py-3">Email</th>
        <th class="px-6 py-3">Date joined</th>
        <th class="px-6 py-3">Role</th>
        <th class="px-6 py-3">Expected hours / month</th>
        <th class="px-6 py-3">Status</th>
        <th class="px-6 py-3"><span class="sr-only">Actions</span></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr class="border-b border-gline last:border-0 hover:bg-gbg">
        <td class="px-6 py-4 font-medium whitespace-nowrap"><?= e($u['name']) ?></td>
        <td class="px-6 py-4 text-ggray"><?= e($u['email']) ?></td>
        <td class="px-6 py-4 whitespace-nowrap text-ggray">
          <time datetime="<?= e($u['created_at']) ?>" title="Joined <?= e(date('F j, Y \a\t g:i A', strtotime($u['created_at']))) ?>">
            <?= e(date('M j, Y', strtotime($u['created_at']))) ?>
          </time>
        </td>
        <td class="px-6 py-4"><?php
          $role_badge = [
            'owner'    => 'bg-gblue text-white',
            'manager'  => 'bg-gblue-tint text-gblue',
            'employee' => 'bg-gbg text-ggray',
          ];
        ?><span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $role_badge[$u['role']] ?? '' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
        <td class="px-6 py-4">
          <?php if ($u['role'] !== 'owner'): ?>
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
          <span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $u['status'] === 'active'
              ? 'bg-ggreen-tint text-ggreen' : 'bg-gyellow-tint text-gyellow' ?>"><?= $u['status'] === 'active' ? 'Active' : 'Pending approval' ?></span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <?php if ($u['role'] === 'employee' && $u['status'] === 'active'): ?>
            <form method="post" action="users.php" class="inline" data-confirm="Promote <?= e($u['name']) ?> to manager?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="promote">
              <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="text-sm font-medium text-gblue hover:underline">Promote to manager</button>
            </form>
          <?php elseif ($u['role'] === 'manager'): ?>
            <form method="post" action="users.php" class="inline" data-confirm="Change <?= e($u['name']) ?> to employee?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="demote">
              <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="text-sm font-medium text-ggray hover:underline">Change to employee</button>
            </form>
          <?php endif; ?>
          <?php if ($u['role'] !== 'owner'): ?>
            <form method="post" action="users.php" class="inline" data-confirm="Remove <?= e($u['name']) ?> from the company?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="ml-3 text-sm font-medium text-gred hover:underline">Remove</button>
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
