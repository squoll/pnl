self.addEventListener('install', (e) => {
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  return self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  // Pass-through fetch for basic PWA requirement
  e.respondWith(fetch(e.request).catch(() => new Response("Offline mode not implemented.")));
});
