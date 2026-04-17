const CACHE_NAME = 'erp-v2';
const ASSETS = [
  '/'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        // Use individual add() calls so one failure doesn't break the whole install
        return Promise.allSettled(ASSETS.map(url => cache.add(url)));
      })
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  // Only cache GET requests, skip cross-origin and API calls
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  if (url.origin !== location.origin) return;

  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
      .catch(() => fetch(event.request))
  );
});
