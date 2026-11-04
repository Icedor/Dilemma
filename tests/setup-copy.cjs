const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public_html/assets/app.js', 'utf8');
const loader = source.slice(source.indexOf('async function loadDebateSetup'), source.indexOf('function clearSetup'));
const click = source.slice(source.indexOf('\tconst copy = event.target.closest'), source.indexOf("\tif (event.target.closest('#renew-debate-connection'))"));
const section = { dataset: { debateAi: 'expected-debate' } }, restore = { disabled: false }, status = {}, retry = {};
const target = { value: 'stale-key', matches: () => true };
const copy = { dataset: { copyTarget: 'ai-setup' }, focus() {} };
let response, fail = false, reads = 0, copied = [];
const context = {
	document: { getElementById: () => target, querySelector: selector => ({ '[data-debate-ai]': section, '#renew-debate-connection': restore, '#connection-copy-status': status, '#retry-debate-connection': retry })[selector] },
	event: { target: { closest: () => copy } }, t: key => key, announce() {},
	request: async (path, input) => { reads++; assert.equal(path, '/api/connections/setup'); assert.equal(input.debate_id, 'expected-debate'); assert.equal(input.renew, false); if (fail) throw Error('unavailable'); return response; },
	renderSetup: data => { target.value = data.setup_text; },
	copyText: async text => { copied.push(text); return true; }
};
vm.createContext(context); vm.runInContext(loader, context);
async function copyNow() { await vm.runInContext('(async()=>{'+click+'})()', context); }
(async()=>{
	response = { setup_text: 'replacement-key', revoked: false }; await copyNow();
	assert.deepEqual(copied, ['replacement-key']); assert.equal(reads, 1);
	response = { setup_text: '', revoked: true }; await copyNow(); assert.equal(copied.length, 1);
	target.value = 'stale-key'; fail = true; await copyNow(); assert.equal(copied.length, 1); assert.equal(retry.hidden, false);
	fail = false; section.dataset.setupBusy = 'true'; await copyNow(); assert.equal(copied.length, 1); assert.equal(reads, 3);
	console.log('PASS Private setup refreshes before copy; revoked, failed and concurrent refreshes never copy stale access.');
})().catch(error=>{ console.error(error); process.exitCode=1; });
