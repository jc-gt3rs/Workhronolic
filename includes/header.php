<?php
/**
 * Shared page header + top navigation.
 * Expects: $page_title (string), optional $base ('' or '../' for /admin pages).
 */
$base = $base ?? '';
$user = current_user();
$self = basename($_SERVER['PHP_SELF']);
$in_admin = str_contains($_SERVER['PHP_SELF'], '/admin/');

// Everyone logs their own time, so every role gets the personal pages.
// Managers and owners also get the management area; People and Company
// settings are owner-only.
if ($in_admin) {
    $nav = [
        ['dashboard.php',  'Overview'],
        ['timesheets.php', 'Approvals'],
        ['reports.php',    'Reports'],
    ];
    if (is_owner()) {
        $nav[] = ['users.php',   'People'];
        $nav[] = ['company.php', 'Company'];
    }
    $nav[] = ['../dashboard.php', 'My time'];
} else {
    $nav = [
        ['dashboard.php', 'Dashboard'],
        ['timesheet.php', 'Timesheet'],
        ['profile.php',   'Profile'],
    ];
    if (is_manager()) {
        $nav[] = ['admin/dashboard.php', 'Manage'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? APP_NAME) ?> · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['Roboto', 'system-ui', 'sans-serif'],
        mono: ['"Roboto Mono"', 'monospace'],
      },
      colors: {
        gblue:   { DEFAULT: '#1a73e8', dark: '#1765cc', tint: '#e8f0fe' },
        ggreen:  { DEFAULT: '#188038', tint: '#e6f4ea' },
        gred:    { DEFAULT: '#d93025', tint: '#fce8e6' },
        gyellow: { DEFAULT: '#b06000', tint: '#fef7e0' },
        gink:    '#202124',
        ggray:   '#5f6368',
        gline:   '#dadce0',
        gbg:     '#f8f9fa',
      },
      boxShadow: {
        card: '0 1px 2px 0 rgba(60,64,67,.3), 0 1px 3px 1px rgba(60,64,67,.15)',
      },
    },
  },
};
</script>
<style>
  html { -webkit-font-smoothing: antialiased; }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; }
  }
</style>
</head>
<body class="min-h-screen bg-gbg font-sans text-gink">

<header class="sticky top-0 z-20 border-b border-gline bg-white">
  <div class="flex h-16 items-center gap-6 px-4 sm:px-6">
    <a href="<?= e($base) ?>dashboard.php" class="flex items-center gap-2.5 shrink-0">
      <span class="grid h-8 w-8 place-items-center rounded-full bg-gblue text-sm font-medium text-white">W</span>
      <span class="text-xl tracking-tight text-ggray">Work<span class="font-medium text-gink">hronolic</span></span>
      <?php if ($in_admin): ?>
        <span class="ml-1 rounded-full bg-gblue-tint px-2.5 py-0.5 text-xs font-medium text-gblue">Manage</span>
      <?php endif; ?>
    </a>

    <?php if ($user): ?>
    <div class="ml-auto flex items-center gap-3">
      <span class="hidden text-sm text-ggray md:block">
        <?= e($user['name']) ?>
        <span class="ml-1 rounded-full bg-gbg px-2 py-0.5 text-xs font-medium text-ggray"><?= e(ucfirst($user['role'])) ?></span>
      </span>
      <span class="grid h-8 w-8 place-items-center rounded-full bg-ggreen text-sm font-medium text-white"
            title="<?= e($user['email']) ?>"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
      <a href="<?= e($base) ?>logout.php"
         class="rounded-full border border-gline px-4 py-1.5 text-sm font-medium text-gblue hover:bg-gblue-tint">Sign out</a>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($user): ?>
  <!-- Mobile nav: the sidebar collapses to a scrollable pill row -->
  <nav class="flex gap-1 overflow-x-auto border-t border-gline px-2 py-1.5 sm:hidden" aria-label="Main mobile">
    <?php foreach ($nav as [$href, $label]): $active = !str_contains($href, '/') && $self === $href; ?>
      <a href="<?= e($href) ?>"
         class="whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium <?= $active
            ? 'bg-gblue-tint text-gblue' : 'text-ggray' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>
</header>

<div class="flex w-full">
  <?php if ($user): ?>
  <aside class="hidden w-60 shrink-0 border-r border-gline sm:block" aria-label="Sidebar">
    <nav class="sticky top-16 flex flex-col gap-1 px-3 py-6" aria-label="Main">
      <p class="mb-1 px-4 text-xs font-medium uppercase tracking-wide text-ggray"><?= $in_admin ? 'Manage' : 'My time' ?></p>
      <?php foreach ($nav as [$href, $label]): $active = !str_contains($href, '/') && $self === $href; ?>
        <a href="<?= e($href) ?>"
           class="rounded-full px-4 py-2 text-sm font-medium <?= $active
              ? 'bg-gblue-tint text-gblue'
              : 'text-ggray hover:bg-white hover:text-gink' ?>"
           <?= $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <?php endif; ?>

<main class="min-w-0 max-w-6xl flex-1 px-4 py-8 sm:px-8">
