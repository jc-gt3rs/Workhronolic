// Workhronolic frontend behavior — no framework, just progressive enhancement.

// Live shift timer on the dashboard. Break time is excluded from the
// worked total: while on a break the main timer freezes and the break
// counter ticks instead.
(function () {
  const timer = document.getElementById('live-timer');
  if (!timer || !timer.dataset.start) return;

  const breakTimer = document.getElementById('break-timer');
  const startedAt = parseInt(timer.dataset.start, 10) * 1000;
  const breakTotal = (parseInt(timer.dataset.breakTotal, 10) || 0) * 1000;
  const breakStart = timer.dataset.breakStart
    ? parseInt(timer.dataset.breakStart, 10) * 1000
    : null;

  function fmt(s) {
    const h = Math.floor(s / 3600);
    const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const sec = String(s % 60).padStart(2, '0');
    return h + ':' + m + ':' + sec;
  }

  const currentBreak = document.getElementById('current-break');

  function tick() {
    const now = Date.now();
    const openBreakMs = breakStart ? Math.max(0, now - breakStart) : 0;
    const breakMs = breakTotal + openBreakMs;
    const workedMs = Math.max(0, now - startedAt - breakMs);
    timer.textContent = fmt(Math.floor(workedMs / 1000));
    if (currentBreak) currentBreak.textContent = fmt(Math.floor(openBreakMs / 1000));
    if (breakTimer) {
      const bs = Math.floor(breakMs / 1000);
      breakTimer.textContent = Math.floor(bs / 3600) + 'h ' + String(Math.floor((bs % 3600) / 60)).padStart(2, '0') + 'm';
    }
  }

  tick();
  setInterval(tick, 1000);
})();

// Live character counter for the justification textarea.
(function () {
  const note = document.getElementById('note');
  const count = document.getElementById('note-count');
  if (!note || !count) return;

  function update() {
    const len = note.value.trim().length;
    count.textContent = len;
    count.parentElement.classList.toggle('text-gred', len > 0 && len < 30);
  }

  note.addEventListener('input', update);
  update();
})();

// Copy the company code to the clipboard.
(function () {
  const btn = document.getElementById('copy-code');
  if (!btn) return;
  btn.addEventListener('click', function () {
    navigator.clipboard.writeText(btn.dataset.code).then(function () {
      const label = btn.textContent;
      btn.textContent = 'Copied';
      setTimeout(function () { btn.textContent = label; }, 1500);
    });
  });
})();

// Confirm before any destructive form submit (delete / reject).
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (!window.confirm(form.dataset.confirm)) e.preventDefault();
  });
});

// Client-side guard: end time must be after start time on the entry form.
(function () {
  const form = document.getElementById('entry-form');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    const start = form.querySelector('[name="start"]');
    const end = form.querySelector('[name="end"]');
    const msg = document.getElementById('entry-form-error');
    if (start && end && start.value && end.value && end.value <= start.value) {
      e.preventDefault();
      if (msg) {
        msg.textContent = 'End time must be after the start time.';
        msg.classList.remove('hidden');
      }
    }
  });
})();
