const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public_html/sw.js', 'utf8');
const handlers = {}, cached = [], notices = [], opened = [];
let network = true, checks = 0;
const context = {
	URL,
	self: { location: { origin: 'https://dilemma.se' }, addEventListener: (name, fn) => handlers[name] = fn, registration: { showNotification: async (title, options) => notices.push({ title, options }) } },
	caches: { open: async () => ({ addAll: async paths => cached.push(...paths) }), keys: async () => ['unrelated-cache', 'dilemma-shell-old', 'dilemma-shell-v3'], delete: async key => cached.push(`delete:${key}`), match: async path => `cached:${path}` },
	fetch: async request => { if (!network) throw Error('Offline'); return { url: request.url, privateResponse: true }; },
	clients: { matchAll: async () => [], openWindow: async url => opened.push(url) }
};
vm.runInNewContext(source, context);
async function event(name, data = {}) { let promise; handlers[name]({ ...data, waitUntil(value) { promise = value; }, respondWith(value) { promise = value; } }); return promise; }
(async () => {
	await event('install'); assert.deepEqual(cached, ['/offline.html', '/assets/icon-192.png', '/assets/gradient-yellow.webp']); checks++;
	await event('activate'); assert(cached.includes('delete:dilemma-shell-old')); assert(!cached.includes('delete:unrelated-cache')); checks++;
	const request = { mode: 'navigate', url: 'https://dilemma.se/verify#private-token' };
	const response = await event('fetch', { request }); assert.equal(response.url, request.url); assert(!cached.some(value => value.includes('verify'))); checks++;
	network = false; assert.equal(await event('fetch', { request }), 'cached:/offline.html'); checks++;
	assert.equal(await event('fetch', { request: { mode: 'cors', url: 'https://dilemma.se/api/mine' } }), undefined); checks++;
	await event('push', { data: { text: () => 'private attacker text' } }); assert.equal(notices.length, 1); assert(!notices[0].options.body.includes('private')); checks++;
	let closed = false; await event('notificationclick', { notification: { close() { closed = true; } } }); assert(closed); assert.deepEqual(opened, ['https://dilemma.se/app']); checks++;
	console.log(`PASS ${checks} service-worker privacy and notification checks`);
})().catch(error => { console.error(error); process.exitCode = 1; });
