/* Motion helpers: header shadow, scroll progress bar, scroll-reveal, number count-up.
   Purely visual. If this file fails, the site still works. */
(function () {
  window.__motionReady = true;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* header shadow + scroll progress */
  const header = document.querySelector('.site-header');
  const bar = document.getElementById('scrollProgress');
  function onScroll() {
    const y = window.scrollY;
    if (header) header.classList.toggle('is-scrolled', y > 10);
    if (bar) {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* hero video: pause when off-screen, and don't autoplay for reduced-motion / data-saver users (poster shows instead) */
  const vid = document.querySelector('.hero__video');
  if (vid) {
    const saver = navigator.connection && navigator.connection.saveData;
    if (reduce || saver) {
      vid.pause();
    } else if ('IntersectionObserver' in window) {
      new IntersectionObserver(([en]) => { if (en.isIntersecting) { const p = vid.play(); if (p) p.catch(() => {}); } else vid.pause(); }, { threshold: 0.1 }).observe(vid);
    }
  }

  /* count-up: "₱1.25M", "77,000", "100%", "12" ... keeps the prefix and suffix */
  function countUp(el) {
    const m = el.textContent.trim().match(/^(\D*?)(\d[\d,]*\.?\d*)(.*)$/);
    if (!m || reduce) return;
    const [, prefix, numText, suffix] = m;
    const target = parseFloat(numText.replace(/,/g, ''));
    const decimals = (numText.split('.')[1] || '').length;
    const commas = numText.includes(',');
    const fmt = (n) => (commas
      ? n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
      : n.toFixed(decimals));
    const t0 = performance.now(), dur = 1000;
    const step = (t) => {
      const p = Math.min((t - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3);
      el.textContent = prefix + fmt(target * e) + suffix;
      if (p < 1) requestAnimationFrame(step); else el.textContent = prefix + fmt(target) + suffix;
    };
    requestAnimationFrame(step);
  }

  /* scroll reveal + count-up trigger */
  const revealEls = document.querySelectorAll('[data-reveal]');
  const countEls = document.querySelectorAll('[data-count]');
  if (!('IntersectionObserver' in window)) {
    revealEls.forEach((el) => el.classList.add('is-visible', 'is-done'));
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => {
      if (!en.isIntersecting) return;
      const el = en.target;
      io.unobserve(el);
      if (el.hasAttribute('data-reveal')) {
        el.classList.add('is-visible');
        const delay = parseFloat(getComputedStyle(el).getPropertyValue('--d')) || 0;
        setTimeout(() => el.classList.add('is-done'), reduce ? 0 : 900 + delay * 1000);
      }
      if (el.hasAttribute('data-count')) countUp(el);
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
  revealEls.forEach((el) => io.observe(el));
  countEls.forEach((el) => io.observe(el));
})();
