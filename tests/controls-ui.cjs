const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public_html/assets/app.js', 'utf8');
const render = source.slice(source.indexOf('let waitingExpiry;'), source.indexOf('function chooseAiApp'));
const click = source.slice(source.indexOf("\tconst waitToggle = event.target.closest('#ai-wait-toggle');"), source.indexOf("\tconst share = event.target.closest('[data-share]');"));
const state = { textContent: '' }, status = { textContent: '' }, feedback = { textContent: '' };
const attributes = { 'aria-checked': 'false' };
const button = {
	dataset: {}, disabled: false,
	setAttribute(name, value) { attributes[name] = value; },
	removeAttribute(name) { delete attributes[name]; },
	getAttribute(name) { return attributes[name]; },
	querySelector(selector) { assert.equal(selector, '.switch-state'); return state; }
};
let timeout, calls = 0;
const nodes = { '#ai-wait-toggle': button, '#ai-wait-status': status, '#connection-copy-status': feedback, '[data-debate-ai]': { dataset: { debateAi: 'fixture' } } };
const context = {
	document: { querySelector: selector => nodes[selector], documentElement: { lang: 'sv' } },
	t: key => key, Date, Intl,
	setTimeout: callback => { timeout = callback; return 1; }, clearTimeout: () => { timeout = null; },
	request: async (url, input) => { calls++; assert.equal(url, '/api/connections/waiting'); return { waiting: { enabled: input.enabled, until: new Date(Date.now() + 7200000).toISOString() } }; }
};
vm.createContext(context);
vm.runInContext(render, context);
context.renderSetup = data => context.renderWaiting(data.waiting);
vm.runInContext('async function handleClick(event) {' + click + '\n}', context);
const event = { target: { closest: () => button } };
(async () => {
	assert.equal(state.textContent, 'control_off');
	await context.handleClick(event);
	assert.equal(attributes['aria-checked'], 'true'); assert.equal(state.textContent, 'control_on');
	assert.equal(button.disabled, false); assert.equal(attributes['aria-busy'], undefined);
	assert.equal(feedback.textContent, 'ai_wait_ready');
	assert.equal(typeof timeout, 'function');
	timeout();
	assert.equal(attributes['aria-checked'], 'false'); assert.equal(state.textContent, 'control_off'); assert.equal(status.textContent, 'ai_wait_expired');
	context.request = async () => { calls++; throw new Error('offline'); };
	await context.handleClick(event);
	assert.equal(attributes['aria-checked'], 'false'); assert.equal(button.disabled, false); assert.equal(feedback.textContent, 'offline');
	button.disabled = true;
	await context.handleClick(event);
	assert.equal(calls, 2);
	console.log('PASS Waiting switch on/off, accessible state, expiry, server failure and duplicate-click guard.');
})().catch(error => { console.error(error); process.exitCode = 1; });
