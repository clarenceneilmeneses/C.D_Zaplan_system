/* =====================================================================
   C.D. Zaplan Trucking — Reviews marquee, review pop-up, privacy modal
   Vanilla JS, no dependencies. Loaded from includes/scripts.php.
   ===================================================================== */
(function () {
  'use strict';

  var FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
  var marquee = document.getElementById('cdzMarquee');
  var track = document.getElementById('cdzMarqueeTrack');
  var active = null; // the modal currently open

  /* ---------- Generic modal helper (backdrop click, Esc, focus trap, scroll lock) ---------- */
  function createModal(el, hooks) {
    if (!el) return null;
    var dialog = el.querySelector('.cdz-modal__dialog');
    var lastFocused = null;
    hooks = hooks || {};

    function open() {
      lastFocused = document.activeElement;
      el.classList.add('is-open');
      el.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      active = api;
      if (hooks.onOpen) hooks.onOpen();
      setTimeout(function () { dialog.focus(); }, 50);
    }
    function close() {
      el.classList.remove('is-open');
      el.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (active === api) active = null;
      if (hooks.onClose) hooks.onClose();
      if (lastFocused && lastFocused.focus) lastFocused.focus();
    }
    var api = { el: el, dialog: dialog, open: open, close: close };

    el.addEventListener('click', function (e) { if (e.target === el) close(); });
    el.querySelectorAll('[data-modal-close], [data-privacy-close]').forEach(function (b) {
      b.addEventListener('click', close);
    });
    return api;
  }

  document.addEventListener('keydown', function (e) {
    if (!active) return;
    if (e.key === 'Escape') { active.close(); return; }
    if (e.key === 'Tab') { // keep focus inside the dialog
      var items = active.dialog.querySelectorAll(FOCUSABLE);
      if (!items.length) return;
      var first = items[0], last = items[items.length - 1];
      if (e.shiftKey && (document.activeElement === first || document.activeElement === active.dialog)) {
        e.preventDefault(); last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault(); first.focus();
      }
    }
  });

  /* ---------- 1. Reviews marquee (JS-driven so buttons can nudge it, even while paused) ---------- */
  var wrap = document.getElementById('cdzMarqueeWrap');

  if (marquee && track) {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var SPEED = parseFloat(marquee.getAttribute('data-speed')) || 45; // pixels per second (edit in HTML or here)
    var originals = track.children.length;
    var x = 0, goal = null, loopW = 0, step = 0, last = 0, resumeAt = 0, vel = 0, dragging = false;

    function step_() { // distance of one card + gap
      var gap = parseFloat(getComputedStyle(track).columnGap) || 20;
      return track.children[0].offsetWidth + gap;
    }

    if (!reduceMotion) {
      // Clone the cards once so the loop is seamless. Clones are hidden from screen readers
      // and taken out of the tab order; clicking them still opens the review.
      Array.prototype.slice.call(track.children).forEach(function (card) {
        var clone = card.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.querySelectorAll('button').forEach(function (b) { b.setAttribute('tabindex', '-1'); });
        track.appendChild(clone);
      });

      var measure = function () {
        step = step_();
        loopW = track.children[originals].offsetLeft - track.children[0].offsetLeft; // width of one full set
      };
      measure();
      window.addEventListener('resize', measure);
      window.addEventListener('load', measure);

      var isPaused = function () {
        return (wrap && (wrap.matches(':hover') || wrap.matches(':focus-within'))) ||
               marquee.classList.contains('is-paused') || performance.now() < resumeAt;
      };

      var tick = function (ts) {
        var dt = Math.min((ts - (last || ts)) / 1000, 0.05);
        last = ts;

        if (dragging) {
          // x is being set by the pointer while the user drags
        } else if (goal !== null) {                  // button nudge: ease toward the target (runs even when paused)
          x += (goal - x) * (1 - Math.exp(-dt * 9));
          if (Math.abs(goal - x) < 0.5) { x = goal; goal = null; }
        } else {
          if (Math.abs(vel) > 1) { x += vel * dt; vel *= Math.exp(-dt * 4); } else { vel = 0; } // momentum after a swipe
          if (!isPaused()) x -= SPEED * dt;          // normal auto-scroll
        }

        if (loopW > 0) {                             // wrap around seamlessly
          while (x <= -loopW) { x += loopW; if (goal !== null) goal += loopW; }
          while (x > 0)       { x -= loopW; if (goal !== null) goal -= loopW; }
        }
        track.style.transform = 'translate3d(' + x + 'px,0,0)';
        requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);

      /* ----- Scroll by hand: trackpad / shift+wheel (horizontal), mouse drag, touch swipe ----- */
      wrap.addEventListener('wheel', function (e) {
        if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return; // vertical wheel keeps scrolling the page
        e.preventDefault();
        goal = null; vel = 0;
        x -= e.deltaX;
        resumeAt = performance.now() + 1200;
      }, { passive: false });

      var downId = null, startX = 0, startOx = 0, lastX = 0, lastT = 0, moved = false, dragEnd = 0;

      marquee.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        downId = e.pointerId; startX = lastX = e.clientX; startOx = x; lastT = performance.now(); moved = false; vel = 0;
      });
      marquee.addEventListener('pointermove', function (e) {
        if (downId !== e.pointerId) return;
        var dx = e.clientX - startX;
        if (!moved) {
          if (Math.abs(dx) < 6) return;                // small movement = still a click
          moved = true; dragging = true; goal = null;
          marquee.classList.add('is-dragging');
          try { marquee.setPointerCapture(e.pointerId); } catch (err) {}
        }
        x = startOx + dx;
        var now = performance.now(), dt = (now - lastT) / 1000;
        if (dt > 0) vel = 0.7 * vel + 0.3 * ((e.clientX - lastX) / dt);
        lastX = e.clientX; lastT = now;
      });
      function endDrag(e) {
        if (downId !== e.pointerId) return;
        downId = null;
        if (!moved) return;
        dragging = false; dragEnd = performance.now();
        if (dragEnd - lastT > 80) vel = 0;               // stopped before letting go: no momentum
        vel = Math.max(-3000, Math.min(3000, vel));
        resumeAt = dragEnd + 1200;
        marquee.classList.remove('is-dragging');
        try { marquee.releasePointerCapture(e.pointerId); } catch (err) {}
      }
      marquee.addEventListener('pointerup', endDrag);
      marquee.addEventListener('pointercancel', endDrag);
      // A drag must not count as a click on a card (so the review pop-up doesn't open)
      marquee.addEventListener('click', function (e) {
        if (performance.now() - dragEnd < 300) { e.stopPropagation(); e.preventDefault(); }
      }, true);
      marquee.addEventListener('dragstart', function (e) { e.preventDefault(); }); // no ghost-image drag
    }

    // Touch: pause while a finger is down, resume shortly after
    var resumeTimer;
    wrap.addEventListener('touchstart', function () {
      clearTimeout(resumeTimer);
      marquee.classList.add('is-paused');
    }, { passive: true });
    ['touchend', 'touchcancel'].forEach(function (evt) {
      wrap.addEventListener(evt, function () {
        resumeTimer = setTimeout(function () {
          if (!active) marquee.classList.remove('is-paused');
        }, 1500);
      }, { passive: true });
    });
  }

  /* ---------- 2. Full-review pop-up (opens when a card is clicked) ---------- */
  var review = createModal(document.getElementById('cdzReviewModal'), {
    onOpen:  function () { if (marquee) marquee.classList.add('is-paused'); },
    onClose: function () { if (marquee) marquee.classList.remove('is-paused'); }
  });

  if (review && track) {
    var rmImg = document.getElementById('cdzRmImg');
    var rmTag = document.getElementById('cdzRmTag');
    var rmText = document.getElementById('cdzRmText');
    var rmName = document.getElementById('cdzRmName');
    var rmAvatar = document.getElementById('cdzRmAvatar');

    track.addEventListener('click', function (e) {
      var card = e.target.closest('.cdz-review');
      if (!card) return;
      var photo = card.querySelector('.cdz-review__media img');
      rmImg.src = photo ? photo.currentSrc || photo.src : '';
      rmImg.alt = photo ? photo.alt : '';
      rmTag.textContent = card.querySelector('.cdz-review__tag').textContent;
      rmText.textContent = card.querySelector('.cdz-review__text').textContent; // full text (CSS only clamps the view)
      rmName.textContent = card.querySelector('.cdz-review__author strong').textContent;
      rmAvatar.innerHTML = card.querySelector('.cdz-avatar').innerHTML;
      review.open();
    });

    // "Request a Quote" in the pop-up: close it, then open the site's inquiry form if present
    var quoteBtn = review.el.querySelector('[data-review-quote]');
    if (quoteBtn) quoteBtn.addEventListener('click', function () {
      review.close();
      if (typeof window.openInquiry === 'function') setTimeout(function () { window.openInquiry('General Inquiry'); }, 300);
    });
  }

  /* ---------- 3. Footer year ---------- */
  var year = document.getElementById('cdzYear');
  if (year) year.textContent = new Date().getFullYear();

  /* ---------- 4. Privacy Policy modal ---------- */
  var privacy = createModal(document.getElementById('cdzPrivacyModal'));
  if (privacy) {
    document.querySelectorAll('[data-privacy-open]').forEach(function (el) {
      el.addEventListener('click', privacy.open);
    });
  }
})();
