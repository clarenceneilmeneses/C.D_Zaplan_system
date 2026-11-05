// ===== Shared UI helpers =====

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

// Register form: catch mismatched passwords before submitting (the server checks again)
function checkPasswordsMatch(form) {
  const pw = form.querySelectorAll('input[type="password"], input[name^="password"]');
  if (pw.length >= 2 && pw[pw.length - 2].value !== pw[pw.length - 1].value) {
    alert('Passwords do not match.');
    return false;
  }
  return true;
}

// Simple show/hide modals (portal pages)
function openModal(id)  { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }

// ===== Portal top bar =====
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

// Popovers (date range, notifications): one open at a time, closed by clicking outside or Esc
function togglePanel(id, force) {
  const root = document.getElementById(id);
  const panel = root.querySelector('.popover');
  const open = force ?? panel.classList.contains('hidden');
  document.querySelectorAll('.range, .notif').forEach(el => { if (el !== root) closePopover(el); });
  panel.classList.toggle('hidden', !open);
  root.querySelector('[aria-expanded]').setAttribute('aria-expanded', open);
  return open;
}
function closePopover(root) {
  root.querySelector('.popover')?.classList.add('hidden');
  root.querySelector('[aria-expanded]')?.setAttribute('aria-expanded', 'false');
}
document.addEventListener('click', e => {
  document.querySelectorAll('.range, .notif').forEach(root => { if (!root.contains(e.target)) closePopover(root); });
});

// Notifications: badge + dropdown, refreshed every minute from api/notifications.php
function renderNotif(n) {   // same markup as notification_item() in includes/notifications.php
  return `<a href="${esc(n.href)}" class="notif-item${n.unread ? ' is-unread' : ''}">` +
    `<span class="notif-item__icon notif-item__icon--${esc(n.kind)}"><iconify-icon icon="${esc(n.icon)}" width="16"></iconify-icon></span>` +
    `<span class="notif-item__text"><span class="notif-item__title">${esc(n.title)}</span>` +
    (n.body ? `<span class="notif-item__body">${esc(n.body)}</span>` : '') +
    `<span class="notif-item__ago">${esc(n.ago)}</span></span></a>`;
}
function applyNotifications(data) {
  const badge = document.getElementById('notifBadge');
  if (!badge || !data || !data.ok) return;
  badge.textContent = data.unread > 99 ? '99+' : data.unread;
  badge.classList.toggle('hidden', !data.unread);
  document.getElementById('notifReadAll').classList.toggle('hidden', !data.unread);
  document.querySelector('.notif__btn').setAttribute('aria-label', `Notifications, ${data.unread} unread`);
  document.getElementById('notifList').innerHTML = data.items.length
    ? data.items.map(renderNotif).join('') : '<div class="notif__empty">No notifications yet.</div>';
}
async function refreshNotifications(options) {
  try {
    const res = await fetch('api/notifications.php', options);
    applyNotifications(await res.json());
  } catch (e) { /* offline: keep what is shown */ }
}
function toggleNotifications() {
  if (togglePanel('notif')) refreshNotifications();
}
function markAllNotificationsRead() {
  const body = new FormData();
  body.append('action', 'read_all');
  body.append('csrf', csrfToken());
  refreshNotifications({ method: 'POST', body });
}
if (document.getElementById('notif')) {
  setInterval(() => { if (!document.hidden) refreshNotifications(); }, 60000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshNotifications(); });
}

// Global search: live results under the top-bar search box (api/search.php); Enter opens search.php
(function () {
  const form = document.getElementById('globalSearch');
  if (!form) return;
  const input = form.querySelector('input');
  const box = document.getElementById('searchResults');
  let timer, ctrl, active = -1;

  const links = () => [...box.querySelectorAll('a')];
  const close = () => { box.classList.add('hidden'); input.setAttribute('aria-expanded', 'false'); active = -1; };
  const open = () => { box.classList.remove('hidden'); input.setAttribute('aria-expanded', 'true'); };

  async function lookup(q) {
    if (ctrl) ctrl.abort();
    ctrl = new AbortController();
    try {
      const res = await fetch('api/search.php?q=' + encodeURIComponent(q), { signal: ctrl.signal });
      const data = await res.json();
      if (input.value.trim() !== q) return;
      active = -1;
      box.innerHTML = data.groups && data.groups.length
        ? data.groups.map(g => `<div class="search-results__group"><iconify-icon icon="${esc(g.icon)}" width="13"></iconify-icon>${esc(g.label)}</div>` +
            g.items.map(it => `<a href="${esc(it.link)}" class="search-results__item" role="option">` +
              `<span class="result__text"><span class="result__title">${esc(it.title)}</span><span class="result__meta">${esc(it.meta)}</span></span>${it.pill}</a>`).join('')
          ).join('') + `<a href="search.php?q=${encodeURIComponent(q)}" class="search-results__all">See all results for “${esc(q)}”</a>`
        : `<div class="search-results__empty">No matches for “${esc(q)}”.</div>`;
      open();
    } catch (e) { if (e.name !== 'AbortError') close(); }
  }

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { close(); return; }
    timer = setTimeout(() => lookup(q), 250);
  });
  input.addEventListener('focus', () => { if (input.value.trim().length >= 2 && box.innerHTML) open(); });
  input.addEventListener('keydown', e => {
    const items = links();
    if (box.classList.contains('hidden') || !items.length) return;
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
      items.forEach((a, i) => a.classList.toggle('is-active', i === active));
      items[active].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter' && active >= 0) {
      e.preventDefault();
      location.href = items[active].href;
    } else if (e.key === 'Escape') {
      close();
    }
  });
  form.addEventListener('submit', e => { if (input.value.trim().length < 2) e.preventDefault(); });
  document.addEventListener('click', e => { if (!form.contains(e.target)) close(); });
})();

// Phones: tables turn into stacked cards; each cell shows its column name (from the table header)
function labelTableCells() {
  document.querySelectorAll('table.table').forEach(table => {
    const heads = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
    table.querySelectorAll('tbody tr').forEach(tr => {
      [...tr.children].forEach((td, i) => { if (!td.hasAttribute('data-label')) td.dataset.label = heads[i] || ''; });
    });
  });
}
document.addEventListener('DOMContentLoaded', () => {
  labelTableCells();
  document.querySelector('.mobile-tabs .tab.is-active')?.scrollIntoView({ inline: 'center', block: 'nearest' });   // keep the current page's tab visible
});

// Reviews carousel (homepage): scroll one card at a time, disable arrows at the ends
function scrollReviews(dir) {
  const track = document.getElementById('reviewTrack');
  const card = track.firstElementChild;
  const gap = parseFloat(getComputedStyle(track).columnGap) || 24;
  track.scrollBy({ left: dir * (card.offsetWidth + gap), behavior: 'smooth' });
}
function updateReviewArrows() {
  const t = document.getElementById('reviewTrack');
  if (!t) return;
  document.getElementById('reviewPrev').disabled = t.scrollLeft <= 4;
  document.getElementById('reviewNext').disabled = t.scrollLeft + t.clientWidth >= t.scrollWidth - 4;
}
window.addEventListener('load', () => {
  const t = document.getElementById('reviewTrack');
  if (!t) return;
  t.addEventListener('scroll', updateReviewArrows);
  window.addEventListener('resize', updateReviewArrows);
  updateReviewArrows();
});

// ===== Inquiry modal (public) — saved through api/inquiry.php =====
/**
 * openInquiry('Backhoe')
 * openInquiry('Backhoe · 5 days', { type: 'Quote & Pricing Request', message: '...', estimate: {type_id, days, lat, lng, km, total} })
 */
function openInquiry(item, opts = {}) {
  const m = document.getElementById('inquiryModal');
  const form = document.getElementById('inquiryForm');
  document.getElementById('inquiryItem').value = item || 'General Inquiry';
  document.getElementById('inquiryFormView').classList.remove('hidden');
  document.getElementById('inquirySuccess').classList.add('hidden');
  document.getElementById('inquiryError').classList.add('hidden');
  if (opts.type) document.getElementById('inquiryType').value = opts.type;
  if (opts.message) document.getElementById('inquiryMessage').value = opts.message;

  const est = opts.estimate || {};
  ['type_id', 'days', 'lat', 'lng', 'km'].forEach(k => { form.elements[k].value = est[k] ?? ''; });
  const box = document.getElementById('inquiryEstimate');
  box.classList.toggle('hidden', !opts.estimate);
  if (opts.estimate) {
    box.innerHTML = '<div class="notice__row"><iconify-icon icon="lucide:map-pin" width="14" class="notice__icon"></iconify-icon>' +
      '<span>Your pinned site and estimate of <strong>' + peso(est.total) + '</strong> are attached to this request.</span></div>';
  }

  m.classList.add('is-open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById(document.getElementById('inquiryName').value ? 'inquiryMessage' : 'inquiryName').focus(), 280);
}
function closeInquiry() {
  const m = document.getElementById('inquiryModal');
  if (!m) return;
  m.classList.remove('is-open');
  document.body.style.overflow = '';
  setTimeout(() => {
    const form = document.getElementById('inquiryForm');
    const keep = { full_name: form.elements.full_name.value, email: form.elements.email.value, phone: form.elements.phone.value };
    form.reset();
    Object.entries(keep).forEach(([k, v]) => { form.elements[k].value = v; });   // keep contact details for the next inquiry
  }, 300);
}
async function submitInquiry(event) {
  event.preventDefault();
  const form = event.target;
  const btn = document.getElementById('inquirySubmit');
  const err = document.getElementById('inquiryError');
  btn.disabled = true; btn.textContent = 'Sending…';
  err.classList.add('hidden');
  try {
    const res = await fetch('api/inquiry.php', { method: 'POST', body: new FormData(form) });
    const data = await res.json().catch(() => ({ ok: false, error: 'Something went wrong. Please try again.' }));
    if (!data.ok) throw new Error(data.error);
    document.getElementById('inquirySuccessName').textContent  = form.elements.full_name.value.trim().split(' ')[0];
    document.getElementById('inquirySuccessEmail').textContent = form.elements.email.value.trim();
    document.getElementById('inquiryFormView').classList.add('hidden');
    document.getElementById('inquirySuccess').classList.remove('hidden');
  } catch (e) {
    err.textContent = e.message || 'Could not send your inquiry. Please check your connection and try again.';
    err.classList.remove('hidden');
  } finally {
    btn.disabled = false; btn.textContent = 'Submit Inquiry';
  }
}
if (typeof peso === 'undefined') {
  window.peso = (n, d = 2) => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: d, maximumFractionDigits: d });
}

// Escape closes any open overlay / modal
document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  document.querySelectorAll('.overlay.is-open').forEach(el => {
    if (el.id === 'inquiryModal') closeInquiry(); else el.classList.remove('is-open');
  });
  document.querySelectorAll('.modal:not(.hidden)').forEach(el => el.classList.add('hidden'));
  document.querySelectorAll('.range, .notif').forEach(closePopover);
  document.body.style.overflow = '';
});
