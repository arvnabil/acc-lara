import { defineConfig } from 'vite';
import { copyFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'prompt',
            srcDir: 'resources/js',
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
            strategies: 'injectManifest',
            injectManifest: {
                globPatterns: ['**/*.{js,css,html,woff,woff2,ttf,ico,svg,json,png}'],
                globIgnores: ['banner/**', 'products/**', 'storage/**', 'js/filament/**', 'css/filament/**', 'fonts/filament/**', 'livewire/**'],
                maximumFileSizeToCacheInBytes: 10 * 1024 * 1024,
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
