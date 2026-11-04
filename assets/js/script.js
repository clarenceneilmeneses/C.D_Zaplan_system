// ===== UI-only helpers. Replace with real back-end calls later. =====

// Mobile nav (public header)
function toggleMenu() {
  document.getElementById('mobileMenu').classList.toggle('hidden');
}

// Show / hide password
function togglePassword(btn) {
  const input = btn.parentElement.querySelector('input');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.querySelector('iconify-icon').setAttribute('icon', show ? 'lucide:eye-off' : 'lucide:eye');
}

// Demo login routing: admin email goes to admin.php, everyone else to dashboard.php
function handleLogin(event) {
  event.preventDefault();
  const email = event.target.querySelector('input[type="email"]').value.trim().toLowerCase();
  window.location.href = (email === 'carlito.zaplan@gmail.com') ? 'admin.php' : 'dashboard.php';
}

function handleRegister(event) {
  event.preventDefault();
  const pw = event.target.querySelectorAll('input[type="password"]');
  if (pw[0].value !== pw[1].value) { alert('Passwords do not match.'); return; }
  window.location.href = 'login.php';
}

// Modal (client dashboard)
function openModal(id)  { document.getElementById(id).classList.add('is-open'); }
function closeModal(id) { document.getElementById(id).classList.remove('is-open'); }

// Placeholders
function handleAddUser()       { alert('Add User form goes here.'); }
function handleExportPayroll() { alert('Payroll export goes here.'); }

// ===== Inquiry modal (public) =====
function openInquiry(item) {
  const m = document.getElementById('inquiryModal');
  document.getElementById('inquiryItem').value = item || 'General Inquiry';
  document.getElementById('inquiryFormView').classList.remove('hidden');
  document.getElementById('inquirySuccess').classList.add('hidden');
  m.classList.add('is-open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('inquiryName').focus(), 280);
}
function closeInquiry() {
  const m = document.getElementById('inquiryModal');
  if (!m) return;
  m.classList.remove('is-open');
  document.body.style.overflow = '';
  setTimeout(() => document.getElementById('inquiryForm').reset(), 300);
}
function submitInquiry(event) {
  event.preventDefault();
  document.getElementById('inquirySuccessName').textContent  = document.getElementById('inquiryName').value.trim().split(' ')[0];
  document.getElementById('inquirySuccessEmail').textContent = document.getElementById('inquiryEmail').value.trim();
  document.getElementById('inquiryFormView').classList.add('hidden');
  document.getElementById('inquirySuccess').classList.remove('hidden');
}
// Escape closes any open overlay
document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  document.querySelectorAll('.overlay.is-open').forEach(el => {
    if (el.id === 'inquiryModal') closeInquiry(); else el.classList.remove('is-open');
  });
  document.body.style.overflow = '';
});

// ===== Client portal: Our Fleet (category filter, search, inquire) =====
(function () {
  var root = document.getElementById('fltRoot');
  if (!root) return;
  var cards = Array.prototype.slice.call(root.querySelectorAll('.flt-card'));
  var filters = root.querySelectorAll('[data-flt-filter]');
  var search = document.getElementById('fltSearch');
  var empty = document.getElementById('fltEmpty');
  var grid = document.getElementById('fltGrid');
  var state = { filter: 'all', q: '' };

  function apply() {
    var q = state.q.trim().toLowerCase(), shown = 0;
    cards.forEach(function (c) {
      var ok = (state.filter === 'all' || c.getAttribute('data-category') === state.filter) &&
               (!q || c.getAttribute('data-search').indexOf(q) !== -1);
      c.hidden = !ok;
      if (ok) shown++;
    });
    empty.hidden = shown > 0;
    grid.hidden = shown === 0;
  }

  function setFilter(f) {
    state.filter = f;
    filters.forEach(function (b) {
      var on = b.getAttribute('data-flt-filter') === f;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    apply();
  }

  filters.forEach(function (b) { b.addEventListener('click', function () { setFilter(b.getAttribute('data-flt-filter')); }); });
  search.addEventListener('input', function () { state.q = search.value; apply(); });
  document.getElementById('fltReset').addEventListener('click', function () { search.value = ''; state.q = ''; setFilter('all'); });

  root.addEventListener('click', function (e) {
    // Booked / unavailable: open the inquiry form (not the booking form) for this unit
    var ask = e.target.closest('[data-flt-ask]');
    if (ask) {
      openInquiry(ask.getAttribute('data-item'));
      return;
    }
    // Available: open the booking form with this equipment already selected
    var btn = e.target.closest('[data-flt-inquire]');
    if (!btn) return;
    var sel = document.getElementById('bkEquipment');
    if (sel) {
      var want = btn.getAttribute('data-book');
      Array.prototype.forEach.call(sel.options, function (o) { if (o.text.trim() === want) sel.value = o.value || o.text; });
    }
    openModal('bookingModal');
  });
})();

