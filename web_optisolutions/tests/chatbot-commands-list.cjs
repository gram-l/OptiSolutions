const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const script = fs.readFileSync('resources/views/admin_acc/chatbot_commands/index.blade.php', 'utf8')
    .match(/<script id="commands-list-script">([\s\S]*?)<\/script>/)[1];
const rows = Array.from({ length: 17 }, (_, index) => ({ hidden: false,
    dataset: { search: `Label ${index} common trigger ${index === 16 ? 'full reply unique phrase' : 'reply'}` } }));
const nodes = new Map();
for (const id of ['commandsSearch', 'commandsCount', 'commandsEmpty', 'commandsSeeMore', 'commandsSeeMoreWrap']) {
    nodes.set(id, { value: '', hidden: false, textContent: '',
        addEventListener(name, fn) { this[name] = fn; }, querySelector() { return this; } });
}
vm.runInNewContext(script, { document: { querySelectorAll: () => rows, getElementById: id => nodes.get(id) } });
const visible = () => rows.filter(row => !row.hidden).length;
assert.equal(visible(), 7);
assert.equal(nodes.get('commandsSeeMore').textContent, 'See more (10 more)');
nodes.get('commandsSeeMore').click();
assert.equal(visible(), 14);
nodes.get('commandsSeeMore').click();
assert.equal(visible(), 17);
assert.equal(nodes.get('commandsSeeMoreWrap').hidden, true);
const search = nodes.get('commandsSearch');
search.value = '  UNIQUE   phrase '; search.input();
assert.equal(visible(), 1);
assert.equal(rows[16].hidden, false, 'Search includes commands beyond the first batch and full reply text');
search.value = 'missing'; search.input();
assert.equal(visible(), 0);
assert.equal(nodes.get('commandsEmpty').hidden, false);
search.value = ''; search.input();
assert.equal(visible(), 7, 'Changing the search resets the batch size');
assert.equal(nodes.get('commandsSeeMoreWrap').hidden, false);
console.log('Command search, 7-row batches, remaining counts, and empty results passed.');
