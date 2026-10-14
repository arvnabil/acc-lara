import { defineConfig } from 'vite';
import { copyFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';
import { bunny } from 'laravel-vite-plugin/fonts';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        VitePWA({
            registerType: 'prompt',
            filename: 'sw.js',
            manifestFilename: 'manifest.webmanifest',
            outDir: 'public',
            buildBase: '/',
            includeAssets: [
                'offline.html',
                'favicon-192.png',
                'favicon-512.png',
                'favicon-512-maskable.png',
            ],
            manifest: {
                name: 'Accommerce.id',
                short_name: 'Accommerce',
                description: 'B2B Enterprise IT & Audio Visual',
                theme_color: '#003599',
                background_color: '#F8F9FA',
                display: 'standalone',
                orientation: 'portrait-primary',
                start_url: '/',
                scope: '/',
                icons: [
                    { src: '/favicon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: '/favicon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
                    { src: '/favicon-512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                maximumFileSizeToCacheInBytes: 10 * 1024 * 1024,
                cleanupOutdatedCaches: true,
                navigateFallback: '/offline.html',
                navigateFallbackDenylist: [
                    /^\/admin(?:\/|$)/,
                    /^\/livewire(?:\/|$)/,
                    /^\/(?:login|register|lupa-password|reset-password)(?:\/|$)/,
                    /^\/(?:akun|keranjang|checkout|pembayaran|sukses|lacak-pesanan|wishlist|loyalty|reviews|cart)(?:\/|$)/,
                ],
                globPatterns: ['**/*.{js,css,html,woff,woff2,ttf,ico,svg,json,png}'],
                globIgnores: ['banner/**', 'products/**', 'storage/**', 'js/filament/**', 'css/filament/**', 'fonts/filament/**', 'livewire/**'],
                runtimeCaching: [
                    {
                        urlPattern: ({ request, url }) => request.mode === 'navigate' && [
                            '/', '/katalog', '/kategori', '/cari', '/bantuan',
                        ].includes(url.pathname) || (request.mode === 'navigate' && url.pathname.startsWith('/produk/')),
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages',
                            networkTimeoutSeconds: 3,
                            expiration: { maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 * 7 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        urlPattern: ({ request, url }) => request.method === 'GET' && /^\/(?:products|banner|storage\/catalog)\//.test(url.pathname),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'catalog-images',
                            expiration: { maxEntries: 300, maxAgeSeconds: 60 * 60 * 24 * 30 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        urlPattern: ({ request, url }) => request.method === 'GET' && (
                            url.origin === 'https://fonts.googleapis.com' || url.origin === 'https://fonts.gstatic.com'
                        ),
                        handler: 'StaleWhileRevalidate',
                        options: { cacheName: 'google-fonts', expiration: { maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 * 365 } },
                    },
                ],
            },
        }),
        {
            name: 'copy-pwa-root-files',
            closeBundle() {
                for (const file of ['sw.js', 'manifest.webmanifest']) {
                    const source = resolve('public/build', file);
                    if (existsSync(source)) copyFileSync(source, resolve('public', file));
                }
            },
        },
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
