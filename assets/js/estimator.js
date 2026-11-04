/* Booking Fee Estimator: UI layer only (Leaflet + OpenStreetMap, no API key).

   This file does NOT calculate fees. It collects the inputs, shows the result, and hands the
   estimate to the booking form. All pricing lives in your backend.

   ── INTEGRATION CONTRACT ─────────────────────────────────────────────────────────────
   1) REQUEST  Whenever the inputs are valid and a destination is pinned, the component builds
        {
          equipment:      "dump",                       // key from $estimatorConfig['equipment']
          equipmentLabel: "10-Wheeler Dump Truck",
          weight:         12.5,  weightUnit: "tons",    // or "kg"; sent as typed, no conversion
          days:           3,
          destination:    { lat: 16.01, lng: 120.40, address: "..." }
        }
      and (a) fires a bubbling DOM event  `estimator:request`  (event.detail = the object above)
          (b) if ESTIMATOR.endpoint is set, POSTs it as JSON (debounced) and renders the JSON reply.

   2) RESULT   Call  FeeEstimator.render(result)  (or return this JSON from the endpoint):
        {
          distanceKm: 18.4,                  // optional, shown above the breakdown
          distanceNote: "by road",           // optional
          base:   { amount: 16500, detail: "₱5,500 × 3 days" },
          haul:   { amount: 828,   detail: "18.4 km × ₱45/km" },
          weight: { amount: 1875,  detail: "12.5 t × ₱150/ton" },
          total:  19203
        }
      Amounts are only formatted as pesos here; no arithmetic is done on them. `detail` is plain text.

   3) OTHER    FeeEstimator.setLoading(true|false) · .setError("message") · .reset() · .getRequest()
   Quick UI preview in the browser console:
        FeeEstimator.render({ distanceKm: 18.4, base:{amount:16500,detail:'3 days'}, haul:{amount:828,detail:'18.4 km'}, weight:{amount:1875,detail:'12.5 t'}, total:19203 })
   ───────────────────────────────────────────────────────────────────────────────────── */
(function () {
  const cfg = window.ESTIMATOR;
  if (!cfg || typeof L === 'undefined') return;

  const $ = (id) => document.getElementById(id);
  const peso = (n) => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const state = { dest: null, address: '', request: null, result: null, sig: null, geoToken: 0, reqToken: 0, timer: null };

  /* ---------- Map ---------- */
  const depot = [cfg.depot.lat, cfg.depot.lng];
  const map = L.map('estMap', { scrollWheelZoom: false }).setView(depot, 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  const pin = (kind, icon) => L.divIcon({
    className: '', iconSize: [34, 34], iconAnchor: [17, 36], popupAnchor: [0, -34],
    html: '<div class="pin pin--' + kind + '"><iconify-icon icon="' + icon + '" width="16"></iconify-icon></div>'
  });

  L.marker(depot, { icon: pin('depot', 'lucide:warehouse') }).addTo(map)
    .bindPopup('<strong>' + cfg.depot.label + '</strong><br>' + cfg.depot.address);

  let destMarker = null, line = null;
  setTimeout(() => map.invalidateSize(), 200);
  map.on('click', (e) => setDestination(e.latlng));

  function setDestination(latlng, label) {
    state.dest = latlng;
    if (!destMarker) {
      destMarker = L.marker(latlng, { icon: pin('dest', 'lucide:map-pin'), draggable: true }).addTo(map);
      destMarker.on('dragend', () => setDestination(destMarker.getLatLng()));
    } else {
      destMarker.setLatLng(latlng);
    }
    const coords = latlng.lat.toFixed(5) + ', ' + latlng.lng.toFixed(5);
    state.address = label || coords;
    $('estDestination').value = state.address;
    if (!label) reverseGeocode(latlng);

    // decorative dashed line depot → job site (a visual cue only; no distance is computed here)
    if (line) map.removeLayer(line);
    line = L.polyline([depot, [latlng.lat, latlng.lng]], { color: '#15233e', weight: 3, opacity: .6, dashArray: '8 10' }).addTo(map);
    map.fitBounds(line.getBounds(), { padding: [40, 40] });
    onInputsChanged();
  }

  async function reverseGeocode(latlng) {
    const my = ++state.geoToken;
    try {
      const r = await fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=en&lat=' + latlng.lat + '&lon=' + latlng.lng);
      const d = await r.json();
      if (state.geoToken !== my || !d.display_name) return;
      state.address = d.display_name;
      $('estDestination').value = state.address;
    } catch (e) { /* keep the coordinates */ }
  }

  /* ---------- Address search (Nominatim) ---------- */
  $('estSearchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const q = $('estSearch').value.trim();
    const msg = $('estSearchMsg');
    msg.className = 'est-msg';
    if (!q) return;
    msg.textContent = 'Searching…';
    try {
      const r = await fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=ph&accept-language=en&q=' + encodeURIComponent(q));
      const d = await r.json();
      if (!d.length) { msg.textContent = 'No match found. Try a nearby town or click the map.'; msg.className = 'est-msg est-msg--error'; return; }
      msg.textContent = '';
      setDestination(L.latLng(parseFloat(d[0].lat), parseFloat(d[0].lon)), d[0].display_name);
    } catch (err) {
      msg.textContent = 'Search is unavailable right now. Click the map instead.'; msg.className = 'est-msg est-msg--error';
    }
  });

  /* ---------- Form validation (input checks only) ---------- */
  function read() {
    const key = $('estEquipment').value;
    const eq = cfg.equipment[key];
    const unit = $('estWeightUnit').value;

    const dRaw = $('estDays').value.trim();
    const days = Number(dRaw);
    const daysOk = dRaw !== '' && Number.isInteger(days) && days >= 1 && days <= 90;
    $('estDaysErr').textContent = daysOk ? '' : 'Enter a whole number of days from 1 to 90.';
    $('estDaysErr').classList.toggle('hidden', daysOk);
    $('estDays').classList.toggle('field--error', !daysOk);

    const wRaw = $('estWeight').value.trim();
    const w = wRaw === '' ? 0 : Number(wRaw);
    let wOk = true, wMsg = '';
    if (wRaw !== '') {
      if (!isFinite(w) || w < 0) { wOk = false; wMsg = 'Enter a valid weight (0 or more).'; }
      else if ((unit === 'kg' ? w / 1000 : w) > eq.maxTons) { wOk = false; wMsg = 'Max load for ' + eq.label + ' is ' + eq.maxTons + ' tons.'; }
    }
    $('estWeightErr').textContent = wMsg;
    $('estWeightErr').classList.toggle('hidden', wOk);
    $('estWeight').classList.toggle('field--error', !wOk);

    $('estRateNote').textContent = 'Max load ' + eq.maxTons + ' tons';

    return {
      valid: daysOk && wOk,
      request: {
        equipment: key, equipmentLabel: eq.label,
        weight: w, weightUnit: unit, days: daysOk ? days : null,
        destination: state.dest ? { lat: +state.dest.lat.toFixed(6), lng: +state.dest.lng.toFixed(6), address: state.address } : null
      }
    };
  }

  /* ---------- Display ---------- */
  const lines = { base: ['lnBase', 'lnBaseDetail', 'Base Equipment Rental'], haul: ['lnHaul', 'lnHaulDetail', 'Distance-Based Hauling Fee'], weight: ['lnWeight', 'lnWeightDetail', 'Weight-Based Surcharge'] };

  function tween(el, to) {
    const from = el._v || 0;
    el._v = to;
    el.classList.remove('est-line__value--empty');
    if (reduceMotion || from === to) { el.textContent = peso(to); return; }
    const t0 = performance.now(), dur = 350;
    const step = (t) => {
      const p = Math.min((t - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3);
      el.textContent = peso(from + (to - from) * e);        // visual easing only
      if (p < 1 && el._v === to) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  function clearDisplay() {
    Object.values(lines).forEach(([val, det]) => {
      const v = $(val); v._v = 0; v.textContent = '—'; v.classList.add('est-line__value--empty'); $(det).textContent = '—';
    });
    $('lnTotal')._v = 0; $('lnTotal').textContent = '—';
    $('estDistance').textContent = 'Distance: —';
    state.result = null;
  }

  function syncProceed(valid) {
    $('estHint').classList.remove('field-error');
    const ready = valid && state.dest && state.result;
    $('estProceed').disabled = !ready;
    $('estHint').textContent = ready ? '' :
      (!state.dest ? 'Select a destination on the map to continue.' :
       (!valid ? 'Fix the highlighted fields to continue.' : 'Your estimate will appear here once it is ready.'));
  }

  /* ---------- Request / result hand-off ---------- */
  function onInputsChanged() {
    const { valid, request } = read();
    const sig = JSON.stringify([valid, request]);
    if (sig === state.sig) return;                      // nothing really changed (e.g. a blur-triggered duplicate event)
    state.sig = sig;
    clearTimeout(state.timer);
    state.reqToken++;                                   // invalidates any in-flight response
    if (state.result) clearDisplay();                   // inputs changed: previous result is stale
    state.request = valid && request.destination ? request : null;
    syncProceed(valid);
    if (!state.request) return;
    state.timer = setTimeout(() => sendRequest(state.request), 350);
  }

  async function sendRequest(request) {
    document.dispatchEvent(new CustomEvent('estimator:request', { detail: request }));
    if (!cfg.endpoint) return;
    const my = state.reqToken;
    api.setLoading(true);
    try {
      const res = await fetch(cfg.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(request) });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      if (my !== state.reqToken) return;                // a newer request superseded this one
      api.render(data);
    } catch (err) {
      if (my === state.reqToken) api.setError('We couldn’t load your estimate. Please try again.');
    } finally {
      if (my === state.reqToken) api.setLoading(false);
    }
  }

  const api = window.FeeEstimator = {
    render(result) {
      if (!result || !result.base || !result.haul || !result.weight || result.total == null) {
        console.warn('FeeEstimator.render: unexpected result shape', result); return;
      }
      state.result = result;
      Object.entries(lines).forEach(([k, [val, det]]) => { tween($(val), result[k].amount); $(det).textContent = result[k].detail || ''; });
      tween($('lnTotal'), result.total);
      $('estDistance').textContent = result.distanceKm != null
        ? 'Distance: ' + Number(result.distanceKm).toFixed(1) + ' km' + (result.distanceNote ? ' · ' + result.distanceNote : '')
        : 'Distance: —';
      syncProceed(read().valid);
    },
    setLoading(on) { $('estLines').classList.toggle('is-loading', !!on); $('estLines').setAttribute('aria-busy', on ? 'true' : 'false'); },
    setError(msg) { $('estHint').textContent = msg; $('estHint').classList.add('field-error'); $('estProceed').disabled = true; },
    reset() { clearDisplay(); state.request = null; state.sig = null; syncProceed(read().valid); },
    getRequest() { return read().request; }
  };

  // 'input' fires for typing and for <select> changes; 'change' would double-fire on blur
  ['estEquipment', 'estWeight', 'estWeightUnit', 'estDays'].forEach((id) => $(id).addEventListener('input', onInputsChanged));

  /* ---------- Hand the estimate to the booking form ---------- */
  const setText = (id, v) => { $(id).textContent = v; };
  const money = (n) => Number(n).toFixed(2);

  window.openBookingWithEstimate = function () {
    const req = state.request, res = state.result;
    if (!req || !res) return;
    $('bkEquipment').value = req.equipmentLabel;
    $('bkSite').value = state.address;
    setText('bkSumBase', req.equipmentLabel + ' · ' + req.days + (req.days === 1 ? ' day' : ' days'));
    setText('bkBase', peso(res.base.amount));
    setText('bkSumHaul', 'Hauling' + (res.distanceKm != null ? ' · ' + Number(res.distanceKm).toFixed(1) + ' km' : ''));
    setText('bkHaul', peso(res.haul.amount));
    setText('bkSumWeight', 'Weight surcharge' + (req.weight ? ' · ' + req.weight + ' ' + req.weightUnit : ''));
    setText('bkWeight', peso(res.weight.amount));
    setText('bkTotal', peso(res.total));
    $('bkHDays').value = req.days; $('bkHKm').value = res.distanceKm != null ? money(res.distanceKm) : '';
    $('bkHBase').value = money(res.base.amount); $('bkHHaul').value = money(res.haul.amount);
    $('bkHWeight').value = money(res.weight.amount); $('bkHTotal').value = money(res.total);
    $('bkEstimate').classList.remove('hidden');
    window.__estDays = req.days;
    $('bkStart').value = ''; $('bkEnd').value = '';
    openModal('bookingModal');
  };

  $('estProceed').addEventListener('click', () => {
    if (!state.result) return;
    $('lnTotal').classList.remove('is-pulse'); void $('lnTotal').offsetWidth; $('lnTotal').classList.add('is-pulse');
    window.openBookingWithEstimate();
  });

  // Rental end date follows the start date for the number of days requested (date convenience only)
  const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  $('bkStart').addEventListener('change', () => {
    if (!window.__estDays || !$('bkStart').value) return;
    const d = new Date($('bkStart').value + 'T00:00:00');
    d.setDate(d.getDate() + window.__estDays - 1);
    $('bkEnd').value = fmt(d);
  });

  read();
  syncProceed(read().valid);
})();
