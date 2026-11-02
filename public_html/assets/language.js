// Run before styles so a saved explicit choice can be restored before painting.
(() => {
	try {
		const url = new URL(location.href);
		const explicit = url.searchParams.get('lang');
		if (explicit === 'sv' || explicit === 'en') {
			localStorage.setItem('dilemma:locale', explicit);
			return;
		}
		const saved = localStorage.getItem('dilemma:locale');
		if ((saved === 'sv' || saved === 'en') && saved !== document.documentElement.lang) {
			url.searchParams.set('lang', saved);
			location.replace(url.href);
		}
	} catch { /* Storage can be unavailable; server language negotiation still works. */ }
})();

// Retain the browser event, never open an automatic installation prompt.
window.addEventListener('beforeinstallprompt', event => {
	event.preventDefault();
	window.dilemmaInstallPrompt = event;
	window.dispatchEvent(new Event('dilemma-install-ready'));
});
