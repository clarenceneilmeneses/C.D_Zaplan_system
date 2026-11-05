/*
 * Map site picker + pricing formula, shared by the landing page estimator and the client booking form.
 *
 *   Map tiles      : OpenStreetMap        (free, attribution required)
 *   Road distance  : OSRM public server   (free demo server — falls back to straight line × 1.3 if it is down)
 *   Search/address : Nominatim (OSM)     (free, max ~1 request per second) — "Find" button + reverse lookup of a pin
 *   Autocomplete   : Photon (komoot, OSM) (free, built for search-as-you-type; Nominatim's policy forbids autocomplete)
 *
 * Needs Leaflet loaded first (set $useMap = true before including includes/header.php)
 * and window.ESTIMATOR from estimator_config() in includes/bootstrap.php.
 */

function peso(n, decimals = 2) {
  return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
}

/* Same formula as estimate_cost() in includes/bootstrap.php — keep both in sync. */
function estimateCost(type, km, days) {
  const trip = Math.max(type.minTrip, km * type.perKm);       // one trip (yard → site)
  const rental = type.daily * days;
  const mobilization = Math.round(trip * 2 * 100) / 100;       // delivery + pull-out
  return { trip, rental, mobilization, total: rental + mobilization };
}

function haversineKm(lat1, lng1, lat2, lng2) {
  const rad = d => d * Math.PI / 180, R = 6371;
  const a = Math.sin(rad(lat2 - lat1) / 2) ** 2 +
            Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(rad(lng2 - lng1) / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

async function fetchJson(url, ms = 8000) {
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), ms);
  try {
    const res = await fetch(url, { signal: ctrl.signal, headers: { 'Accept-Language': 'en' } });
    if (!res.ok) throw new Error(res.status);
    return await res.json();
  } finally { clearTimeout(timer); }
}

/* Shortens "Bonuan Gueset, Dagupan, Pangasinan, Ilocos Region, 2400, Philippines" to the useful part */
function shortAddress(displayName) {
  return displayName.split(',').map(s => s.trim()).filter(s => !/^\d{4}$/.test(s) && s !== 'Philippines' && !/Region$/.test(s)).slice(0, 4).join(', ');
}

class SitePicker {
  /**
   * @param {HTMLElement} el     map container
   * @param {object}      cfg    window.ESTIMATOR
   * @param {function}    onChange called with {lat, lng, km, approx, address, pending}
   * @param {object=}     initial optional {lat, lng, address} to start with
   */
  constructor(el, cfg, onChange, initial) {
    this.cfg = cfg;
    this.onChange = onChange;
    this.seq = 0;
    this.base = [cfg.base.lat, cfg.base.lng];

    this.map = L.map(el, { scrollWheelZoom: false }).setView(this.base, 11);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(this.map);

    L.marker(this.base, { icon: SitePicker.icon('yard', 'lucide:warehouse'), keyboard: false })
      .addTo(this.map).bindTooltip('Our yard · ' + cfg.base.label);

    this.map.on('click', e => this.setSite(e.latlng.lat, e.latlng.lng));
    // Only zoom with the scroll wheel after the user clicks the map, so scrolling the page isn't hijacked
    this.map.on('focus click', () => this.map.scrollWheelZoom.enable());
    this.map.on('blur', () => this.map.scrollWheelZoom.disable());

    // The map stretches to match the price panel, so redraw tiles whenever its size changes
    if (window.ResizeObserver) new ResizeObserver(() => this.map.invalidateSize()).observe(el);

    if (initial && isFinite(initial.lat) && isFinite(initial.lng)) this.setSite(+initial.lat, +initial.lng, initial.address);
  }

  static icon(kind, iconName) {
    return L.divIcon({
      className: 'map-pin map-pin--' + kind,
      html: `<span class="map-pin__body"><iconify-icon icon="${iconName}" width="14"></iconify-icon></span>`,
      iconSize: [30, 38], iconAnchor: [15, 36], tooltipAnchor: [0, -30],
    });
  }

  /** Call after the map's container becomes visible (e.g. a modal opens) */
  refresh() {
    this.map.invalidateSize();
    if (this.site) this.fit();
  }

  fit() {
    this.map.fitBounds(L.latLngBounds([this.base, [this.site.lat, this.site.lng]]), { padding: [40, 40], maxZoom: 14 });
  }

  emit() { this.onChange && this.onChange({ ...this.site }); }

  async setSite(lat, lng, address = '') {
    const id = ++this.seq;
    this.site = { lat, lng, km: null, approx: false, address, pending: true };

    if (!this.marker) {
      this.marker = L.marker([lat, lng], { draggable: true, icon: SitePicker.icon('site', 'lucide:hard-hat') }).addTo(this.map);
      this.marker.bindTooltip('Project site');
      this.marker.on('dragend', () => { const p = this.marker.getLatLng(); this.setSite(p.lat, p.lng); });
    } else {
      this.marker.setLatLng([lat, lng]);
    }
    if (this.line) { this.line.remove(); this.line = null; }
    this.fit();
    this.emit();

    const [route, place] = await Promise.all([this.route(lat, lng), address ? null : this.reverse(lat, lng)]);
    if (id !== this.seq) return;   // user already picked another spot

    this.site.km = route.km;
    this.site.approx = route.approx;
    if (place) this.site.address = place;
    this.site.pending = false;
    this.line = L.polyline(route.path, {
      color: '#15233e', weight: 4, opacity: .75, dashArray: route.approx ? '8 8' : null,
    }).addTo(this.map);
    this.emit();
  }

  async route(lat, lng) {
    const [bLat, bLng] = this.base;
    try {
      const data = await fetchJson(`https://router.project-osrm.org/route/v1/driving/${bLng},${bLat};${lng},${lat}?overview=simplified&geometries=geojson`);
      const r = data.routes && data.routes[0];
      if (!r) throw new Error('no route');
      return { km: r.distance / 1000, approx: false, path: r.geometry.coordinates.map(([x, y]) => [y, x]) };
    } catch (err) {
      // Routing service down or no road found: straight line × 1.3 is a fair road-distance guess
      return { km: haversineKm(bLat, bLng, lat, lng) * 1.3, approx: true, path: [this.base, [lat, lng]] };
    }
  }

  async reverse(lat, lng) {
    try {
      const data = await fetchJson(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=16&lat=${lat}&lon=${lng}`);
      return data.display_name ? shortAddress(data.display_name) : '';
    } catch (err) { return ''; }
  }

  async search(query) {
    query = (query || '').trim();
    if (!query) return;
    try {
      const results = await fetchJson(`https://nominatim.openstreetmap.org/search?format=jsonv2&countrycodes=ph&limit=1&q=${encodeURIComponent(query)}`);
      if (!results.length) { alert('We couldn\'t find "' + query + '". Try a nearby town or landmark, or click the map.'); return; }
      this.setSite(+results[0].lat, +results[0].lon, shortAddress(results[0].display_name));
    } catch (err) {
      alert('Location search is unavailable right now. You can still click the map to set your site.');
    }
  }
}

/**
 * Address suggestions under a text input while the user types.
 *   new PlaceAutocomplete(input, place => picker.setSite(place.lat, place.lng, place.label), { near: [lat, lng] })
 * Keyboard: ↑/↓ to move, Enter to pick, Esc to close. If the service is down the input just works as a normal field.
 */
class PlaceAutocomplete {
  constructor(input, onPick, opts = {}) {
    this.input = input;
    this.onPick = onPick;
    this.near = opts.near;
    this.items = [];
    this.active = -1;
    this.cache = new Map();

    // Wrap the input so the list can sit right under it
    this.box = document.createElement('div');
    this.box.className = 'ac hidden';
    this.box.setAttribute('role', 'listbox');
    this.box.id = (input.id || 'place') + 'Suggestions';
    const host = opts.container || input.parentElement;
    host.classList.add('ac-host');   // position: relative, so the list lines up under the field
    host.appendChild(this.box);
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', this.box.id);
    input.setAttribute('aria-expanded', 'false');

    let timer;
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = input.value.trim();
      if (q.length < 3) { this.close(); return; }
      timer = setTimeout(() => this.lookup(q), 300);
    });
    input.addEventListener('keydown', e => this.key(e));
    input.addEventListener('blur', () => setTimeout(() => this.close(), 150));
    input.addEventListener('focus', () => { if (this.items.length && input.value.trim().length >= 3) this.open(); });
  }

  async lookup(q) {
    if (this.cache.has(q)) { this.render(this.cache.get(q)); return; }
    if (this.ctrl) this.ctrl.abort();
    this.ctrl = new AbortController();
    const p = new URLSearchParams({ q, limit: 8, lang: 'en', bbox: '116.9,4.5,126.7,21.3' });   // Philippines
    if (this.near) { p.set('lat', this.near[0]); p.set('lon', this.near[1]); }                   // rank places near the yard first
    try {
      const res = await fetch('https://photon.komoot.io/api/?' + p, { signal: this.ctrl.signal });
      if (!res.ok) throw new Error(res.status);
      const data = await res.json();
      const seen = new Set();
      const places = (data.features || [])
        .filter(f => (f.properties.countrycode || 'PH') === 'PH')
        .map(f => PlaceAutocomplete.toPlace(f))
        .filter(pl => !seen.has(pl.label) && seen.add(pl.label))
        .slice(0, 6);
      this.cache.set(q, places);
      if (this.input.value.trim() === q) this.render(places);
    } catch (err) {
      if (err.name !== 'AbortError') this.close();   // service down: keep the field usable
    }
  }

  static toPlace(f) {
    const p = f.properties;
    const name = p.name || [p.housenumber, p.street].filter(Boolean).join(' ') || p.city || p.county || '';
    const street = p.street && p.name && p.street !== p.name ? [p.housenumber, p.street].filter(Boolean).join(' ') : '';
    const area = [street, p.district || p.locality, p.city || p.town || p.village, p.county !== p.city ? p.county : '', p.state]
      .filter((v, i, a) => v && v !== name && a.indexOf(v) === i);
    return {
      lat: f.geometry.coordinates[1], lng: f.geometry.coordinates[0],
      name, area: area.slice(0, 3).join(', '),
      label: [name, ...area.slice(0, 3)].join(', '),
    };
  }

  render(places) {
    this.items = places;
    this.active = -1;
    if (!places.length) {
      this.box.innerHTML = '<div class="ac__empty">No matching places. Try a town or barangay name.</div>';
      this.open();
      return;
    }
    this.box.replaceChildren(...places.map((pl, i) => {
      const opt = document.createElement('button');
      opt.type = 'button';
      opt.className = 'ac__item';
      opt.setAttribute('role', 'option');
      opt.innerHTML = '<iconify-icon icon="lucide:map-pin" width="14"></iconify-icon><span><strong></strong><small></small></span>';
      opt.querySelector('strong').textContent = pl.name;
      opt.querySelector('small').textContent = pl.area;
      opt.addEventListener('mousedown', e => e.preventDefault());   // keep focus so blur doesn't close first
      opt.addEventListener('click', () => this.pick(i));
      return opt;
    }));
    this.open();
  }

  key(e) {
    const open = !this.box.classList.contains('hidden') && this.items.length;
    if (e.key === 'ArrowDown' && open)      { e.preventDefault(); this.highlight(this.active + 1); }
    else if (e.key === 'ArrowUp' && open)   { e.preventDefault(); this.highlight(this.active - 1); }
    else if (e.key === 'Enter' && open)     { e.preventDefault(); this.pick(Math.max(0, this.active)); }   // Enter = top suggestion
    else if (e.key === 'Escape')            { this.close(); }
  }

  highlight(i) {
    const opts = this.box.querySelectorAll('.ac__item');
    this.active = (i + opts.length) % opts.length;
    opts.forEach((o, n) => o.classList.toggle('is-active', n === this.active));
    opts[this.active].scrollIntoView({ block: 'nearest' });
  }

  pick(i) {
    const pl = this.items[i];
    this.input.value = pl.label;
    this.close();
    this.onPick(pl);
  }

  open()  { this.box.classList.remove('hidden'); this.input.setAttribute('aria-expanded', 'true'); }
  close() { this.box.classList.add('hidden'); this.input.setAttribute('aria-expanded', 'false'); }
}
