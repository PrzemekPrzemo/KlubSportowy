<?php
/** @var string $cacheVersion */
/** @var string $iconUrl */
/** @var ?int   $clubId */
$cv   = preg_replace('/[^A-Za-z0-9\-_.]/', '', $cacheVersion ?? 'clubdesk-v1');
$icon = json_encode($iconUrl ?? '/icons/icon-192.png', JSON_UNESCAPED_SLASHES);
?>
// ClubDesk PWA Service Worker — generated <?= date('c') ?>
// Cache version: <?= $cv ?>
//
// Strategy:
//   - HTML (navigations): network-first → cache → /portal/offline.html
//   - Static assets (css/js/img/font): cache-first → network
//   - GET only (POST/PUT etc passthrough)

'use strict';

const CACHE_VERSION = '<?= $cv ?>';
const CACHE_NAME    = CACHE_VERSION + '-static';
const OFFLINE_URL   = '/portal/offline.html';

const STATIC_ASSETS = [
  OFFLINE_URL,
  '/favicon.svg',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      // addAll fails if ANY request fails — use Promise.all + add to be tolerant
      return Promise.all(
        STATIC_ASSETS.map(function (url) {
          return cache.add(url).catch(function (err) {
            console.warn('[SW] Failed to cache', url, err);
          });
        })
      );
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) { return k !== CACHE_NAME; })
            .map(function (k) { return caches.delete(k); })
      );
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (event) {
  var request = event.request;
  if (request.method !== 'GET') return;

  var url = new URL(request.url);

  // Bypass: API endpoints, auth, portal-internal mutations
  if (url.pathname.startsWith('/api/') ||
      url.pathname.startsWith('/portal/payments/') ||
      url.pathname.startsWith('/portal/push/')) {
    return; // default network handling
  }

  var accept = request.headers.get('accept') || '';
  var isHtml = request.mode === 'navigate' ||
               accept.indexOf('text/html') !== -1;

  if (isHtml) {
    // Network-first, with cached + offline fallback
    event.respondWith(
      fetch(request).then(function (response) {
        // Cache successful HTML for fallback
        if (response.ok && response.type === 'basic') {
          var clone = response.clone();
          caches.open(CACHE_NAME).then(function (c) { c.put(request, clone); });
        }
        return response;
      }).catch(function () {
        return caches.match(request).then(function (cached) {
          return cached || caches.match(OFFLINE_URL);
        });
      })
    );
    return;
  }

  // Static asset: cache-first
  event.respondWith(
    caches.match(request).then(function (cached) {
      if (cached) return cached;
      return fetch(request).then(function (response) {
        if (response && response.ok &&
            (response.type === 'basic' || response.type === 'cors')) {
          var clone = response.clone();
          caches.open(CACHE_NAME).then(function (c) { c.put(request, clone); });
        }
        return response;
      }).catch(function () {
        return cached; // undefined if no cache — browser handles
      });
    })
  );
});

// ──────────────────────────────────────────────────────────
// Push notifications (FCM web push integration)
// ──────────────────────────────────────────────────────────
self.addEventListener('push', function (event) {
  var data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) {
    try { data = { title: 'ClubDesk', body: event.data ? event.data.text() : '' }; }
    catch (e2) { data = {}; }
  }
  var title = data.title || (data.notification && data.notification.title) || 'ClubDesk';
  var body  = data.body  || (data.notification && data.notification.body)  || '';
  var url   = (data.data && data.data.url) || data.url || '/portal/dashboard';

  event.waitUntil(
    self.registration.showNotification(title, {
      body: body,
      icon: <?= $icon ?>,
      badge: <?= $icon ?>,
      data: { url: url },
      tag:  data.tag || 'clubdesk-notification',
      renotify: true
    })
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var targetUrl = (event.notification.data && event.notification.data.url) || '/portal/dashboard';
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var c = clientList[i];
        if (c.url.indexOf(targetUrl) !== -1 && 'focus' in c) {
          return c.focus();
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(targetUrl);
      }
    })
  );
});

// Allow page to ask SW to update immediately
self.addEventListener('message', function (event) {
  if (event.data === 'SKIP_WAITING') self.skipWaiting();
});
