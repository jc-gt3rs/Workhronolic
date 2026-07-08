<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors  = [];
$success = false;
$old     = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['name']  = clean_text($_POST['name'] ?? '');
        $email        = valid_email($_POST['email'] ?? '');
        $old['email'] = $email ?? clean_text($_POST['email'] ?? '');
        $password     = $_POST['password'] ?? '';
        $confirm      = $_POST['confirm'] ?? '';

        if (mb_strlen($old['name']) < 2) {
            $errors[] = 'Enter your full name (at least 2 characters).';
        }
        if ($email === null) {
            $errors[] = 'Enter a valid email address.';
        }
        if (!valid_password($password)) {
            $errors[] = 'Password must be 8+ characters with at least one letter and one number.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            // BACKEND TODO: check email uniqueness, then
            // INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'worker')
            // using password_hash($password, PASSWORD_DEFAULT) and a prepared statement.
            $success = true;
        }
    }
}

$page_title = 'Create account';
require __DIR__ . '/includes/header.php';
?>

<div class="mx-auto mt-6 max-w-md sm:mt-16">
  <div class="rounded-2xl border border-gline bg-white p-8 sm:p-10">
    <div class="mb-8 flex flex-col items-center text-center">
      <span class="mb-4 grid h-12 w-12 place-items-center rounded-full bg-gblue text-lg font-medium text-white">W</span>
      <h1 class="text-2xl font-normal">Create your account</h1>
      <p class="mt-1 text-sm text-ggray">Track and justify your hours on <?= e(APP_NAME) ?></p>
    </div>

    <?php if ($success): ?>
      <div class="mb-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status">
        Account created. <a href="login.php" class="font-medium underline">Sign in</a> to get started.
      </div>
    <?php elseif ($errors): ?>
      <div class="mb-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="register.php" novalidate>
      <?= csrf_field() ?>

      <label class="mb-1.5 block text-sm font-medium" for="name">Full name</label>
      <input id="name" name="name" type="text" required minlength="2" autocomplete="name"
             value="<?= e($old['name']) ?>"
             class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <label class="mb-1.5 block text-sm font-medium" for="email">Email</label>
      <input id="email" name="email" type="email" required autocomplete="email"
             value="<?= e($old['email']) ?>"
             class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <div class="mb-2 grid gap-5 sm:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-sm font-medium" for="password">Password</label>
          <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                 class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium" for="confirm">Confirm</label>
          <input id="confirm" name="confirm" type="password" required minlength="8" autocomplete="new-password"
                 class="w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
        </div>
      </div>
      <p class="mb-8 text-xs text-ggray">8+ characters, with at least one letter and one number.</p>

      <div class="flex items-center justify-between">
        <a href="login.php" class="text-sm font-medium text-gblue hover:underline">Sign in instead</a>
        <button type="submit"
                class="rounded-full bg-gblue px-6 py-2.5 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
          Create account
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
