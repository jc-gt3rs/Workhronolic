// Workhronolic frontend behavior — no framework, just progressive enhancement.

// Live shift timer on the dashboard.
(function () {
  const timer = document.getElementById('live-timer');
  if (!timer || !timer.dataset.start) return;

  const startedAt = parseInt(timer.dataset.start, 10) * 1000;

  function tick() {
    const s = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
    const h = Math.floor(s / 3600);
    const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const sec = String(s % 60).padStart(2, '0');
    timer.textContent = h + ':' + m + ':' + sec;
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
