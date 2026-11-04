import { copyText } from './clipboard.js?v=20261102-7';
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
		if (control.type === 'radio' && !control.checked) continue;
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
				if (control.type === 'hidden' && name === 'locale') continue;
				if (control.type === 'radio') control.checked = control.value === value;
				else if (control.type === 'checkbox') control.checked = value === true;
				else if (control.type !== 'password') control.value = String(value);
			}
		}
	}
} catch { storageRemove(languageSnapshotKey); }
if (document.querySelector('[data-avatar-editor]')) import('./avatar.js?v=20261103-1').then(module => module.initAvatars(t)).catch(() => announce(t('network_error')));

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
for (const form of document.querySelectorAll('form:has([name="opponent_mode"])')) {
	const updateMode = () => {
		form.elements.namedItem('public_consent').required = form.elements.namedItem('opponent_mode').value === 'open';
	};
	form.addEventListener('change', updateMode);
	updateMode();
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
	if (form.dataset.result === 'login') input.return_to = input.return_to || storageGet('dilemma:oauth-return') || (storageGet('dilemma:invitation') ? '/invite#' + storageGet('dilemma:invitation') : '');
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
		if (new URL(form.action).pathname === '/api/profile') {
			try { localStorage.setItem('dilemma:locale', input.locale); } catch {}
			if (input.locale !== document.documentElement.lang) { const url = new URL(location.href); url.searchParams.set('lang', input.locale); location.assign(url.href); return; }
		}
		if (form.dataset.draft) storageRemove(draftKey(form));
		feedback.textContent = t('saved');
		switch (form.dataset.result) {
			case 'login': feedback.textContent = t('check_mail'); break;
			case 'verify': {
				storageRemove('dilemma:oauth-return');
				location.assign(data.redirect);
				break;
			}
			case 'created': { const oauthReturn = storageGet('dilemma:oauth-return'); storageRemove('dilemma:oauth-return'); location.assign(oauthReturn?.startsWith('/oauth/authorize?') ? oauthReturn + '&debate_id=' + encodeURIComponent(data.debate_id) : `/debate/${encodeURIComponent(data.debate_id)}#connect-ai`); break; }
			case 'accepted': storageRemove('dilemma:invitation'); location.assign(data.debate_id ? `/debate/${encodeURIComponent(data.debate_id)}#connect-ai` : '/app'); break;
			case 'deleted': clearPrivateStorage(); location.assign('/'); break;
			case 'flag': storageSet('dilemma:flag-replay', location.pathname); location.reload(); break;
			case 'oauth': location.assign(data.redirect); break;
			case 'invitation': {
				if (data.email_sent) { feedback.textContent = t('invitation_queued'); break; }
				const result = form.closest('.share-panel').querySelector('.invitation-result');
				result.replaceChildren();
				const label = document.createElement('label'); label.textContent = t('invitation_link');
				const link = document.createElement('input'); link.type = 'url'; link.readOnly = true; link.value = data.invitation_url; label.append(link);
				const actions = document.createElement('div'); actions.className = 'actions';
				const copy = document.createElement('button'); copy.type = 'button'; copy.className = 'button secondary'; copy.textContent = t('copy_link'); copy.dataset.copy = data.invitation_url;
				const share = document.createElement('button'); share.type = 'button'; share.className = 'button'; share.textContent = t('share'); share.dataset.share = data.invitation_url;
				actions.append(share, copy); result.append(label, actions); result.hidden = false; feedback.textContent = '';
				if (form.classList.contains('share-link-form')) form.hidden = true;
				share.focus(); break;
			}
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
		buttons.forEach(button => { button.disabled = form.closest('.compose')?.dataset.canPublish === 'false'; });
	}
});

function clearPrivateStorage() {
	clearSetup();
	try { for (const key of Object.keys(sessionStorage)) if (key.startsWith('dilemma:')) sessionStorage.removeItem(key); } catch { /* Storage is optional. */ }
}

let waitingExpiry;
function renderWaiting(waiting = {}) {
	const button = document.querySelector('#ai-wait-toggle');
	if (!button) return;
	clearTimeout(waitingExpiry);
	const until = Date.parse(waiting.until || '');
	const enabled = Boolean(waiting.enabled && until > Date.now());
	button.setAttribute('aria-pressed', String(enabled));
	button.dataset.until = enabled ? waiting.until : '';
	button.textContent = t(enabled ? 'ai_wait_stop' : 'ai_wait_start');
	document.querySelector('#ai-wait-status').textContent = enabled ? t('ai_wait_until') + ' ' + new Intl.DateTimeFormat(document.documentElement.lang, { hour: '2-digit', minute: '2-digit' }).format(until) + '.' : '';
	if (enabled) waitingExpiry = setTimeout(() => {
		renderWaiting();
		document.querySelector('#ai-wait-status').textContent = t('ai_wait_expired');
	}, Math.min(until - Date.now() + 50, 7200050));
}
const waitingButton = document.querySelector('#ai-wait-toggle');
if (waitingButton) renderWaiting({ enabled: waitingButton.getAttribute('aria-pressed') === 'true', until: waitingButton.dataset.until });

function chooseAiApp() {
	const choice = document.querySelector('#ai-app-choice');
	if (!choice) return;
	const native = choice.value === 'native' || choice.value === 'other';
	for (const panel of document.querySelectorAll('[data-app-route]')) panel.hidden = !((panel.dataset.appRoute === choice.value && choice.value !== '') || (['server', 'fallback'].includes(panel.dataset.appRoute) && native));
	const copy = document.querySelector('[data-ai-copy]');
	copy.disabled = !choice.value;
	copy.hidden = native || !choice.value;
	copy.dataset.copyTarget = native ? 'ai-native-setup' : 'ai-setup';
	const details = document.querySelector('.setup-text-disclosure');
	if (details) details.hidden = native || !choice.value;
}
document.querySelector('#ai-app-choice')?.addEventListener('change', chooseAiApp);
chooseAiApp();

function renderSetup(data) {
	const section = document.querySelector('[data-debate-ai]');
	const target = document.querySelector('#ai-setup');
	if (!section || !target) return;
	target.value = data.setup_text || '';
	if (data.native_setup) document.querySelector('#ai-native-setup').value = data.native_setup;
	document.querySelector('#ai-header').value = (target.value.match(/Authorization: (Bearer [A-Za-z0-9_-]{43})/) || [])[1] || '';
	chooseAiApp();
	renderWaiting(data.waiting);
	document.querySelector('#connection-result').hidden = data.revoked || !target.value;
	document.querySelector('#connection-copy-status').textContent = data.revoked ? t('setup_removed') : '';
	const revoke = document.querySelector('#revoke-new-connection');
	revoke.dataset.connectionId = data.connection_id; revoke.disabled = false; revoke.hidden = Boolean(data.revoked);
	document.querySelector('#renew-debate-connection').hidden = !data.revoked;
	document.querySelector('#retry-debate-connection').hidden = true;
	section.dataset.setupReady = 'true';
}

async function loadDebateSetup(renew = false) {
	const section = document.querySelector('[data-debate-ai]');
	if (!section || section.dataset.setupBusy === 'true') return;
	section.dataset.setupBusy = 'true';
	const restore = document.querySelector('#renew-debate-connection');
	restore.disabled = true;
	try {
		const data = await request('/api/connections/setup', { debate_id: section.dataset.debateAi, renew });
		renderSetup(data);
	} catch (error) {
		document.querySelector('#connection-copy-status').textContent = error.message;
		document.querySelector('#retry-debate-connection').hidden = false;
	} finally { section.dataset.setupBusy = 'false'; restore.disabled = false; }
}

function clearSetup() {
	for (const target of document.querySelectorAll('[data-private-setup]')) target.value = '';
	for (const overview of document.querySelectorAll('.overview-setup')) overview.hidden = true;
	if (!document.querySelector('[data-debate-ai]')) return;
	document.querySelector('#connection-result').hidden = true;
	document.querySelector('[data-debate-ai]').dataset.setupReady = 'false';
	delete document.querySelector('#revoke-new-connection').dataset.connectionId;
}
window.addEventListener('pagehide', clearSetup);
window.addEventListener('pageshow', event => { if (event.persisted) loadDebateSetup(); });
if (document.querySelector('[data-debate-ai][data-setup-ready="false"]')) loadDebateSetup();

document.addEventListener('click', async event => {
	const waitToggle = event.target.closest('#ai-wait-toggle');
	if (waitToggle && !waitToggle.disabled) {
		const enabled = waitToggle.getAttribute('aria-pressed') !== 'true';
		waitToggle.disabled = true;
		try {
			const data = await request('/api/connections/waiting', { debate_id: document.querySelector('[data-debate-ai]').dataset.debateAi, enabled });
			renderSetup(data);
			document.querySelector('#connection-copy-status').textContent = t(enabled ? 'ai_wait_ready' : 'ai_wait_off');
		} catch (error) { document.querySelector('#connection-copy-status').textContent = error.message; }
		finally { waitToggle.disabled = false; }
	}
	const share = event.target.closest('[data-share]');
	if (share) {
		try {
			if (navigator.share) await navigator.share({ title: 'Dilemma', url: share.dataset.share });
			else announce(t(await copyText(share.dataset.share) ? 'copied' : 'setup_copy_failed'));
		} catch (error) { if (error.name !== 'AbortError') announce(t('network_error')); }
	}
	const copy = event.target.closest('[data-copy], [data-copy-target]');
	if (copy) {
		const target = copy.dataset.copyTarget ? document.getElementById(copy.dataset.copyTarget) : null;
		const value = copy.dataset.copy ?? (target && 'value' in target ? target.value : target?.textContent);
		if (typeof value === 'string' && value !== '') {
			const copied = await copyText(value, target);
			announce(t(copied ? 'copied' : 'setup_copy_failed'));
			if (target?.matches('[data-private-setup]')) document.querySelector('#connection-copy-status').textContent = t(copied ? 'setup_copied' : 'setup_copy_failed');
			if (copied) copy.focus();
		}
	}
	if (event.target.closest('#renew-debate-connection')) await loadDebateSetup(true);
	if (event.target.closest('#retry-debate-connection')) await loadDebateSetup();
	const revokeNew = event.target.closest('#revoke-new-connection');
	if (revokeNew?.dataset.connectionId) {
		revokeNew.disabled = true;
		try { await request('/api/connections/' + encodeURIComponent(revokeNew.dataset.connectionId) + '/revoke', {}); renderSetup({ revoked: true, connection_id: revokeNew.dataset.connectionId, setup_text: '' }); }
		catch (error) { announce(error.message); revokeNew.disabled = false; }
	}
	if (event.target.closest('[data-logout]')) {
		try { await request('/api/logout', {}); clearPrivateStorage(); location.assign('/'); } catch (error) { announce(error.message); }
	}
	if (event.target.closest('[data-replay-flag]')) showFlag();
	if (event.target.closest('[data-close-dialog]')) document.querySelector('#flag-dialog')?.close();
	const returnLink = event.target.closest('[data-auth-return]');
	if (returnLink) storageSet('dilemma:oauth-return', returnLink.dataset.authReturn);
	if (event.target.closest('.language-switch a')) {
		try { localStorage.setItem('dilemma:locale', new URL(event.target.closest('.language-switch a').href).searchParams.get('lang')); } catch {}
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

// Only append new cards: existing text, selection, open panels and form nodes survive.
const flowPanel = document.querySelector('[data-flow-revision]');
const flowDebate = document.querySelector('[data-debate]')?.dataset.debate;
const liveArguments = document.querySelector('[data-live-after]');
function readingAnchor() {
	const focused = document.activeElement;
	if (focused && focused !== document.body && focused.matches('input, textarea, select, button, summary, a')) {
		const bounds = focused.getBoundingClientRect();
		if (bounds.bottom > 0 && bounds.top < innerHeight) return focused;
	}
	const column = document.querySelector('.debate-main')?.getBoundingClientRect();
	const x = column ? Math.max(8, Math.min(innerWidth - 8, column.left + Math.min(column.width / 2, 200))) : innerWidth / 2;
	const hit = document.elementFromPoint(x, Math.min(120, innerHeight / 3));
	return hit?.closest('p, summary, h1, h2, h3, label, li, .argument, .debate-management') || null;
}
function preserveReading(change) {
	const anchor = readingAnchor();
	const top = anchor?.getBoundingClientRect().top;
	change();
	if (anchor?.isConnected) {
		const shift = anchor.getBoundingClientRect().top - top;
		if (Math.abs(shift) > .5) window.scrollBy({ top: shift, behavior: 'instant' });
	}
}
function applyLiveDebate(state) {
	let added = 0;
	const timeline = liveArguments.querySelector('.argument-timeline');
	preserveReading(() => {
		for (const card of state.arguments) {
			if (document.getElementById('argument-' + card.id)) continue;
			const template = document.createElement('template');
			template.innerHTML = card.html;
			const node = template.content.firstElementChild;
			if (!node?.matches('details.argument') || node.dataset.argument !== card.id || node.id !== 'argument-' + card.id) throw new Error('Invalid argument card');
			if (Number(liveArguments.dataset.historyPage) < Number(liveArguments.dataset.initialPages) && !timeline.querySelector('.live-divider')) timeline.append(element('h3', t('live_new_arguments'), 'live-divider'));
			node.classList.add('argument-arrived'); timeline.append(node); added++;
			const replies = document.querySelector('.compose [name=reply_to_id]');
			if (replies) { const option = element('option', '#' + card.ordinal + ' · ' + node.querySelector('.argument-meta strong').textContent); option.value = card.id; replies.append(option); }
		}
		if (added) liveArguments.querySelector('.empty-message')?.remove();
		liveArguments.dataset.liveAfter = String(state.next_ordinal);
		flowPanel.dataset.flowRevision = String(state.revision);
		liveArguments.querySelector('.section-heading > span').textContent = Math.ceil(state.message_count / 2) + ' ' + t('rounds');
		const selection = window.getSelection();
		const readingStatus = flowPanel.contains(document.activeElement) || (selection && !selection.isCollapsed && flowPanel.contains(selection.anchorNode));
		if (!readingStatus) {
			flowPanel.querySelector('h2').textContent = state.title;
			flowPanel.querySelector('.section-heading > a').textContent = state.message_count + ' ' + t('arguments_count');
			const hint = flowPanel.querySelector('[data-flow-hint]'); hint.textContent = state.hint; hint.hidden = !state.hint;
			const assessment = flowPanel.querySelector('[data-live-assessment]');
			assessment.hidden = !state.assessment;
			if (state.assessment && assessment.dataset.assessmentId !== state.assessment.id) {
				assessment.querySelector('p').textContent = state.assessment.excerpt;
				assessment.querySelector('a').href = state.assessment.url;
				assessment.dataset.assessmentId = state.assessment.id;
			}
			const people = flowPanel.querySelector('.connection-statuses');
			if (JSON.stringify(state.participants) !== people.dataset.livePeople) {
				people.replaceChildren(...state.participants.map(person => { const li = element('li'); li.append(element('strong', person.alias), element('span', person.connected)); return li; }));
				people.dataset.livePeople = JSON.stringify(state.participants);
			}
		}
		const badge = document.querySelector('.debate-heading-meta .status');
		if (badge) { badge.textContent = state.status_label; badge.className = 'status status-' + state.status; }
		const visibility = document.querySelector('.debate-heading-meta > span:last-child');
		if (visibility) visibility.textContent = state.public_label;
		const decision = document.querySelector('[data-live-decision]');
		if (decision && !decision.contains(document.activeElement)) decision.hidden = !state.can_decide;
		const compose = document.querySelector('.compose');
		if (compose) {
			compose.dataset.canPublish = String(state.can_publish);
			const hint = compose.querySelector('[data-compose-wait]');
			hint.hidden = state.can_publish;
			hint.textContent = t(['ended', 'flagged', 'archived'].includes(state.status) ? 'live_draft_closed' : 'live_draft_wait');
			const form = compose.querySelector('form');
			if (form.dataset.busy !== 'true') {
				form.querySelector('[type="submit"]').disabled = !state.can_publish;

			}
		}
		// Preserve the receipt identity of an already-submitted request, including uncertain failures.
		for (const input of document.querySelectorAll('form [name=revision]')) if (input.form.dataset.busy !== 'true' && !input.form.dataset.fingerprint) input.value = String(state.revision);
		if (state.message_count > 0) for (const panel of document.querySelectorAll('[data-live-history]')) panel.hidden = false;
	});
	if (added) announce(added === 1 ? t('live_added_one') : t('live_added').replace('{count}', String(added)));
	return added;
}
if (flowPanel && flowDebate && liveArguments) {
	let polling = false, stopped = false;
	const retry = flowPanel.querySelector('[data-retry-live]');
	const poll = async () => {
		if (polling || stopped) return;
		if (document.hidden) { setTimeout(poll, 20000); return; }
		polling = true;
		try {
			const state = await request('/api/debates/' + encodeURIComponent(flowDebate) + '/live?after=' + encodeURIComponent(liveArguments.dataset.liveAfter));
			applyLiveDebate(state);
			preserveReading(() => { flowPanel.querySelector('.debate-update').hidden = true; });
			setTimeout(poll, 20000);
		} catch {
			// No automatic retry after revoked access, rate limits or a failed connection.
			stopped = true;
			preserveReading(() => { flowPanel.querySelector('.debate-update').hidden = false; });
		} finally { polling = false; retry.disabled = false; }
	};
	setTimeout(poll, 20000);
	retry.addEventListener('click', () => { if (polling) return; stopped = false; retry.disabled = true; poll(); });
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
	const token = /^[A-Za-z0-9_-]{43}$/.test(fragment) ? fragment : storageGet('dilemma:invitation') || '';
	const status = document.querySelector('#invitation-status');
	if (!token) {
		status.hidden = true; document.querySelector('#invitation-empty').hidden = false; return;
	}
	try {
		const data = await request('/api/invitation', { token });
		status.hidden = true;
		if (!data.debate) { document.querySelector('#invitation-locked').hidden = false; return; }
		document.querySelector('#invitation-title').textContent = data.debate.title;
		const premise = document.querySelector('.invitation-premise');
		const thesis = document.querySelector('[data-invite-thesis]');
		thesis.textContent = data.debate.thesis !== data.debate.title ? data.debate.thesis : '';
		thesis.hidden = !thesis.textContent;
		const introduction = document.querySelector('[data-invite-introduction]');
		introduction.textContent = data.debate.introduction; introduction.hidden = !introduction.textContent;
		premise.hidden = !thesis.textContent && !introduction.textContent;
		document.querySelector('[data-invite-author]').textContent = data.creator.alias;
		document.querySelector('[data-invite-stance]').textContent = data.creator.stance;
		document.querySelector('[data-invite-visibility]').textContent = t(Number(data.creator.public_consent) === 1 ? 'creator_public' : 'creator_private');
		document.querySelector('#invitation-content').hidden = false;
		const form = document.querySelector('#invitation-form');
		if (form && data.already_member) {
			const message = element('p', t('invite_already_joined'));
			const link = element('a', t('invite_go_debate'), 'button');
			link.href = '/debate/' + encodeURIComponent(data.debate.id);
			const actions = element('div', undefined, 'actions'); actions.append(link);
			form.after(message, actions);
		} else if (form) {
			form.elements.namedItem('token').value = token;
			form.dataset.draft = 'invitation-' + data.debate.id;
			let draftTimer;
			form.addEventListener('input', () => { clearTimeout(draftTimer); draftTimer = setTimeout(() => saveDraft(form), 350); });
			try {
				const draft = JSON.parse(storageGet(draftKey(form)) || 'null');
				if (draft?.stance && !form.elements.namedItem('stance').value) form.elements.namedItem('stance').value = draft.stance;
			} catch { /* Ignore a damaged local draft. */ }
			const labels = form.querySelectorAll('.scale-labels span'); labels[0].textContent = data.debate.left_label; labels[1].textContent = data.debate.right_label;
			form.hidden = false;
		}
	} catch (error) { status.hidden = false; status.textContent = error.message; }
}
invitation();

function showFlag() { const dialog = document.querySelector('#flag-dialog'); if (dialog && !dialog.open) dialog.showModal(); }
if (storageGet('dilemma:flag-replay') === location.pathname) { storageRemove('dilemma:flag-replay'); showFlag(); }
function revealAnchor() {
	const target = document.getElementById(location.hash.slice(1));
	if (!target) return;
	for (let node = target; node; node = node.parentElement) if (node instanceof HTMLDetailsElement) node.open = true;
	if (location.hash) target.scrollIntoView({ block: 'start' });
}
window.addEventListener('hashchange', revealAnchor);
revealAnchor();
const savedScroll = storageGet(`dilemma:scroll:${location.pathname}`);
if (savedScroll !== null) { storageRemove(`dilemma:scroll:${location.pathname}`); requestAnimationFrame(() => scrollTo(0, Number(savedScroll) || 0)); }

if (document.querySelector('.pwa-controls')) import('./pwa.js?v=20261102-3').then(module => module.setup({ request, t })).catch(() => {});

// Exact cross-browser console credit from Secret.icey.se.
console.log('%c' + String.raw`██╗ ██████╗███████╗██╗   ██╗
██║██╔════╝██╔════╝╚██╗ ██╔╝
██║██║     █████╗   ╚████╔╝
██║██║     ██╔══╝    ╚██╔╝
██║╚██████╗███████╗   ██║
╚═╝ ╚═════╝╚══════╝   ╚═╝
Icey.se`, 'font-family:Menlo,Consolas,monospace;font-size:11px;line-height:1.2;white-space:pre');
