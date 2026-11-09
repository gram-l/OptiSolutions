const text = value => typeof value === 'string' ? value.trim() : '';

function httpUrl(value) {
  try {
    const url = new URL(text(value));
    return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
  } catch {
    return '';
  }
}

function visible(element, show) {
  element.hidden = !show;
  element.style.display = show ? '' : 'none';
}

function link(element, url) {
  if (url) element.setAttribute('href', url);
  else element.removeAttribute('href');
  visible(element, Boolean(url));
}

export function renderClinicDetails(data, root = document) {
  const info = data && typeof data === 'object' ? data : {};
  root.querySelectorAll('[data-clinic-field]').forEach(element => {
    const value = text(info[element.dataset.clinicField]);
    element.textContent = value || element.dataset.clinicEmpty || '';
    if (element.dataset.clinicEmpty === '') element.hidden = !value;
    if (element.hasAttribute('data-clinic-placeholder')) element.classList.toggle('values-empty', !value);
  });

  const valueIcons = ['heart-pulse', 'shield-check', 'star', 'handshake', 'lock', 'users'];
  root.querySelectorAll('[data-clinic-values]').forEach(element => {
    const values = Array.isArray(info.core_values) ? info.core_values.map(text).filter(Boolean) : [];
    const owner = element.ownerDocument || root;
    const nodes = values.map((value, index) => {
      const [name, ...rest] = value.split('|');
      const desc = text(rest.join('|'));
      const card = owner.createElement('div');
      card.className = 'about-v2-value-card';
      const iconWrap = owner.createElement('span');
      iconWrap.className = 'about-v2-value-icon';
      const icon = owner.createElement('span');
      icon.className = 'asset-icon';
      icon.setAttribute('aria-hidden', 'true');
      icon.style.setProperty('--icon', "url('/images/icons/" + valueIcons[index % valueIcons.length] + ".svg')");
      iconWrap.appendChild(icon);
      const title = owner.createElement('strong');
      title.textContent = text(name);
      card.append(iconWrap, title);
      if (desc) {
        const small = owner.createElement('small');
        small.textContent = desc;
        card.appendChild(small);
      }
      return card;
    });
    element.replaceChildren(...nodes);
    const wrap = element.closest('[data-clinic-values-wrap]');
    if (wrap) visible(wrap, nodes.length > 0);
  });

  const facebook = httpUrl(info.facebook_url);
  const directions = httpUrl(info.directions_url);
  const map = httpUrl(info.map_url);
  const phone = /^tel:\+?[0-9]{3,20}$/.test(info.phone_url || '') ? info.phone_url : '';
  root.querySelectorAll('[data-clinic-facebook]').forEach(element => link(element, facebook));
  root.querySelectorAll('[data-clinic-directions]').forEach(element => link(element, directions));
  root.querySelectorAll('[data-clinic-phone]').forEach(element => link(element, phone));
  root.querySelectorAll('[data-clinic-map]').forEach(element => {
    // Avoid reloading the iframe when an unrelated clinic field changes.
    if (map && element.getAttribute('src') !== map) element.setAttribute('src', map);
    else if (!map) element.removeAttribute('src');
    visible(element, Boolean(map));
  });
  root.querySelectorAll('[data-clinic-visible]').forEach(element => {
    const field = element.dataset.clinicVisible;
    visible(element, Boolean(field === 'facebook_url' ? facebook : text(info[field])));
  });
}

export function createClinicDetailsLoader(root = document, fetcher = globalThis.fetch) {
  let busy = false;
  let signature = '';
  return async function load() {
    if (busy) return;
    busy = true;
    try {
      const response = await fetcher('/api/clinic-info', { cache: 'no-store', headers: { Accept: 'application/json' } });
      if (!response.ok) return;
      const payload = await response.json();
      const info = (Array.isArray(payload) ? payload[0] : payload) || {};
      const next = JSON.stringify(info);
      if (next !== signature) {
        renderClinicDetails(info, root);
        signature = next;
      }
    } catch {
      // Keep the last valid display during an outage; retry on the next tick.
    } finally {
      busy = false;
    }
  };
}

export function startClinicDetailsSync(root = document, win = window, fetcher = globalThis.fetch) {
  const load = createClinicDetailsLoader(root, fetcher);
  const tick = () => { if (!root.hidden) return load(); };
  tick();
  win.setInterval(tick, 8000);
  root.addEventListener('visibilitychange', tick);
  win.addEventListener('focus', tick);
  return load;
}

if (typeof document !== 'undefined' && typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => startClinicDetailsSync(), { once: true });
  } else {
    startClinicDetailsSync();
  }
}