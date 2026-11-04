export async function setup({ request, t }) {
	const panels = [...document.querySelectorAll('.pwa-controls')];
	const status = text => panels.forEach(panel => { panel.querySelector('[data-pwa-status]').textContent = text; });
	const enables = panels.map(panel => panel.querySelector('[data-push-enable]'));
	const disables = panels.map(panel => panel.querySelector('[data-push-disable]'));
	const installs = panels.map(panel => panel.querySelector('[data-install]'));
	let registration, config, busy = false;
	const installed = () => matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
	const updateInstall = () => installs.forEach(button => { button.hidden = installed(); });
	updateInstall();
	window.addEventListener('appinstalled', () => { window.dilemmaInstallPrompt = null; installs.forEach(button => { button.hidden = true; }); status(t('app_installed')); });
	for (const button of installs) button.addEventListener('click', async () => {
		if (window.dilemmaInstallPrompt) {
			const prompt = window.dilemmaInstallPrompt;
			window.dilemmaInstallPrompt = null;
			await prompt.prompt();
			await prompt.userChoice;
		} else status(t('install_help'));
	});
	const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && isSecureContext;
	const preference = () => t(config?.notifications_enabled === false ? 'notifications_paused' : config?.enabled ? (config.device ? 'push_active' : config.devices > 0 ? 'push_other_device' : 'push_email_fallback') : 'push_optional');
	const paint = () => {
		enables.forEach(button => { button.disabled = busy || !supported || !config || !registration; (button.querySelector?.('[data-control-label]') || button).textContent = t(config?.enabled && config?.device ? 'push_active' : 'push_enable'); button.hidden = !!(config?.enabled && config?.device); });
		disables.forEach(button => { button.hidden = !config?.enabled; button.disabled = busy; });
	};
	paint();
	try {
		if ('serviceWorker' in navigator && isSecureContext) {
			await navigator.serviceWorker.register('/sw.js');
			registration = await navigator.serviceWorker.ready;
		}
		config = await request('/api/push');
		if (supported && config.device && !(await registration.pushManager.getSubscription())) config.device = false;
		status(preference() + (!supported ? ' ' + t('push_unsupported') : ''));
	} catch { status(t('push_unavailable')); }
	paint();
	for (const button of enables) button.addEventListener('click', async () => {
		if (busy || !registration || !config || !supported) return;
		busy = true; paint();
		try {
			// Permission request stays in the direct click handler, including on iOS.
			const permission = await Notification.requestPermission();
			if (permission !== 'granted') { status(t('push_denied')); return; }
			let subscription = await registration.pushManager.getSubscription();
			if (!subscription) {
				const key = Uint8Array.from(atob(config.public_key.replace(/-/g, '+').replace(/_/g, '/')), char => char.charCodeAt(0));
				subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key });
			}
			try { config = { ...config, ...await request('/api/push/subscribe', { endpoint: subscription.endpoint, consent: true }) }; }
			catch (error) { await subscription.unsubscribe(); throw error; }
			status(preference());
		} catch (error) { status(error.message || t('push_unavailable')); }
		finally { busy = false; paint(); }
	});
	for (const button of disables) button.addEventListener('click', async () => {
		if (busy) return;
		busy = true; paint();
		try {
			config = { ...config, ...await request('/api/push/disable', {}) };
			const subscription = await registration?.pushManager?.getSubscription();
			if (subscription) await subscription.unsubscribe();
			status(config.notifications_enabled === false ? preference() : t('push_off'));
		} catch (error) { status(error.message); }
		finally { busy = false; paint(); }
	});
}
