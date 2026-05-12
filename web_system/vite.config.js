import { defineConfig } from 'vite';
import legacy from '@vitejs/plugin-legacy';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        legacy({
            targets: [
                'defaults',
                'iOS >= 12',
                'Safari >= 12',
                'Chrome >= 70',
                'Edge >= 79',
                'Firefox >= 78',
                'Android >= 8',
            ],
            modernPolyfills: true,
            renderLegacyChunks: true,
        }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
