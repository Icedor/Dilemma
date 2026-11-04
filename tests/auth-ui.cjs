const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const app = fs.readFileSync('public_html/assets/app.js', 'utf8');
const functions = app.slice(app.indexOf('function showEmailSent'), app.indexOf('function collect'));
const fragment = app.slice(app.indexOf('const fragmentForm ='), app.indexOf('async function invitation'));

function node() {
	return { hidden: false, disabled: true, textContent: '', value: '', focused: false, focus() { this.focused = true; } };
}
const address = node(), heading = node(), fields = node(), alias = node(), feedback = node(), submit = node(), title = node(), intro = node();
const sent = { hidden: true, querySelector: selector => selector === 'h2' ? heading : address };
const panel = { querySelector: selector => ({ '[data-auth-sent]': sent, '[data-auth-title]': title, '[data-auth-intro]': intro })[selector] };
const form = { hidden: false, closest: () => panel, elements: { namedItem: () => alias }, querySelector: selector => ({ '[data-registration]': fields, '[type="submit"]': submit, '.form-feedback': feedback })[selector] };
const context = { t: key => key };
vm.createContext(context);
vm.runInContext(functions, context);
context.showEmailSent(form, 'person+<img>@example.test');
assert.equal(address.textContent, 'person+<img>@example.test');
assert.equal(form.hidden, true);
assert.equal(sent.hidden, false);
assert.equal(heading.focused, true);
context.showRegistration(form);
assert.equal(fields.hidden, false);
assert.equal(fields.disabled, false);
assert.equal(submit.textContent, 'auth_finish');
assert.equal(title.textContent, 'auth_finish_title');
assert.equal(alias.focused, true);

function restore(hash, stored, initial = '', blocked = false) {
	const field = { value: initial };
	let value = stored, cleaned = false;
	vm.runInNewContext(fragment, {
		document: { querySelector: () => ({ elements: { namedItem: () => field } }) },
		location: { hash, pathname: '/verify', search: '?lang=en' },
		history: { replaceState: () => { cleaned = true; } },
		storageGet: () => blocked ? null : value,
		storageSet: (_, next) => { if (!blocked) value = next; },
		storageRemove: () => { value = null; }, Date
	});
	return { field, value, cleaned };
}
const token = 'x'.repeat(43);
const first = restore('#' + token, null);
assert.equal(first.field.value, token);
assert.equal(first.cleaned, true);
assert.equal(restore('', first.value).field.value, token);
assert.equal(restore('', JSON.stringify({token, until: Date.now() - 1})).field.value, '');
assert.equal(restore('#' + token, null, '', true).field.value, token);
assert.equal(restore('', null, token, true).field.value, token);
assert.equal(restore('#invalid', '{broken').field.value, '');
console.log('PASS Account UI switches, safe address text, focus, reload/language recovery, expiry and unavailable storage.');
