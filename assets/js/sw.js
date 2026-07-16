/**
 * Service Worker — Les Randos de Nono
 * Permet de consulter hors-ligne les randonnées déjà visitées (récit, carte,
 * trace GPX) : utile sur le terrain, sans réseau. Servi dynamiquement à la
 * racine du site via une réécriture WordPress (voir rando_nono_serve_sw()
 * dans functions.php), qui remplace les jetons ci-dessous.
 */
const CACHE_VERSION = '__CACHE_VERSION__';
const CACHE_NAME = 'rando-nono-' + CACHE_VERSION;
const OFFLINE_URL = '__OFFLINE_URL__';
const APP_SHELL = __APP_SHELL_JSON__;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  // Ne pas intercepter les ressources externes (tuiles OSM, CDN, analytics, météo) :
  // on laisse le navigateur les gérer normalement.
  if (url.origin !== self.location.origin) return;

  // Navigation (pages HTML) : réseau en priorité pour un contenu à jour,
  // cache en secours, page "hors ligne" en dernier recours.
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          return res;
        })
        .catch(() => caches.match(req).then((cached) => cached || caches.match(OFFLINE_URL)))
    );
    return;
  }

  // Traces GPX, images, CSS, JS : cache en priorité (rapide, dispo hors-ligne),
  // mise à jour silencieuse en arrière-plan si le réseau répond.
  if (/\.(gpx|css|js|png|jpe?g|webp|svg|woff2?)$/i.test(url.pathname)) {
    event.respondWith(
      caches.match(req).then((cached) => {
        const network = fetch(req)
          .then((res) => {
            const copy = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
            return res;
          })
          .catch(() => cached);
        return cached || network;
      })
    );
  }
});
