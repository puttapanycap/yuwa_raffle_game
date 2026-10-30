/**
 * Yuwa Raffle — Service Worker
 * Strategy
 *  - App shell (launcher, offline pages, CSS, JS, fonts, icons): precached
 *  - Pages (.php / navigations): network-first → cached copy → offline.html
 *  - Static same-origin assets: stale-while-revalidate
 *  - Google Fonts / CDN libs: stale-while-revalidate (opaque OK)
 *  - ./actions/* API, POST, audio/video: never cached (always live)
 * Bump VERSION on every deploy that changes shell files.
 */
const VERSION = 'v1.0.0-2026.10.30';
const SHELL_CACHE = `yuwa-shell-${VERSION}`;
const PAGE_CACHE = `yuwa-pages-${VERSION}`;
const ASSET_CACHE = `yuwa-assets-${VERSION}`;
const PAGE_CACHE_LIMIT = 30;

const SHELL = [
  './start.html',
  './offline.html',
  './offline/',
  './offline/index.html',
  './manifest.webmanifest',
  './css/cartoon-game.css',
  './assets/css/fonts.css',
  './assets/css/fa_pro_67.css',
  './assets/fonts/fa_pro_67/fa-solid-900.woff2',
  './assets/fonts/LINESeedSansTH/WOFF2/LINESeedSansTH_W_Rg.woff2',
  './assets/fonts/LINESeedSansTH/WOFF2/LINESeedSansTH_W_Bd.woff2',
  './assets/fonts/LINESeedSansTH/WOFF2/LINESeedSansTH_W_XBd.woff2',
  './js/game-dialog.js',
  './js/pwa.js',
  './js/confetti.js',
  './js/aurora-poller.js',
  './js/aurora-drawer.js',
  './js/raffle-display.js',
  './js/raffle-remote.js',
  './js/raffle-settings.js',
  './js/raffle-vip.js',
  './assets/medias/icons/icon.svg',
  './assets/medias/icons/icon-32.png',
  './assets/medias/icons/icon-192.png',
  './assets/medias/icons/icon-512.png',
  './assets/medias/icons/maskable-512.png',
  './assets/medias/icons/apple-touch-icon.png',
  './assets/medias/logos/favicon-32x32.png',
];

const scopeUrl = new URL(self.registration.scope);
const inScope = (url) => url.href.startsWith(scopeUrl.href);
const scoped = (p) => new URL(p, scopeUrl).href;

// ------------------------------------------------------------- lifecycle
self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(SHELL_CACHE);
    // Cache individually so one missing file never blocks installation
    await Promise.all(SHELL.map(async (p) => {
      try {
        const res = await fetch(new Request(scoped(p), { cache: 'reload' }));
        if (res.ok) await cache.put(scoped(p), res);
      } catch (_) { /* ignore */ }
    }));
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keep = new Set([SHELL_CACHE, PAGE_CACHE, ASSET_CACHE]);
    const names = await caches.keys();
    await Promise.all(names.filter((n) => n.startsWith('yuwa-') && !keep.has(n)).map((n) => caches.delete(n)));
    if (self.registration.navigationPreload) {
      try { await self.registration.navigationPreload.enable(); } catch (_) {}
    }
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});

// ---------------------------------------------------------------- fetch
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  if (req.destination === 'audio' || req.destination === 'video' || req.headers.has('range')) return;

  const url = new URL(req.url);

  // Cross-origin: fonts & CDN libraries
  if (url.origin !== self.location.origin) {
    if (/^(fonts\.googleapis\.com|fonts\.gstatic\.com|cdn\.jsdelivr\.net)$/.test(url.hostname)) {
      event.respondWith(staleWhileRevalidate(req, ASSET_CACHE));
    }
    return;
  }
  if (!inScope(url)) return;

  // Live API — never cache
  if (url.pathname.includes('/actions/')) return;
  // Uploaded sound files — let the browser handle range requests
  if (url.pathname.includes('/uploads/')) return;

  const isPage = req.mode === 'navigate' || url.pathname.endsWith('.php') || url.pathname.endsWith('/');
  if (isPage) {
    event.respondWith(networkFirstPage(event));
    return;
  }

  event.respondWith(staleWhileRevalidate(req, ASSET_CACHE));
});

async function networkFirstPage(event) {
  const req = event.request;
  const url = new URL(req.url);
  const isStatic = url.pathname.endsWith('.html');
  try {
    const preload = event.preloadResponse ? await event.preloadResponse : null;
    const res = preload || await fetch(req);
    // Never cache "create a new raffle" redirects (index.php / settings.php without ?share)
    if (res.ok && !res.redirected && (isStatic || url.searchParams.has('share') || url.pathname.endsWith('/offline/'))) {
      const cache = await caches.open(isStatic ? SHELL_CACHE : PAGE_CACHE);
      cache.put(req, res.clone()).then(() => trimCache(PAGE_CACHE, PAGE_CACHE_LIMIT));
    }
    return res;
  } catch (_) {
    const cached = await caches.match(req, { ignoreVary: true }) ||
                   (isStatic ? await caches.match(req, { ignoreSearch: true }) : null);
    if (cached) return cached;
    return (await caches.match(scoped('./offline.html'))) ||
      new Response('<h1>Offline</h1>', { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
  }
}

async function staleWhileRevalidate(req, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(req) || await caches.match(req) ||
                 await caches.match(req, { ignoreSearch: true });
  const network = fetch(req).then((res) => {
    if (res && (res.ok || res.type === 'opaque')) cache.put(req, res.clone());
    return res;
  }).catch(() => null);
  if (cached) return cached;
  const res = await network;
  return res || new Response('', { status: 504 });
}

async function trimCache(name, max) {
  const cache = await caches.open(name);
  const keys = await cache.keys();
  for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
}
