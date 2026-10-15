import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { ExpirationPlugin } from 'workbox-expiration';
import { registerRoute, setCatchHandler } from 'workbox-routing';
import { CacheFirst, NetworkFirst, StaleWhileRevalidate } from 'workbox-strategies';

const navigationDenylist = [
    'admin', 'livewire', 'login', 'register', 'lupa-password', 'reset-password',
    'akun', 'keranjang', 'checkout', 'pembayaran', 'sukses', 'lacak-pesanan',
    'wishlist', 'loyalty', 'reviews', 'cart',
];

const isDeniedNavigation = (pathname) => {
    const segments = pathname.split('/').filter(Boolean);
    return navigationDenylist.some((segment) => segments.includes(segment));
};

const precacheName = 'accommerce-precache-v1';
const precacheEntries = self.__WB_MANIFEST;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(precacheName).then((cache) => cache.addAll(
            precacheEntries.map((entry) => typeof entry === 'string' ? entry : entry.url),
        )),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key.startsWith('accommerce-precache-') && key !== precacheName)
                .map((key) => caches.delete(key)),
        )),
    );
});

const matchPrecached = (url) => caches.open(precacheName)
    .then((cache) => cache.match(new URL(url, self.location.origin).pathname));

const publicPagePaths = ['/','/katalog','/kategori','/cari','/bantuan'];
const isPublicPagePath = (pathname) => publicPagePaths.includes(pathname)
    || pathname.startsWith('/produk/');

registerRoute(
    ({ request, url }) => request.method === 'GET'
        && request.headers.get('X-Inertia') === 'true'
        && !isDeniedNavigation(url.pathname)
        && isPublicPagePath(url.pathname),
    new NetworkFirst({
        cacheName: 'inertia-pages',
        networkTimeoutSeconds: 3,
        plugins: [
            new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 * 7 }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    }),
);

registerRoute(
    ({ request, url }) => request.mode === 'navigate'
        && !isDeniedNavigation(url.pathname)
        && isPublicPagePath(url.pathname),
    new NetworkFirst({
        cacheName: 'pages',
        networkTimeoutSeconds: 3,
        plugins: [
            new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 * 7 }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    }),
);

registerRoute(
    ({ request, url }) => request.method === 'GET'
        && /^\/(?:products|banner|storage\/catalog)\//.test(url.pathname),
    new CacheFirst({
        cacheName: 'catalog-images',
        plugins: [
            new ExpirationPlugin({ maxEntries: 300, maxAgeSeconds: 60 * 60 * 24 * 30 }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    }),
);

registerRoute(
    ({ request, url }) => request.method === 'GET'
        && ['https://fonts.googleapis.com', 'https://fonts.gstatic.com'].includes(url.origin),
    new StaleWhileRevalidate({
        cacheName: 'google-fonts',
        plugins: [new ExpirationPlugin({ maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 * 365 })],
    }),
);

setCatchHandler(async ({ event }) => {
    if (event.request.mode === 'navigate') {
        return matchPrecached('/offline.html');
    }

    return Response.error();
});
