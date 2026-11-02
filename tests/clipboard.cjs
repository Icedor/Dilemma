const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public_html/assets/clipboard.js', 'utf8').replace('export async function', 'async function');
let denied = false, legacy = true, copied = '', removed = false;
const details = { open: false };
const target = { value: 'synthetic-private-setup', selected: false, focus() {}, select() { this.selected = true; }, closest() { return details; }, remove() { removed = true; } };
const context = { navigator: { clipboard: { async writeText(text) { if (denied) throw Error('Denied'); copied = text; } } }, document: { createElement: () => ({ ...target }), body: { append() {} }, execCommand: () => legacy } };
vm.createContext(context); vm.runInContext(source, context);
(async () => {
	assert.equal(await context.copyText(target.value, target), true); assert.equal(copied, target.value);
	denied = true; assert.equal(await context.copyText(target.value, target), true); assert(target.selected); assert(details.open);
	legacy = false; assert.equal(await context.copyText(target.value, target), false); assert.equal(target.value, 'synthetic-private-setup'); assert(target.selected);
	await context.copyText('ordinary text'); assert(removed);
	console.log('PASS 4 clipboard success, fallback, manual-selection and cleanup checks');
})().catch(error => { console.error(error); process.exitCode = 1; });
