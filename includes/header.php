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
// settings are owner-only. Company accounts switch between the two areas
// with the workspace switch at the bottom of the sidebar.
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
} else {
    $nav = [
        ['dashboard.php', 'Dashboard'],
        ['timesheet.php', 'Timesheet'],
        ['profile.php',   'Profile'],
    ];
}

// Workspace switch targets (owner/manager only).
$switch = is_manager() ? [
    'personal' => $in_admin ? '../dashboard.php' : 'dashboard.php',
    'manage'   => $in_admin ? 'dashboard.php' : 'admin/dashboard.php',
] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? APP_NAME) ?> · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['Manrope', 'system-ui', 'sans-serif'],
        mono: ['"DM Mono"', 'monospace'],
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
        card: '0 18px 45px -28px rgba(15, 23, 42, .38), 0 8px 18px -14px rgba(15, 23, 42, .2)',
      },
    },
  },
};
</script>
<style>
  html { -webkit-font-smoothing: antialiased; }
  body { background-color: #f8f9fa; }
  header { box-shadow: 0 1px 0 rgba(15, 23, 42, .04), 0 8px 22px -20px rgba(15, 23, 42, .32); }
  aside { background: linear-gradient(180deg, rgba(255,255,255,.75), rgba(248,250,252,.6)); }
  h1 { font-weight: 700 !important; letter-spacing: -.035em; }
  h2 { letter-spacing: -.018em; }
  main .rounded-2xl.border {
    border-color: rgba(218, 220, 224, .94);
    box-shadow: 0 18px 42px -30px rgba(15, 23, 42, .36), 0 5px 12px -10px rgba(15, 23, 42, .14);
  }
  main input, main textarea, main select {
    border-color: #dadce0 !important;
    background-color: rgba(255, 255, 255, .9);
    transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
  }
  main input:hover, main textarea:hover, main select:hover { border-color: #bdc1c6 !important; }
  main input:focus, main textarea:focus, main select:focus {
    border-color: #1a73e8 !important;
    box-shadow: 0 0 0 4px rgba(26, 115, 232, .12) !important;
  }
  button, a { transition: color .18s ease, background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
  button[class*="bg-gblue"] { box-shadow: 0 10px 18px -12px rgba(26, 115, 232, .7); }
  button[class*="bg-gblue"]:hover { box-shadow: 0 13px 22px -12px rgba(26, 115, 232, .62); transform: translateY(-1px); }
  a.rounded-full[class*="bg-gblue"] { box-shadow: 0 10px 18px -12px rgba(26, 115, 232, .7); }
  table thead { background: rgba(248, 250, 252, .72); }
  table tbody tr { transition: background-color .16s ease; }
  [role="alert"], [role="status"] { border: 1px solid currentColor; border-color: color-mix(in srgb, currentColor 20%, transparent); }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; }
  }
</style>
</head>
<body class="min-h-screen bg-gbg font-sans text-gink">

<header class="sticky top-0 z-20 border-b border-gline bg-white/85 backdrop-blur-xl">
  <div class="flex h-[4.5rem] items-center gap-6 px-4 sm:px-6">
    <a href="<?= e($base) ?>dashboard.php" class="flex items-center gap-2.5 shrink-0">
      <span class="grid h-9 w-9 place-items-center rounded-xl bg-gblue text-sm font-extrabold text-white shadow-lg shadow-gblue/25">W</span>
      <span class="text-xl font-semibold tracking-[-0.04em] text-ggray">Work<span class="font-extrabold text-gink">hronolic</span></span>
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
    <?php if ($switch): ?>
      <a href="<?= e($in_admin ? $switch['personal'] : $switch['manage']) ?>"
         class="ml-auto whitespace-nowrap rounded-full border border-gline px-4 py-1.5 text-sm font-medium text-gblue">
        <?= $in_admin ? 'My time' : 'Manage' ?></a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</header>

<div class="flex w-full">
  <?php if ($user): ?>
  <aside class="hidden w-64 shrink-0 border-r border-gline sm:block" aria-label="Sidebar">
    <div class="sticky top-[4.5rem] flex h-[calc(100vh-4.5rem)] flex-col px-3 py-6">
      <?php if (!empty($user['company'])): ?>
      <div class="mb-5 border-b border-gline px-4 pb-5">
        <p class="text-xs font-medium uppercase tracking-wide text-ggray">Company</p>
        <p class="mt-0.5 truncate text-sm font-medium" title="<?= e($user['company']) ?>"><?= e($user['company']) ?></p>
      </div>
      <?php endif; ?>
      <nav class="flex flex-col gap-1" aria-label="Main">
        <p class="mb-1 px-4 text-xs font-medium uppercase tracking-wide text-ggray"><?= $in_admin ? 'Manage' : 'My time' ?></p>
        <?php foreach ($nav as [$href, $label]): $active = !str_contains($href, '/') && $self === $href; ?>
          <a href="<?= e($href) ?>"
             class="rounded-full px-4 py-2 text-sm font-medium <?= $active
                ? 'bg-gblue-tint text-gblue'
                : 'text-ggray hover:bg-white hover:text-gink' ?>"
             <?= $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>

      <?php if ($switch): ?>
      <!-- Workspace switch: company accounts flip between their own time
           tracking and the management area. -->
      <div class="mt-auto pt-6">
        <div class="flex rounded-full border border-gline bg-white p-1 text-sm font-medium" role="group" aria-label="Workspace">
          <a href="<?= e($switch['personal']) ?>"
             class="flex-1 rounded-full px-3 py-1.5 text-center <?= !$in_admin ? 'bg-gblue-tint text-gblue' : 'text-ggray hover:text-gink' ?>"
             <?= !$in_admin ? 'aria-current="true"' : '' ?>>My time</a>
          <a href="<?= e($switch['manage']) ?>"
             class="flex-1 rounded-full px-3 py-1.5 text-center <?= $in_admin ? 'bg-gblue-tint text-gblue' : 'text-ggray hover:text-gink' ?>"
             <?= $in_admin ? 'aria-current="true"' : '' ?>>Manage</a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </aside>
  <?php endif; ?>

<main class="min-w-0 max-w-[82rem] flex-1 px-4 py-8 sm:px-8 lg:px-10">
