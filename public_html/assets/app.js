const translations = JSON.parse(document.querySelector('#translations')?.content.textContent || '{}');
const t = key => translations[key] || translations.unexpected_error || key;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const user = document.body.dataset.user || 'guest';
const toast = document.querySelector('#toast');
let toastTimer;

function announce(message) {
	clearTimeout(toastTimer);
	toast.textContent = message;
	toastTimer = setTimeout(() => { toast.textContent = ''; }, 6000);
}

async function request(url, input) {
	const controller = new AbortController();
	const timer = setTimeout(() => controller.abort(), 25000);
	try {
		const response = await fetch(url, { method: input === undefined ? 'GET' : 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', ...(input === undefined ? {} : { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf }) }, body: input === undefined ? undefined : JSON.stringify(input), signal: controller.signal });
		const data = await response.json();
		if (!response.ok) {
			const error = new Error(data.message || t(data.error || 'unexpected_error'));
			error.code = data.error;
			throw error;
		}
		return data;
	} catch (error) {
		if (error.code) throw error;
		throw new Error(t('network_error'));
	} finally { clearTimeout(timer); }
}

function storageGet(key) { try { return sessionStorage.getItem(key); } catch { return null; } }
function storageSet(key, value) { try { sessionStorage.setItem(key, value); } catch { /* Storage may be disabled by the browser. */ } }
function storageRemove(key) { try { sessionStorage.removeItem(key); } catch { /* No persisted draft to remove. */ } }
function draftKey(form) { return `dilemma:draft:${user}:${location.pathname}:${form.dataset.draft}`; }

function collect(form) {
	const data = Object.fromEntries(new FormData(form));
	for (const checkbox of form.querySelectorAll('input[type="checkbox"][name]')) data[checkbox.name] = checkbox.checked;
	for (const key of ['revision', 'position']) if (key in data) data[key] = Number(data[key]);
	if ('position_enabled' in data) {
		if (!data.position_enabled) data.position = null;
		delete data.position_enabled;
	}
	if ('approve' in data) data.approve = data.approve === 'true' || data.approve === true;
	if ('allow_write' in data) { data.scopes = data.allow_write ? 'debates:read arguments:write' : 'debates:read'; delete data.allow_write; }
	if ('sources_text' in data) {
		data.sources = data.sources_text.split('\n').map(line => line.trim()).filter(Boolean).map(url => ({ url, label: url }));
		delete data.sources_text;
	}
	if ('reply_to_id' in data) { data.reply_to = data.reply_to_id ? [data.reply_to_id] : []; delete data.reply_to_id; }
	if (form.action.endsWith('/api/admin') && data.debate_id && ['hide_debate', 'restore_debate'].includes(data.action)) data.target_id = data.debate_id;
	return data;
}

function updateScale(fieldset) {
	const check = fieldset.querySelector('[name="position_enabled"]');
	const range = fieldset.querySelector('[type="range"]');
	if (!check || !range) return;
	range.disabled = !check.checked;
	fieldset.querySelector('output').textContent = check.checked ? range.value : t('not_given');
}

function saveDraft(form) {
	if (!form.dataset.draft) return;
	const values = {};
	for (const control of form.elements) {
		if (!control.name || ['hidden', 'password', 'email'].includes(control.type) || control.name === 'chat_link') continue;
		values[control.name] = control.type === 'checkbox' ? control.checked : control.value;
	}
	storageSet(draftKey(form), JSON.stringify(values));
}

const languageSnapshotKey = `dilemma:language-forms:${user}:${location.pathname}`;
try {
	const snapshot = JSON.parse(storageGet(languageSnapshotKey) || 'null');
	storageRemove(languageSnapshotKey);
	if (snapshot) for (const [index, values] of snapshot.entries()) {
		const form = document.forms[index];
		if (!form) continue;
		for (const [name, value] of Object.entries(values)) {
			const controls = [...form.elements].filter(control => control.name === name);
			for (const control of controls) {
				if (control.type === 'radio') control.checked = control.value === value;
				else if (control.type === 'checkbox') control.checked = value === true;
				else if (control.type !== 'password') control.value = String(value);
			}
		}
	}
} catch { storageRemove(languageSnapshotKey); }

for (const form of document.querySelectorAll('form[data-draft]')) {
	const saved = storageGet(draftKey(form));
	if (saved) {
		try {
			for (const [name, value] of Object.entries(JSON.parse(saved))) {
				const control = form.elements.namedItem(name);
				if (!control || control.type === 'hidden') continue;
				if (control.type === 'checkbox') control.checked = value === true;
				else control.value = String(value);
			}
			form.querySelector('.form-feedback').textContent = t('draft_recovered');
		} catch { storageRemove(draftKey(form)); }
	}
	let timer;
	form.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => saveDraft(form), 350); });
}
for (const scale of document.querySelectorAll('.position-input')) {
	updateScale(scale);
	scale.addEventListener('input', () => updateScale(scale));
}

document.addEventListener('submit', async event => {
	const form = event.target;
	if (!(form instanceof HTMLFormElement) || !form.matches('[data-api]')) return;
	event.preventDefault();
	if (form.dataset.busy === 'true' || !form.reportValidity()) return;
	if (form.dataset.confirm && !window.confirm(t(form.dataset.confirm))) return;
	const input = collect(form);
	if (form.dataset.result === 'login') input.return_to = storageGet('dilemma:oauth-return') || (storageGet('dilemma:invitation') ? '/invite#' + storageGet('dilemma:invitation') : '');
	if (form.dataset.confirm) input.confirm = true;
	const fingerprint = JSON.stringify({ ...input, idempotency_key: undefined });
	if (form.dataset.fingerprint && form.dataset.fingerprint !== fingerprint) {
		input.idempotency_key = crypto.randomUUID();
		form.elements.namedItem('idempotency_key').value = input.idempotency_key;
	}
	form.dataset.fingerprint = fingerprint;
	form.dataset.busy = 'true';
	const feedback = form.querySelector('.form-feedback');
	feedback.classList.remove('error');
	feedback.textContent = t('working');
	const buttons = [...form.querySelectorAll('button')];
	buttons.forEach(button => { button.disabled = true; });
	saveDraft(form);
	try {
		const data = await request(new URL(form.action).pathname, input);
		if (form.dataset.draft) storageRemove(draftKey(form));
		feedback.textContent = t('saved');
		switch (form.dataset.result) {
			case 'login': feedback.textContent = t('check_mail'); break;
			case 'verify': {
				storageRemove('dilemma:oauth-return');
				location.assign(data.redirect);
				break;
			}
			case 'created': location.assign(`/debate/${encodeURIComponent(data.debate_id)}`); break;
			case 'accepted': storageRemove('dilemma:invitation'); location.assign(data.debate_id ? `/debate/${encodeURIComponent(data.debate_id)}` : '/app'); break;
			case 'deleted': clearPrivateStorage(); location.assign('/'); break;
			case 'flag': storageSet('dilemma:flag-replay', location.pathname); location.reload(); break;
			case 'oauth': location.assign(data.redirect); break;
			case 'invitation': {
				const result = form.parentElement.querySelector('.invitation-result');
				result.replaceChildren();
				const link = document.createElement('a'); link.href = data.invitation_url; link.textContent = data.invitation_url;
				const copy = document.createElement('button'); copy.type = 'button'; copy.className = 'button secondary'; copy.textContent = t('copy'); copy.dataset.copy = data.invitation_url;
				result.append(link, copy); result.hidden = false; break;
			}
			case 'connection': document.querySelector('#new-token').textContent = data.access_token; document.querySelector('#connection-result').hidden = false; break;
			case 'report': feedback.textContent = t('report_sent'); form.reset(); break;
			case 'saved': announce(t('saved')); break;
			default: location.reload();
		}
	} catch (error) {
		feedback.classList.add('error');
		feedback.textContent = error.message;
		feedback.tabIndex = -1;
		feedback.focus();
	} finally {
		form.dataset.busy = 'false';
		buttons.forEach(button => { button.disabled = false; });
	}
});

function clearPrivateStorage() {
	try { for (const key of Object.keys(sessionStorage)) if (key.startsWith('dilemma:')) sessionStorage.removeItem(key); } catch { /* Storage is optional. */ }
}

document.addEventListener('click', async event => {
	const copy = event.target.closest('[data-copy], [data-copy-target]');
	if (copy) {
		try { await navigator.clipboard.writeText(copy.dataset.copy ?? document.getElementById(copy.dataset.copyTarget).textContent); announce(t('copied')); }
		catch { announce(t('unexpected_error')); }
	}
	if (event.target.closest('[data-logout]')) {
		try { await request('/api/logout', {}); clearPrivateStorage(); location.assign('/'); } catch (error) { announce(error.message); }
	}
	if (event.target.closest('[data-replay-flag]')) showFlag();
	if (event.target.closest('[data-close-dialog]')) document.querySelector('#flag-dialog')?.close();
	const returnLink = event.target.closest('[data-auth-return]');
	if (returnLink) storageSet('dilemma:oauth-return', returnLink.dataset.authReturn);
	if (event.target.closest('.language-switch a')) {
		for (const form of document.querySelectorAll('form[data-draft]')) saveDraft(form);
		const snapshot = [...document.forms].map(form => {
			const values = Object.fromEntries(new FormData(form));
			for (const control of form.querySelectorAll('input[type="checkbox"][name]')) values[control.name] = control.checked;
			for (const control of form.querySelectorAll('input[type="password"][name]')) delete values[control.name];
			return values;
		});
		storageSet(languageSnapshotKey, JSON.stringify(snapshot));
		storageSet(`dilemma:scroll:${location.pathname}`, String(scrollY));
	}
	if (event.target.closest('[data-decline]')) {
		const form = document.querySelector('#invitation-form');
		try { await request('/api/accept', { token: form.elements.namedItem('token').value, accept: false, idempotency_key: crypto.randomUUID() }); storageRemove('dilemma:invitation'); location.assign('/app'); }
		catch (error) { announce(error.message); }
	}
	if (event.target.closest('[data-oauth-deny]')) {
		const form = event.target.closest('form');
		try { const result = await request('/api/oauth/authorize', { ...collect(form), approve: false }); location.assign(result.redirect); } catch (error) { announce(error.message); }
	}
});

function element(tag, text, className = '') {
	const node = document.createElement(tag);
	if (text !== undefined) node.textContent = text;
	if (className) node.className = className;
	return node;
}

function messageLink(id, text) {
	const link = element('a', text);
	link.href = `#argument-${encodeURIComponent(id)}`;
	link.addEventListener('click', () => {
		const details = document.getElementById(`argument-${id}`);
		if (details) details.open = true;
		else {
			const current = document.querySelector('[data-debate]')?.dataset.debate;
			request(`/api/arguments/${encodeURIComponent(id)}`).then(message => location.assign(`/debate/${current}?page=${Math.ceil(message.ordinal / 20)}#argument-${id}`)).catch(error => announce(error.message));
		}
	});
	return link;
}

async function loadArgument(details) {
	if (details.dataset.loaded === 'true' || details.dataset.loading === 'true') return;
	const output = details.querySelector('.argument-body');
	details.dataset.loading = 'true'; output.textContent = t('loading');
	try {
		const data = await request(`/api/arguments/${encodeURIComponent(details.dataset.argument)}`);
		output.replaceChildren(element('p', data.body || t('removed_content'), 'user-text'));
		if (data.client_label) output.append(element('p', `${t('client_label')}: ${data.client_label}`, 'muted'));
		for (const key of ['subarguments', 'objections']) if (data[key].length) {
			output.append(element('h3', t(key)));
			const list = element('ul'); data[key].forEach(text => list.append(element('li', text, 'user-text'))); output.append(list);
		}
		for (const key of ['rationale', 'conclusion', 'agent_assessment']) if (data[key]) { output.append(element('h3', t(key)), element('p', data[key], 'user-text')); if (key === 'agent_assessment') output.append(element('p', t('assessment_help'), 'muted')); }
		if (data.sources.length) {
			output.append(element('h3', t('sources')));
			const list = element('ul');
			for (const source of data.sources) {
				const item = element('li'); const link = element('a', source.label);
				if (new URL(source.url).protocol !== 'https:') continue;
				link.href = source.url; link.target = '_blank'; link.rel = 'noopener noreferrer'; item.append(link); list.append(item);
			}
			output.append(list);
		}
		if (data.amendments.length) {
			output.append(element('h3', t('amendments')));
			for (const amendment of data.amendments) output.append(element('time', amendment.created_at + ' UTC', 'muted'), element('p', amendment.clarification, 'user-text'));
		}
		for (const [key, label] of [['reply_to', 'original_argument'], ['replies', 'reply_links']]) if (data[key].length) {
			output.append(element('h3', t(label)));
			const list = element('ul');
			for (const reply of data[key]) { const item = element('li'); item.append(messageLink(typeof reply === 'string' ? reply : reply.id, typeof reply === 'string' ? t('original_argument') : `#${reply.ordinal} · ${reply.excerpt}`)); list.append(item); }
			output.append(list);
		}
		details.dataset.loaded = 'true';
	} catch (error) { output.textContent = error.message; }
	finally { details.dataset.loading = 'false'; }
}

async function loadEvents(details, reset = false) {
	if (details.dataset.loading === 'true') return;
	details.dataset.loading = 'true';
	const list = details.querySelector('.event-list'); const feedback = details.querySelector('.form-feedback'); const button = details.querySelector('[data-more-events]');
	if (reset) { list.replaceChildren(); details.dataset.after = '0'; }
	feedback.textContent = t('loading'); button.disabled = true;
	try {
		const params = new URLSearchParams({ after: details.dataset.after || '0', kind: details.querySelector('[data-event-filter]').value });
		const data = await request(`/api/debates/${encodeURIComponent(details.dataset.history)}/events?${params}`);
		for (const event of data.events) {
			const item = element('li'); item.append(element('strong', t(event.kind)), element('time', event.created_at + ' UTC'));
			if (event.actor_alias) item.append(element('span', event.actor_alias === '[deleted]' ? t('deleted_participant') : event.actor_alias));
			if (event.data.reason) item.append(element('span', t(event.data.reason)));
			if (event.data.message_id) item.append(messageLink(event.data.message_id, t('full_argument')));
			list.append(item);
		}
		details.dataset.after = String(data.next_sequence); details.dataset.loaded = 'true'; button.hidden = !data.has_more; feedback.textContent = '';
	} catch (error) { feedback.textContent = error.message; }
	finally { details.dataset.loading = 'false'; button.disabled = false; }
}

document.addEventListener('toggle', event => {
	const details = event.target;
	if (!(details instanceof HTMLDetailsElement) || !details.open) return;
	if (details.dataset.argument) loadArgument(details);
	if (details.dataset.history && details.dataset.loaded !== 'true') loadEvents(details);
}, true);
for (const details of document.querySelectorAll('[data-history]')) {
	details.querySelector('[data-more-events]').addEventListener('click', () => loadEvents(details));
	details.querySelector('[data-event-filter]').addEventListener('change', () => loadEvents(details, true));
}

const fragmentForm = document.querySelector('form[data-fragment]');
if (fragmentForm) {
	const token = location.hash.slice(1);
	if (/^[A-Za-z0-9_-]{43}$/.test(token)) fragmentForm.elements.namedItem('token').value = token;
	history.replaceState(null, '', location.pathname + location.search);
}

async function invitation() {
	const root = document.querySelector('#invitation');
	if (!root) return;
	const fragment = location.hash.slice(1);
	if (/^[A-Za-z0-9_-]{43}$/.test(fragment)) storageSet('dilemma:invitation', fragment);
	history.replaceState(null, '', location.pathname + location.search);
	if (root.dataset.signedIn !== 'true') return;
	const token = storageGet('dilemma:invitation') || '';
	const status = document.querySelector('#invitation-status');
	try {
		const data = await request('/api/invitation', { token });
		const context = document.querySelector('#invitation-context');
		context.append(element('h2', data.debate.title), element('p', data.debate.thesis, 'user-text'), element('h3', data.creator.alias), element('p', data.creator.stance, 'user-text'));
		context.append(element('p', t(Number(data.creator.public_consent) === 1 ? 'creator_public' : 'creator_private'), 'notice'));
		const form = document.querySelector('#invitation-form'); form.elements.namedItem('token').value = token;
		const labels = form.querySelectorAll('.scale-labels span'); labels[0].textContent = data.debate.left_label; labels[1].textContent = data.debate.right_label;
		form.hidden = false; status.textContent = '';
	} catch (error) { status.textContent = error.message; }
}
invitation();

function showFlag() { const dialog = document.querySelector('#flag-dialog'); if (dialog && !dialog.open) dialog.showModal(); }
if (storageGet('dilemma:flag-replay') === location.pathname) { storageRemove('dilemma:flag-replay'); showFlag(); }
if (location.hash.startsWith('#argument-')) { const details = document.getElementById(location.hash.slice(1)); if (details instanceof HTMLDetailsElement) details.open = true; }
const savedScroll = storageGet(`dilemma:scroll:${location.pathname}`);
if (savedScroll !== null) { storageRemove(`dilemma:scroll:${location.pathname}`); requestAnimationFrame(() => scrollTo(0, Number(savedScroll) || 0)); }
