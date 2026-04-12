const CACHE_VERSION = 'ks-v1';
const SHELL_ASSETS = [
  '/',
  '/portal/dashboard',
  '/css/app.css',
  '/js/app.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'
];

// Install: cache the app shell
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_VERSION).then(function(cache) {
      return cache.addAll(SHELL_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(keys) {
      return Promise.all(
        keys.filter(function(key) {
          return key !== CACHE_VERSION;
        }).map(function(key) {
          return caches.delete(key);
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch: routing strategies
self.addEventListener('fetch', function(event) {
  var request = event.request;
  var url = new URL(request.url);

  // API calls: network-first
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request).then(function(response) {
        var clone = response.clone();
        caches.open(CACHE_VERSION).then(function(cache) {
          cache.put(request, clone);
        });
        return response;
      }).catch(function() {
        return caches.match(request);
      })
    );
    return;
  }

  // Static assets (css, js, icons, images): cache-first
  if (
    url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/) ||
    url.pathname.startsWith('/icons/')
  ) {
    event.respondWith(
      caches.match(request).then(function(cached) {
        if (cached) {
          return cached;
        }
        return fetch(request).then(function(response) {
          var clone = response.clone();
          caches.open(CACHE_VERSION).then(function(cache) {
            cache.put(request, clone);
          });
          return response;
        });
      })
    );
    return;
  }

  // HTML pages: network-first with offline fallback
  event.respondWith(
    fetch(request).then(function(response) {
      var clone = response.clone();
      caches.open(CACHE_VERSION).then(function(cache) {
        cache.put(request, clone);
      });
      return response;
    }).catch(function() {
      return caches.match(request).then(function(cached) {
        return cached || caches.match('/offline.html');
      });
    })
  );
});
