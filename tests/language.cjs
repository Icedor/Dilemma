const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public_html/assets/language.js', 'utf8');
let checks = 0;
function run(url, lang, saved, blocked = false) {
	const state = { saved, replaced: null, events: {} };
	const window = { addEventListener: (name, fn) => state.events[name] = fn, dispatchEvent() {} };
	vm.runInNewContext(source, { URL, Event: class {}, window, document: { documentElement: { lang } }, location: { href: url, replace: value => state.replaced = value }, localStorage: { getItem: () => { if (blocked) throw Error(); return state.saved; }, setItem: (_, value) => { if (blocked) throw Error(); state.saved = value; } } });
	return { state, window };
}
function check(fn) { fn(); checks++; }
check(() => { const { state } = run('https://dilemma.se/', 'sv', null); assert.equal(state.saved, null); assert.equal(state.replaced, null); });
check(() => { const { state } = run('https://dilemma.se/?lang=en', 'en', 'sv'); assert.equal(state.saved, 'en'); assert.equal(state.replaced, null); });
check(() => { const { state } = run('https://dilemma.se/invite?source=friend#private-token', 'sv', 'en'); assert.equal(state.replaced, 'https://dilemma.se/invite?source=friend&lang=en#private-token'); });
check(() => { assert.equal(run('https://dilemma.se/', 'sv', 'broken').state.replaced, null); });
check(() => { assert.equal(run('https://dilemma.se/', 'sv', 'en', true).state.replaced, null); });
check(() => { assert.equal(run('https://dilemma.se/', 'en', 'en').state.replaced, null); });
check(() => { const { state, window } = run('https://dilemma.se/', 'en', null); let prevented = false; const event = { preventDefault() { prevented = true; } }; state.events.beforeinstallprompt(event); assert.equal(prevented, true); assert.equal(window.dilemmaInstallPrompt, event); });
console.log(`PASS ${checks} language persistence and deferred installation checks`);
