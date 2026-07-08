<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/demo_data.php';

if (is_logged_in()) {
    redirect(is_manager() ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email    = valid_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === null) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($password === '') {
            $errors[] = 'Enter your password.';
        }

        if (!$errors) {
            // BACKEND TODO: SELECT id, name, email, role, status, company_id FROM users
            // WHERE email = ? (prepared statement), then password_verify().
            // DEMO: any password signs in a known demo email.
            $found = null;
            foreach ($DEMO_USERS as $u) {
                if ($u['email'] === $email) { $found = $u; break; }
            }
            if ($found && $found['status'] === 'pending') {
                $errors[] = 'Your join request is still awaiting approval by the company. Try again once it is accepted.';
            } elseif ($found) {
                session_regenerate_id(true); // prevent session fixation
                // BACKEND TODO: fetch the company name with a JOIN on companies.
                $_SESSION['user'] = [
                    'id'         => $found['id'],
                    'name'       => $found['name'],
                    'email'      => $found['email'],
                    'role'       => $found['role'],
                    'company_id' => $found['company_id'],
                    'company'    => $DEMO_COMPANY['name'],
                ];
                redirect(in_array($found['role'], ['owner', 'manager'], true) ? 'admin/dashboard.php' : 'dashboard.php');
            } else {
                $errors[] = 'Email or password is incorrect.';
            }
        }
    }
    $email = $email ?? '';
}

$page_title = 'Sign in';
require __DIR__ . '/includes/header.php';
?>

<div class="mx-auto mt-6 max-w-md sm:mt-16">
  <div class="rounded-2xl border border-gline bg-white p-8 sm:p-10">
    <div class="mb-8 flex flex-col items-center text-center">
      <span class="mb-4 grid h-12 w-12 place-items-center rounded-full bg-gblue text-lg font-medium text-white">W</span>
      <h1 class="text-2xl font-normal">Sign in</h1>
      <p class="mt-1 text-sm text-ggray">to continue to <?= e(APP_NAME) ?></p>
    </div>

    <?php if ($errors): ?>
      <div class="mb-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
      <?= csrf_field() ?>

      <label class="mb-1.5 block text-sm font-medium" for="email">Email</label>
      <input id="email" name="email" type="email" required autocomplete="email"
             value="<?= e($email) ?>"
             class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <label class="mb-1.5 block text-sm font-medium" for="password">Password</label>
      <input id="password" name="password" type="password" required autocomplete="current-password"
             class="mb-2 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">

      <p class="mb-8 text-xs text-ggray">
        Demo accounts (any password): <span class="font-mono">dathan@startup.io</span> (owner),
        <span class="font-mono">mia@startup.io</span> (manager),
        <span class="font-mono">jc@startup.io</span> (employee),
        <span class="font-mono">paolo@startup.io</span> (pending approval).
      </p>

      <div class="flex items-center justify-between">
        <a href="register.php" class="text-sm font-medium text-gblue hover:underline">Create account</a>
        <button type="submit"
                class="rounded-full bg-gblue px-6 py-2.5 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
          Sign in
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
