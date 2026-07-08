<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/demo_data.php';

if (is_logged_in()) {
    redirect(is_manager() ? 'admin/dashboard.php' : 'dashboard.php');
}

/**
 * Registration paths (one company per account, always):
 *   type=employee              → join a company with its code (goes to approval)
 *   type=company, mode=create  → new company; the account becomes its OWNER
 *   type=company, mode=join    → join an existing company as a MANAGER
 *                                (goes to the owner for approval)
 */
$type = in_array($_GET['type'] ?? '', ['employee', 'company'], true) ? $_GET['type'] : null;
$mode = in_array($_GET['mode'] ?? '', ['create', 'join'], true) ? $_GET['mode'] : null;

$errors   = [];
$success  = null;   // ['heading' => ..., 'body' => ..., 'code' => generated code or null]
$old      = ['name' => '', 'email' => '', 'company_name' => '', 'company_code' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = in_array($_POST['type'] ?? '', ['employee', 'company'], true) ? $_POST['type'] : null;
    $mode = in_array($_POST['mode'] ?? '', ['create', 'join'], true) ? $_POST['mode'] : null;

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($type === null || ($type === 'company' && $mode === null)) {
        $errors[] = 'Invalid registration type.';
    } else {
        $old['name']         = clean_text($_POST['name'] ?? '');
        $email               = valid_email($_POST['email'] ?? '');
        $old['email']        = $email ?? clean_text($_POST['email'] ?? '');
        $old['company_name'] = clean_text($_POST['company_name'] ?? '');
        $old['company_code'] = strtoupper(clean_text($_POST['company_code'] ?? ''));
        $password            = $_POST['password'] ?? '';
        $confirm             = $_POST['confirm'] ?? '';

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

        $joining = $type === 'employee' || ($type === 'company' && $mode === 'join');
        if ($joining && !valid_company_code($old['company_code'])) {
            $errors[] = 'Enter a valid company code (format: AB-123XYZ).';
        }
        if ($type === 'company' && $mode === 'create' && !valid_company_name($old['company_name'])) {
            $errors[] = 'Enter a company name (2–60 characters).';
        }

        if (!$errors) {
            if ($type === 'company' && $mode === 'create') {
                // BACKEND TODO (transaction):
                //   INSERT INTO companies (name, code) VALUES (?, ?)  — retry code on collision
                //   INSERT INTO users (name, email, password_hash, role, status, company_id)
                //   VALUES (?, ?, ?, 'owner', 'active', ?)
                $code = generate_company_code($old['company_name']);
                $success = [
                    'heading' => 'Company created',
                    'body'    => 'Your account is the owner of ' . $old['company_name'] . '. Share this code so your team can join:',
                    'code'    => $code,
                ];
            } else {
                // BACKEND TODO: look up company by code (SELECT id FROM companies WHERE code = ?).
                // If none, error. Otherwise INSERT INTO users (..., role, status, company_id)
                // VALUES (..., 'employee'|'manager', 'pending', ?) — a join request the
                // company's reviewers approve. One company per account: email must be unused.
                if ($old['company_code'] !== $DEMO_COMPANY['code']) {
                    $errors[] = 'No company found with that code. Double-check it with your company.';
                } else {
                    $role = $type === 'company' ? 'manager' : 'employee';
                    $success = [
                        'heading' => 'Join request sent',
                        'body'    => 'Your request to join ' . $DEMO_COMPANY['name'] . ' as ' .
                                     ($role === 'manager' ? 'a manager' : 'an employee') .
                                     ' is awaiting approval. You can sign in once it is accepted.',
                        'code'    => null,
                    ];
                }
            }
        }
    }
}

$page_title = 'Create account';
require __DIR__ . '/includes/header.php';
?>

<div class="mx-auto mt-6 max-w-xl sm:mt-12">
  <div class="rounded-2xl border border-gline bg-white p-8 sm:p-10">
    <div class="mb-8 flex flex-col items-center text-center">
      <span class="mb-4 grid h-12 w-12 place-items-center rounded-full bg-gblue text-lg font-medium text-white">W</span>
      <h1 class="text-2xl font-normal">Create your account</h1>
      <p class="mt-1 text-sm text-ggray">One account, one company — pick how you're joining.</p>
    </div>

    <?php if ($success): ?>
      <div class="rounded-lg bg-ggreen-tint px-5 py-4 text-sm text-ggreen" role="status">
        <p class="font-medium"><?= e($success['heading']) ?></p>
        <p class="mt-1"><?= e($success['body']) ?></p>
        <?php if ($success['code']): ?>
          <p class="mt-3 inline-block rounded-lg border border-ggreen/30 bg-white px-4 py-2 font-mono text-lg tracking-widest text-gink"><?= e($success['code']) ?></p>
        <?php endif; ?>
        <p class="mt-3"><a href="login.php" class="font-medium underline">Sign in</a></p>
      </div>
    <?php else: ?>

    <?php if ($errors): ?>
      <div class="mb-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($type === null): ?>
      <!-- Step 1: registration type -->
      <div class="grid gap-4 sm:grid-cols-2">
        <a href="register.php?type=employee" class="rounded-2xl border border-gline p-6 hover:border-gblue hover:bg-gblue-tint/40">
          <p class="text-base font-medium">Employee</p>
          <p class="mt-1 text-sm text-ggray">Join your company with its company code and start tracking your hours.</p>
        </a>
        <a href="register.php?type=company" class="rounded-2xl border border-gline p-6 hover:border-gblue hover:bg-gblue-tint/40">
          <p class="text-base font-medium">Company</p>
          <p class="mt-1 text-sm text-ggray">Create a new company as its owner, or join an existing one as a manager.</p>
        </a>
      </div>

    <?php elseif ($type === 'company' && $mode === null): ?>
      <!-- Step 2 (company only): create or join -->
      <div class="grid gap-4 sm:grid-cols-2">
        <a href="register.php?type=company&amp;mode=create" class="rounded-2xl border border-gline p-6 hover:border-gblue hover:bg-gblue-tint/40">
          <p class="text-base font-medium">Create a company</p>
          <p class="mt-1 text-sm text-ggray">You become the owner and get a shareable company code for your team.</p>
        </a>
        <a href="register.php?type=company&amp;mode=join" class="rounded-2xl border border-gline p-6 hover:border-gblue hover:bg-gblue-tint/40">
          <p class="text-base font-medium">Join as manager</p>
          <p class="mt-1 text-sm text-ggray">Use the company code. The owner approves your request before you can sign in.</p>
        </a>
      </div>
      <p class="mt-6 text-center text-sm"><a href="register.php" class="font-medium text-gblue hover:underline">Back</a></p>

    <?php else: ?>
      <!-- Step 3: details form -->
      <?php
        $labels = [
            'employee'       => 'Joining as an employee — approved by your company before you can sign in.',
            'company-create' => 'Creating a new company — your account becomes its owner.',
            'company-join'   => 'Joining as a manager — approved by the company owner before you can sign in.',
        ];
        $variant = $type === 'employee' ? 'employee' : 'company-' . $mode;
      ?>
      <p class="mb-6 rounded-lg bg-gblue-tint px-4 py-3 text-sm text-gblue"><?= e($labels[$variant]) ?></p>

      <form method="post" action="register.php" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <?php if ($mode): ?><input type="hidden" name="mode" value="<?= e($mode) ?>"><?php endif; ?>

        <?php if ($variant === 'company-create'): ?>
          <label class="mb-1.5 block text-sm font-medium" for="company_name">Company name</label>
          <input id="company_name" name="company_name" type="text" required minlength="2" maxlength="60"
                 value="<?= e($old['company_name']) ?>"
                 class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
        <?php else: ?>
          <label class="mb-1.5 block text-sm font-medium" for="company_code">Company code</label>
          <input id="company_code" name="company_code" type="text" required placeholder="AB-123XYZ"
                 value="<?= e($old['company_code']) ?>"
                 class="mb-5 w-full rounded-lg border border-gline px-4 py-2.5 font-mono text-sm uppercase tracking-widest outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
        <?php endif; ?>

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
        <p class="mb-8 text-xs text-ggray">8+ characters, with at least one letter and one number. Demo code: <span class="font-mono"><?= e($DEMO_COMPANY['code']) ?></span></p>

        <div class="flex items-center justify-between">
          <a href="register.php<?= $type === 'company' ? '?type=company' : '' ?>" class="text-sm font-medium text-gblue hover:underline">Back</a>
          <button type="submit"
                  class="rounded-full bg-gblue px-6 py-2.5 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
            <?= $variant === 'company-create' ? 'Create company' : 'Send join request' ?>
          </button>
        </div>
      </form>
    <?php endif; ?>

    <p class="mt-8 border-t border-gline pt-5 text-center text-sm text-ggray">
      Already have an account? <a href="login.php" class="font-medium text-gblue hover:underline">Sign in</a>
    </p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
