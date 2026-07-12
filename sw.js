const CACHE = 'taskvel-v2';
self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(['./taskvel-pro.php', './login.php', './manifest.json'])).catch(() => {}));
    self.skipWaiting();
});
self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
    );
    self.clients.claim();
});
self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return; // never cache API POST/DELETE calls
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request).then(cached => cached || caches.match('./taskvel-pro.php')))
    );
});

// ════════════════════════════════════════════
// WEB PUSH — real OS-level notifications that arrive even when Taskvel
// isn't open, on desktop Chrome/Edge/Firefox and on Android/iOS browsers
// that support Web Push (Chrome/Edge on Android; Safari on iOS 16.4+ when
// the app has been "Added to Home Screen").
// ════════════════════════════════════════════
self.addEventListener('push', event => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { title: 'Taskvel', body: event.data ? event.data.text() : 'You have an update.' }; }

    const title = data.title || 'Taskvel';
    const options = {
        body: data.body || '',
        icon: data.icon || 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' rx=\'22\' fill=\'%230a0a0a\'/%3E%3Ctext x=\'50\' y=\'72\' font-family=\'Arial\' font-size=\'62\' font-weight=\'800\' fill=\'%23fff\' text-anchor=\'middle\'%3ET%3C/text%3E%3C/svg%3E',
        badge: data.badge || 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' rx=\'22\' fill=\'%230a0a0a\'/%3E%3C/svg%3E',
        tag: data.tag || 'taskvel-generic',
        renotify: !!data.tag,
        data: { url: data.url || './taskvel-pro.php' },
        vibrate: [80, 40, 80],
        actions: data.actions || [{ action: 'open', title: 'Open Taskvel' }],
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

// Tapping the notification focuses an already-open Taskvel tab if there is
// one, otherwise opens a new one — standard, expected mobile behavior.
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || './taskvel-pro.php';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            for (const client of clientList) {
                if (client.url.includes('taskvel-pro.php') && 'focus' in client) return client.focus();
            }
            if (clients.openWindow) return clients.openWindow(targetUrl);
        })
    );
});
