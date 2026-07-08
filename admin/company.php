<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_owner();

$errors  = [];
$notice  = '';
$user = current_user();
$company = current_company();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'rename') {
        $name = clean_text($_POST['company_name'] ?? '');
        if (!valid_company_name($name)) {
            $errors[] = 'Enter a company name (2–60 characters).';
        } else {
            db_execute('UPDATE companies SET name = ? WHERE id = ?', 'si', [$name, (int) $user['company_id']]);
            $_SESSION['user']['company'] = $name;
            $notice = 'Company name updated.';
        }
    } elseif (($_POST['action'] ?? '') === 'regenerate') {
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $candidate = generate_company_code($company['name']);
            if (!db_one('SELECT id FROM companies WHERE code = ? AND id <> ? LIMIT 1', 'si', [$candidate, (int) $user['company_id']])) {
                $code = $candidate;
                break;
            }
        }
        if ($code === '') {
            $errors[] = 'Could not generate a unique company code. Try again.';
        } else {
            db_execute('UPDATE companies SET code = ? WHERE id = ?', 'si', [$code, (int) $user['company_id']]);
            $notice = 'New company code generated. The old code no longer works.';
        }
    }
    $company = current_company();
}

$base = '../';
$page_title = 'Company';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-2xl font-normal">Company settings</h1>
<p class="mt-1 text-sm text-ggray">Your company's name and the code people use to join it.</p>

<?php if ($notice): ?>
  <div class="mt-6 rounded-lg bg-ggreen-tint px-4 py-3 text-sm text-ggreen" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="mt-6 rounded-lg bg-gred-tint px-4 py-3 text-sm text-gred" role="alert">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-2">

  <section class="rounded-2xl border border-gline bg-white p-8" aria-labelledby="code-heading">
    <h2 id="code-heading" class="text-base font-medium">Company code</h2>
    <p class="mt-1 text-sm text-ggray">Share this code so employees and managers can request to join. Requests appear on the Approvals page.</p>

    <div class="mt-6 flex flex-wrap items-center gap-3">
      <p id="company-code" class="rounded-xl border border-gline bg-gbg px-5 py-3 font-mono text-2xl tracking-[0.2em]"><?= e($company['code']) ?></p>
      <button type="button" id="copy-code" data-code="<?= e($company['code']) ?>"
              class="rounded-full border border-gline px-5 py-2 text-sm font-medium text-gblue hover:bg-gblue-tint">Copy</button>
    </div>

    <form method="post" action="company.php" class="mt-6"
          data-confirm="Generate a new code? The current code will stop working immediately.">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="regenerate">
      <button type="submit" class="text-sm font-medium text-gred hover:underline">Generate a new code</button>
    </form>
  </section>

  <section class="rounded-2xl border border-gline bg-white p-8" aria-labelledby="name-heading">
    <h2 id="name-heading" class="text-base font-medium">Company name</h2>
    <form method="post" action="company.php" class="mt-6" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="rename">
      <label class="mb-1.5 block text-sm font-medium" for="company_name">Name</label>
      <input id="company_name" name="company_name" type="text" required minlength="2" maxlength="60"
             value="<?= e($company['name']) ?>"
             class="mb-6 w-full rounded-lg border border-gline px-4 py-2.5 text-sm outline-none focus:border-gblue focus:ring-2 focus:ring-gblue/30">
      <button type="submit"
              class="rounded-full bg-gblue px-6 py-2.5 text-sm font-medium text-white hover:bg-gblue-dark focus:outline-none focus:ring-2 focus:ring-gblue/40 focus:ring-offset-2">
        Save changes
      </button>
    </form>
  </section>
</div>

<script src="../assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
