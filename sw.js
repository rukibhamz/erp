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
  // Never intercept uploaded media files. Corrupt/truncated assets should come
  // directly from the server/CDN and not through stale SW cache logic.
  if (url.pathname.startsWith('/uploads/')) return;

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) return response;
        return fetch(event.request).catch(() => {
          // Keep browser behavior predictable: for navigation requests,
          // fallback to cached shell if available.
          if (event.request.mode === 'navigate') {
            return caches.match('/');
          }
          return Response.error();
        });
      })
  );
});
