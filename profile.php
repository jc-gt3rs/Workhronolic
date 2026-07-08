<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user   = current_user();
$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'profile') {
        $name  = clean_text($_POST['name'] ?? '');
        $email = valid_email($_POST['email'] ?? '');

        if (mb_strlen($name) < 2) {
            $errors[] = 'Enter your full name (at least 2 characters).';
        }
        if ($email === null) {
            $errors[] = 'Enter a valid email address.';
        }
        if (!$errors) {
            $existing = db_one(
                'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1',
                'si',
                [$email, (int) $user['id']]
            );
            if ($existing) {
                $errors[] = 'Another account already uses that email address.';
            }
        }
        if (!$errors) {
            db_execute(
                'UPDATE users SET name = ?, email = ? WHERE id = ?',
                'ssi',
                [$name, $email, (int) $user['id']]
            );
            $_SESSION['user']['name']  = $name;
            $_SESSION['user']['email'] = $email;
            $user   = current_user();
            $notice = 'Profile updated.';
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '') {
            $errors[] = 'Enter your current password.';
        }
        if (!valid_password($new)) {
            $errors[] = 'New password must be 8+ characters with at least one letter and one number.';
        }
        if ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }
        if (!$errors) {
            $row = db_one('SELECT password_hash FROM users WHERE id = ? LIMIT 1', 'i', [(int) $user['id']]);
            if (!$row || !password_verify($current, $row['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                db_execute('UPDATE users SET password_hash = ? WHERE id = ?', 'si', [$hash, (int) $user['id']]);
                $notice = 'Password changed.';
            }
        }
    }
}

$page_title = 'Profile';
require __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-normal">Profile</h1>
<p class="mt-1 text-sm text-ggray">Manage how you appear to your team and keep your account secure.</p>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-2">

  <section class="rounded-2xl border border-gline bg-white p-8" aria-labelledby="info-heading">
    <h2 id="info-heading" class="text-base font-medium">Basic info</h2>
    <form method="post" action="profile.php" class="mt-6" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="profile">

      <label class="mb-1.5 block text-sm font-medium" for="name">Full name</label>
      <input id="name" name="name" type="text" required minlength="2" autocomplete="name"
             value="<?= e($user['name']) ?>"
             class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <label class="mb-1.5 block text-sm font-medium" for="email">Email</label>
      <input id="email" name="email" type="email" required autocomplete="email"
             value="<?= e($user['email']) ?>"
             class="mb-6 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <button type="submit"
              class="rounded-full bg-gblue px-6 py-2.5 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
        Save changes
      </button>
    </form>
  </section>

  <section class="rounded-2xl border border-gline bg-white p-8" aria-labelledby="pw-heading">
    <h2 id="pw-heading" class="text-base font-medium">Change password</h2>
    <form method="post" action="profile.php" class="mt-6" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">

      <label class="mb-1.5 block text-sm font-medium" for="current_password">Current password</label>
      <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
             class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <label class="mb-1.5 block text-sm font-medium" for="new_password">New password</label>
      <input id="new_password" name="new_password" type="password" required minlength="8" autocomplete="new-password"
             class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <label class="mb-1.5 block text-sm font-medium" for="confirm_password">Confirm new password</label>
      <input id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"
             class="mb-6 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <button type="submit"
              class="rounded-full border border-gline px-6 py-2.5 text-sm font-medium text-gblue hover:bg-gblue-tint focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
        Change password
      </button>
    </form>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
