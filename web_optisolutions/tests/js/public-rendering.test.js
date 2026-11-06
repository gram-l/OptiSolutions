import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/script.js', import.meta.url), 'utf8');
const attack = `'"><img src=x onerror=alert('stored-xss')><svg onload=alert(1)> &`;
const decode = value => value.replace(/&(amp|lt|gt|quot|#39);/g, (_, entity) => ({
  amp: '&', lt: '<', gt: '>', quot: '"', '#39': "'",
})[entity]);

// A small DOM adapter captures the actual renderer output and event callbacks.
// It does not execute HTML or simulate a browser security policy.
class Element {
  innerHTML = '';
  dataset = {};
  listeners = {};
  style = {};
  classList = { add() {}, remove() {}, toggle() {} };
  addEventListener(name, callback) { this.listeners[name] = callback; }
  querySelector() { return null; }
  querySelectorAll(selector) {
    const pattern = selector === '[data-doctor-photo]'
      ? /<img\b[^>]*\bdata-doctor-photo\b[^>]*>/g
      : new RegExp(`<div class="${selector.slice(1)}"[^>]*>`, 'g');
    return [...this.innerHTML.matchAll(pattern)].map(([tag]) => {
      const node = new Element();
      for (const [, name, value] of tag.matchAll(/data-([\w-]+)="([^"]*)"/g)) {
        const key = name.replace(/-([a-z])/g, (_, char) => char.toUpperCase());
        node.dataset[key] = decode(value);
      }
      this.children ??= [];
      this.children.push(node);
      return node;
    });
  }
}

function harness() {
  const elements = new Map([
    'doctorsGrid', 'doctorsGridHome', 'servicesGrid', 'serviceCardsHome',
    'modalContent', 'doctorModal', 'serviceModalBody', 'serviceModal',
  ].map(id => [id, new Element()]));
  const context = vm.createContext({
    URL, console,
    window: { location: { href: 'https://clinic.example/' }, scrollY: 0 },
    document: {
      getElementById: id => elements.get(id) ?? null,
      addEventListener() {}, querySelectorAll: () => [],
      body: new Element(), documentElement: { scrollTop: 0 },
    },
  });
  vm.runInContext(source, context);
  return { context, elements, run: code => vm.runInContext(code, context) };
}

function assertSafeMarkup(html) {
  assert.doesNotMatch(html, /<svg\b|<script\b|<img src=x/i);
  for (const [tag] of html.matchAll(/<[^>]+>/g)) {
    // Event-like strings inside a quoted alt/class/data value are harmless text.
    const attributes = tag.replace(/"[^"]*"|'[^']*'/g, '""');
    assert.doesNotMatch(attributes, /\s(?:onerror|onload)\s*=/i);
  }
  assert.ok(html.includes('&lt;img'), 'attack markup should display as literal text');
}

test('doctor cards and every detail field escape stored text and retain click behavior', () => {
  const { context, elements, run } = harness();
  context.fixture = {
    id: `1);alert('id-xss')`, name: attack, spec: attack, gender: attack,
    photo: `https://clinic.example/photo.jpg?name=${attack}`, yearsExp: attack,
    education: attack, license: attack, fellowship: attack, bio: attack, clinic: attack,
    services: [{ service_key: 'test', title: attack }], schedule: [{ day: attack, time: attack }],
  };
  context.condition = attack;
  run('doctorsData = [fixture]; servicesData.test = { conditions: [condition] }; renderDoctors();');
  const grid = elements.get('doctorsGrid');
  assertSafeMarkup(grid.innerHTML);
  assert.doesNotMatch(grid.innerHTML, /onclick=/);
  assert.doesNotMatch(grid.innerHTML, /id-xss/);
  const card = grid.children.find(node => node.listeners.click);
  assert.ok(card, 'card should have a click listener');
  card.listeners.click();
  const modal = elements.get('modalContent');
  assertSafeMarkup(modal.innerHTML);
  assert.ok(modal.innerHTML.includes('&amp;'));
  assert.ok(modal.children.some(node => node.listeners.error), 'modal images retain fallback listeners');
});

test('service cards, keys, icons and detail fields cannot inject markup or event handlers', () => {
  const { context, elements, run } = harness();
  context.serviceKey = attack;
  context.service = {
    title: attack, icon: attack, description: attack, conditions: [attack],
    doctors: [attack], room: attack, schedule: attack,
  };
  run('servicesData[serviceKey] = service; renderServiceCards();');
  const grid = elements.get('servicesGrid');
  assertSafeMarkup(grid.innerHTML);
  assert.doesNotMatch(grid.innerHTML, /onclick=/);
  const card = grid.children.find(node => node.listeners.click);
  assert.equal(card.dataset.serviceKey, attack);
  card.listeners.click();
  assertSafeMarkup(elements.get('serviceModalBody').innerHTML);
});

test('doctor search treats markup as text in its empty-results message', () => {
  const { context, elements, run } = harness();
  context.query = attack;
  run("renderDoctors('all', 'doctorsGrid', query);");
  assertSafeMarkup(elements.get('doctorsGrid').innerHTML);
});

test('specialty filter titles and keys remain text and attribute values', () => {
  const { context, elements, run } = harness();
  elements.set('filterTabs', new Element());
  context.serviceKey = attack;
  context.serviceTitle = attack;
  run('doctorsData = [{ services: [{ service_key: serviceKey, title: serviceTitle }] }]; rebuildFilterTabs("filterTabs");');
  assertSafeMarkup(elements.get('filterTabs').innerHTML);
});

test('image URLs permit ordinary photos and reject executable or embedded schemes', () => {
  const { context, run } = harness();
  for (const value of ['javascript:alert(1)', 'data:image/svg+xml,<svg onload=alert(1)>', 'vbscript:msgbox(1)', 'file:///private/image']) {
    context.imageValue = value;
    assert.equal(run('safeImageUrl(imageValue)'), null);
    assert.equal(run('doctorPhotoUrl({ profile_image: imageValue })'), null);
  }
  assert.equal(run("doctorPhotoUrl({ profile_image: 'doctors/photo.jpg' })"), 'https://clinic.example/storage/doctors/photo.jpg');
  assert.equal(run("doctorPhotoUrl({ profile_image: 'https://cdn.example/photo.jpg' })"), 'https://cdn.example/photo.jpg');
});

test('ordinary names, photos, specialties and service descriptions still render', () => {
  const { elements, run } = harness();
  run(`doctorsData = [{
    id: 1, name: "Dr. O'Neil & Cruz", spec: 'Pediatrics', gender: 'female',
    photo: '/storage/doctors/photo.jpg', yearsExp: 12, schedule: [], services: [],
  }]; renderDoctors();
  servicesData.pediatrics = { title: 'Pediatrics', icon: 'fa-child', description: 'Care for children', conditions: [], doctors: [], room: '201', schedule: 'Monday' };
  renderServiceCards();`);
  assert.ok(elements.get('doctorsGrid').innerHTML.includes('Dr. O&#39;Neil &amp; Cruz'));
  assert.ok(elements.get('doctorsGrid').innerHTML.includes('https://clinic.example/storage/doctors/photo.jpg'));
  assert.ok(elements.get('doctorsGrid').innerHTML.includes('12 years of experience'));
  assert.ok(elements.get('servicesGrid').innerHTML.includes('Care for children'));
});
