// No page/API caching: private debates and magic links never enter offline storage.
const CACHE = 'dilemma-shell-v2';
const OFFLINE = '/offline.html';
self.addEventListener('install', event => {
	event.waitUntil(caches.open(CACHE).then(cache => cache.addAll([OFFLINE, '/assets/icon-192.png', '/assets/grain.svg'])));
});
self.addEventListener('activate', event => {
	event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('dilemma-shell-') && key !== CACHE).map(key => caches.delete(key)))));
});
self.addEventListener('fetch', event => {
	const url = new URL(event.request.url);
	if (event.request.method === 'GET' && url.origin === self.location.origin && ['/assets/icon-192.png', '/assets/grain.svg'].includes(url.pathname)) {
		event.respondWith(caches.match(url.pathname).then(cached => cached || fetch(event.request)));
		return;
	}
	if (event.request.mode === 'navigate' && new URL(event.request.url).origin === self.location.origin) {
		event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE)));
	}
});
self.addEventListener('push', event => {
	// Deliberately empty push payload. The device supplies a generic bilingual notice.
	event.waitUntil(self.registration.showNotification('Dilemma', {
		body: 'Your debate has an update. / Nytt i din debatt.',
		icon: '/assets/icon-192.png', badge: '/assets/badge.png', tag: 'dilemma-update'
	}));
});
self.addEventListener('notificationclick', event => {
	event.notification.close();
	event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async windows => {
		const target = new URL('/app', self.location.origin).href;
		const existing = windows.find(client => client.url === target);
		if (existing) return existing.focus();
		return clients.openWindow(target);
	}));
});
