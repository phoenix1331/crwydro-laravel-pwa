import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

// Changes every build so the precached `/` shell is refreshed alongside the hashed assets
const buildId = new Date().toISOString();

/**
 * Vite emits the web manifest into its outDir (public/build), but vite-plugin-pwa
 * precaches it as if it sat beside the worker at the web root. Point the entry at
 * the real file, otherwise the precache 404s and the worker never installs.
 */
function webManifestPrecachePath() {
    return {
        name: 'crwydro:web-manifest-precache-path',
        enforce: 'post',
        buildStart() {
            const pwa = this.environment?.config.plugins.find((plugin) => plugin.name === 'vite-plugin-pwa');

            pwa?.api?.extendManifestEntries((entries) =>
                entries.map((entry) =>
                    typeof entry !== 'string' && entry.url === 'manifest.webmanifest'
                        ? { ...entry, url: 'build/manifest.webmanifest' }
                        : entry,
                ),
            );
        },
    };
}

export default defineConfig({
    define: {
        __BUILD_ID__: JSON.stringify(buildId),
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/web-map.js'],
            refresh: true,
            fonts: [
                // Designed for legibility: holds up in glare and with cold, gloved hands
                bunny('Atkinson Hyperlegible Next', {
                    weights: [400, 700, 800],
                }),
            ],
        }),
        tailwindcss(),
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            // Vite builds into public/build, but the worker and manifest must sit at the web root to control `/`
            outDir: 'public',
            base: '/',
            scope: '/',
            buildBase: '/',
            registerType: 'prompt',
            injectRegister: false,
            manifestFilename: 'manifest.webmanifest',
            manifest: {
                id: '/',
                name: 'Crwydro',
                short_name: 'Crwydro',
                description: 'Offline field journal for walkers in the Welsh hills',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                orientation: 'portrait',
                background_color: '#f4f6f3',
                theme_color: '#2f4c25',
                lang: 'en-GB',
                icons: [
                    { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            injectManifest: {
                globDirectory: 'public',
                globPatterns: ['build/assets/*.{js,css,woff2,woff,svg}', 'icons/*.png', 'routes/*.geojson'],
                additionalManifestEntries: [{ url: '/', revision: buildId }],
            },
            devOptions: {
                enabled: false,
            },
        }),
        webManifestPrecachePath(),
    ],
    server: {
        // Vite runs inside the app container; the browser reaches it through the published port
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
