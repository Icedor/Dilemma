const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const app = fs.readFileSync('public_html/assets/app.js', 'utf8');
const source = app.slice(app.indexOf('const flowPanel ='), app.indexOf('const fragmentForm ='));
function harness() {
	const state = { calls: 0, hidden: false, revision: '8', fail: false, timers: [], delays: [], update: { hidden: true }, statuses: [], reloads: 0, refresh: null };
	const panel = { dataset: { flowRevision: '8' }, querySelector: selector => selector === '.debate-update' ? state.update : { addEventListener: (_, fn) => { state.refresh = fn; } }, querySelectorAll: () => state.statuses };
	vm.runInNewContext(source, { document: { get hidden() { return state.hidden; }, querySelector: selector => selector === '[data-flow-revision]' ? panel : { dataset: { debate: 'fixture' } } }, request: async () => { state.calls++; if (state.fail) throw Error('network failure'); return { revision: state.revision, active_participants: [] }; }, setTimeout: (fn, delay) => { state.timers.push(fn); state.delays.push(delay); }, location: { reload: () => { state.reloads++; } }, encodeURIComponent, t: key => key });
	state.tick = async () => { const next = state.timers.shift(); assert.equal(typeof next, 'function'); await next(); };
	return state;
}
(async () => {
	let state = harness();
	for (let i = 0; i < 24; i++) await state.tick();
	assert.equal(state.calls, 24); assert.equal(state.timers.length, 1); assert(state.delays.every(ms => ms >= 20000));
	state.hidden = true; await state.tick(); assert.equal(state.calls, 24); assert.equal(state.timers.length, 1);
	state.hidden = false; state.revision = '9'; await state.tick(); assert.equal(state.update.hidden, false); assert.equal(state.timers.length, 0); assert.equal(state.reloads, 0);
	state.refresh(); assert.equal(state.reloads, 1);
	state = harness(); state.fail = true; await state.tick(); assert.equal(state.timers.length, 0); assert.equal(state.calls, 1);
	console.log('PASS 5 debate-update checks: prolonged wait, hidden tab, changed revision preserves drafts, explicit refresh, failure stops.');
})();
